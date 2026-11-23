<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\Tests\EventListener;

use Contao\NewsletterBundle\EventListener\NewsletterFailureListener;
use Contao\NewsletterBundle\Mailer\NewsletterFailureManager;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\FailedMessageEvent;
use Symfony\Component\Mailer\Event\SentMessageEvent;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Messenger\MessageHandler;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Messenger\Transport\Sender\SendersLocator;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class NewsletterFailureListenerTest extends TestCase
{
    /**
     * @dataProvider unrelatedMessageProvider
     */
    public function testIgnoresUnrelatedMessages(RawMessage $message): void
    {
        $manager = $this->createMock(NewsletterFailureManager::class);
        $manager
            ->expects($this->never())
            ->method('recordFailure')
        ;

        $manager
            ->expects($this->never())
            ->method('clearFailure')
        ;

        $listener = new NewsletterFailureListener($manager);
        $listener->onFailedMessageEvent(new FailedMessageEvent($message, new TransportException('Failure')));
        $listener->onSentMessageEvent(new SentMessageEvent(new SentMessage($message, new Envelope(new Address('sender@example.com'), [new Address('recipient@example.com')]))));
    }

    public static function unrelatedMessageProvider(): iterable
    {
        yield 'raw message' => [new RawMessage('Raw message')];
        yield 'regular email' => [(new Email())->from('sender@example.com')->to('recipient@example.com')->text('Newsletter')];

        foreach (['', '0', 'invalid'] as $id) {
            $email = (new Email())->from('sender@example.com')->to('recipient@example.com')->text('Newsletter');
            $email->getHeaders()->addTextHeader('Contao-Newsletter-ID', $id);

            yield 'invalid newsletter ID '.$id => [$email];
        }
    }

    public function testRecordsFailureWithNewsletterContext(): void
    {
        $email = (new Email())->to('recipient@example.com');
        $email->getHeaders()->addTextHeader('Contao-Newsletter-ID', '42');

        $error = new TransportException('Failure');
        $manager = $this->createMock(NewsletterFailureManager::class);
        $manager
            ->expects($this->once())
            ->method('recordFailure')
            ->with(42, 'recipient@example.com', $error)
        ;

        $manager
            ->expects($this->never())
            ->method('clearFailure')
        ;

        (new NewsletterFailureListener($manager))->onFailedMessageEvent(new FailedMessageEvent($email, $error));
    }

    public function testClearsFailureForSuccessfulPreview(): void
    {
        $email = (new Email())->from('sender@example.com')->to('recipient@example.com')->text('Newsletter');
        $email->getHeaders()->addTextHeader('Contao-Newsletter-ID', '42');

        $manager = $this->createMock(NewsletterFailureManager::class);
        $manager
            ->expects($this->once())
            ->method('clearFailure')
            ->with(42, 'recipient@example.com')
        ;

        $manager
            ->expects($this->never())
            ->method('recordFailure')
        ;

        (new NewsletterFailureListener($manager))->onSentMessageEvent(new SentMessageEvent(new SentMessage($email, Envelope::create($email))));
    }

    /**
     * @dataProvider deliveryProvider
     */
    public function testClearsFailureOnlyAfterSuccessfulDelivery(string $delivery): void
    {
        /** @var \ArrayObject<int, array> $records */
        $records = new \ArrayObject();
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('executeStatement')
            ->willReturnCallback(
                static function (string $sql, array $data) use ($records): int {
                    $records[] = $data;

                    return 1;
                },
            )
        ;

        $connection
            ->expects($this->once())
            ->method('delete')
            ->with('tl_newsletter_failures', ['pid' => 42, 'email' => 'recipient@example.com'])
            ->willReturnCallback(
                static function () use ($records): int {
                    $records->exchangeArray([]);

                    return 1;
                },
            )
        ;

        $connection
            ->expects($this->never())
            ->method('update')
        ;

        $listener = new NewsletterFailureListener(new NewsletterFailureManager($connection));
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(FailedMessageEvent::class, $listener->onFailedMessageEvent(...));
        $dispatcher->addListener(SentMessageEvent::class, $listener->onSentMessageEvent(...));

        $transport = new class($dispatcher) extends AbstractTransport {
            private bool $fail = true;

            public function __toString(): string
            {
                return 'retry://';
            }

            protected function doSend(SentMessage $message): void
            {
                if ($this->fail) {
                    $this->fail = false;

                    throw new TransportException('Connection refused');
                }
            }
        };

        $queue = new InMemoryTransport(new PhpSerializer());
        $senders = new SendersLocator([SendEmailMessage::class => ['async']], new ServiceLocator(['async' => static fn () => $queue]));
        $handler = new MessageHandler($transport);
        $bus = match ($delivery) {
            'async' => new MessageBus([new SendMessageMiddleware($senders)]),
            'sync' => new MessageBus([new HandleMessageMiddleware(new HandlersLocator([SendEmailMessage::class => [$handler]]))]),
            default => null,
        };
        $mailer = new Mailer($transport, $bus, $dispatcher);

        $email = (new Email())->from('sender@example.com')->to('recipient@example.com')->text('Newsletter');
        $email->getHeaders()->addTextHeader('Contao-Newsletter-ID', '42');

        if ('async' === $delivery) {
            $mailer->send($email);

            $this->assertCount(0, $records);

            $messages = iterator_to_array($queue->get());
            $this->assertCount(1, $messages);
            $send = static fn () => $handler($messages[0]->getMessage());
        } else {
            $send = static fn () => $mailer->send($email);
        }

        try {
            $send();

            $this->fail('The first delivery attempt must fail.');
        } catch (TransportException $error) {
            $this->assertSame('Connection refused', $error->getMessage());
        }

        $this->assertCount(1, $records);

        $send();

        $this->assertCount(0, $records);
    }

    public static function deliveryProvider(): iterable
    {
        yield 'direct delivery' => ['direct'];
        yield 'synchronous Messenger delivery' => ['sync'];
        yield 'queued Messenger delivery' => ['async'];
    }
}
