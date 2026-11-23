<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\Tests;

use Contao\CoreBundle\String\SimpleTokenParser;
use Contao\Database\Result;
use Contao\Email;
use Contao\Newsletter;
use Contao\NewsletterBundle\Event\SendNewsletterEvent;
use Contao\NewsletterBundle\EventListener\NewsletterFailureListener;
use Contao\NewsletterBundle\Mailer\NewsletterFailureManager;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Mailer\Event\FailedMessageEvent;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Messenger\MessageHandler;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\AbstractStream;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Mime\Email as MimeEmail;
use Symfony\Component\Mime\Exception\RfcComplianceException;

class NewsletterTest extends ContaoTestCase
{
    /**
     * @dataProvider recipientProvider
     */
    public function testAttachesNewsletterContext(array $recipient): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(static fn (MimeEmail $email): bool => '42' === $email->getHeaders()->get('Contao-Newsletter-ID')?->getBodyAsString()
                    && 'recipient@example.com' === $email->getTo()[0]->getAddress()))
        ;

        $this->assertTrue($this->sendNewsletter($mailer, $recipient));
    }

    public static function recipientProvider(): iterable
    {
        yield 'subscriber' => [['recipient' => '12', 'email' => 'recipient@example.com']];
        yield 'preview' => [['email' => 'recipient@example.com']];
    }

    public function testCanStillSkipSending(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects($this->never())
            ->method('send')
        ;

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(SendNewsletterEvent::class, static fn (SendNewsletterEvent $event) => $event->setSkipSending(true));

        $this->assertFalse($this->sendNewsletter($mailer, ['email' => 'recipient@example.com'], $dispatcher));
    }

    public function testUsesRecipientChangedByEvent(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(static fn (MimeEmail $email): bool => 'changed@example.com' === $email->getTo()[0]->getAddress()))
        ;

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(SendNewsletterEvent::class, static fn (SendNewsletterEvent $event) => $event->setRecipientAddress('changed@example.com'));

        $this->assertTrue($this->sendNewsletter($mailer, ['recipient' => 12, 'email' => 'recipient@example.com'], $dispatcher));
    }

    public function testReturnsFalseOnSynchronousFailure(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects($this->once())
            ->method('send')
            ->willThrowException(new TransportException('Failure'))
        ;

        $this->assertFalse($this->sendNewsletter($mailer, ['recipient' => 12, 'email' => 'recipient@example.com']));
    }

    /**
     * @dataProvider smtpResponseProvider
     */
    public function testRecordsFailureWithSynchronousMessengerDelivery(string $response): void
    {
        $manager = $this->createMock(NewsletterFailureManager::class);
        $manager
            ->expects($this->once())
            ->method('recordFailure')
            ->with(
                42,
                'recipient@example.com',
                $this->callback(static fn (\Throwable $error): bool => str_contains($error->getMessage(), $response)),
            )
        ;

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(FailedMessageEvent::class, (new NewsletterFailureListener($manager))->onFailedMessageEvent(...));

        // Exercise the actual SMTP transport and its debug output without opening a
        // network connection. The last two replies handle RSET and QUIT.
        $stream = new class($response) extends AbstractStream {
            public function __construct(string $response)
            {
                $this->in = fopen('php://memory', 'w+');
                $this->out = fopen('php://memory', 'w+');

                fwrite($this->out, "220 Ready\r\n250 Hello\r\n250 Sender OK\r\n".$response."\r\n250 Reset OK\r\n221 Bye\r\n");
                rewind($this->out);
            }

            public function initialize(): void
            {
            }

            protected function getReadConnectionDescription(): string
            {
                return 'SMTP test';
            }
        };

        $transport = new SmtpTransport($stream, $dispatcher);
        $bus = new MessageBus([new HandleMessageMiddleware(new HandlersLocator([SendEmailMessage::class => [new MessageHandler($transport)]]))]);
        $mailer = new Mailer($transport, $bus, $dispatcher);

        $this->assertFalse($this->sendNewsletter($mailer, ['recipient' => 12, 'email' => 'recipient@example.com'], $dispatcher));
    }

    public static function smtpResponseProvider(): iterable
    {
        yield 'permanent recipient failure' => ['550 5.1.1 Unknown recipient'];
        yield 'temporary rate limit' => ['451 4.7.0 Rate limit exceeded'];
        yield 'permanent policy failure' => ['550 5.7.1 Message blocked'];
    }

    /**
     * @dataProvider invalidRecipientProvider
     */
    public function testRecordsInvalidAddressBeforeSending(array $recipient, bool $useMessenger): void
    {
        $recipient['email'] = 'invalid-email';
        $transport = $this->createMock(TransportInterface::class);
        $transport
            ->expects($this->never())
            ->method('send')
        ;

        $bus = $useMessenger ? $this->createMock(MessageBusInterface::class) : null;

        if ($bus) {
            $bus
                ->expects($this->never())
                ->method('dispatch')
            ;
        }

        $mailer = new Mailer($transport, $bus);
        $manager = $this->createMock(NewsletterFailureManager::class);
        $manager
            ->expects($this->once())
            ->method('recordFailure')
            ->with(
                42,
                'invalid-email',
                $this->callback(static fn (RfcComplianceException $error): bool => str_contains($error->getMessage(), 'does not comply with addr-spec')),
            )
        ;

        $this->assertFalse($this->sendNewsletter($mailer, $recipient, null, $manager));
    }

    public static function invalidRecipientProvider(): iterable
    {
        foreach (self::recipientProvider() as $label => [$recipient]) {
            yield $label.' without Messenger' => [$recipient, false];
            yield $label.' with Messenger' => [$recipient, true];
        }
    }

    private function sendNewsletter(MailerInterface $mailer, array $recipient, EventDispatcher|null $dispatcher = null, NewsletterFailureManager|null $failureManager = null): bool
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.charset', 'UTF-8');
        $container->set('contao.string.simple_token_parser', new SimpleTokenParser(new ExpressionLanguage()));
        $container->set('event_dispatcher', $dispatcher ?? new EventDispatcher());
        $container->set('monolog.logger.contao.error', $this->createMock(LoggerInterface::class));

        if ($failureManager) {
            $container->set('contao_newsletter.mailer.newsletter_failure_manager', $failureManager);
        }

        System::setContainer($container);

        $email = new Email($mailer);
        $email->from = 'sender@example.com';

        $data = ['id' => 42, 'pid' => 7, 'sender' => 'sender@example.com', 'sendText' => true];
        $result = $this->mockClassWithProperties(Result::class, $data);
        $result
            ->method('row')
            ->willReturn($data)
        ;

        $newsletter = (new \ReflectionClass(Newsletter::class))->newInstanceWithoutConstructor();

        return (new \ReflectionMethod(Newsletter::class, 'sendNewsletter'))->invoke($newsletter, $email, $result, $recipient, 'Newsletter', '');
    }
}
