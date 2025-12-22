<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\EventListener\LogEmailMessageListener;
use Contao\CoreBundle\Tests\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\FailedMessageEvent;
use Symfony\Component\Mailer\Event\SentMessageEvent;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class LogEmailMessageListenerTest extends TestCase
{
    public function testLogsSentEmailMessage(): void
    {
        $emailLogger = $this->createMock(LoggerInterface::class);
        $emailLogger
            ->expects($this->once())
            ->method('info')
            ->with('An e-mail has been sent to "Foo Bar" <foobar@example.com>, CC to lorem@example.com, "Max Meladze" <ipsum@example.com>, BCC to dolor@example.com')
        ;

        $emailMessage = (new Email())
            ->from('contao@example.com')
            ->to(new Address('foobar@example.com', 'Foo Bar'))
            ->cc(new Address('lorem@example.com'), new Address('ipsum@example.com', 'Max Meladze'))
            ->bcc(new Address('dolor@example.com'))
            ->text('Test')
        ;

        $sentMessage = new SentMessage($emailMessage, $this->createStub(Envelope::class));
        $event = new SentMessageEvent($sentMessage);

        $listener = new LogEmailMessageListener($emailLogger, $this->createStub(LoggerInterface::class));
        $listener->onSentMessageEvent($event);
    }

    public function testLogsFailedEmailMessage(): void
    {
        $errorLogger = $this->createMock(LoggerInterface::class);
        $errorLogger
            ->expects($this->once())
            ->method('error')
            ->with('Failed to send e-mail to "Foo Bar" <foobar@example.com>, CC to lorem@example.com, "Max Meladze" <ipsum@example.com>, BCC to dolor@example.com')
        ;

        $emailMessage = (new Email())
            ->from('contao@example.com')
            ->to(new Address('foobar@example.com', 'Foo Bar'))
            ->cc(new Address('lorem@example.com'), new Address('ipsum@example.com', 'Max Meladze'))
            ->bcc(new Address('dolor@example.com'))
            ->text('Test')
        ;

        $event = new FailedMessageEvent($emailMessage, new \Exception());

        $listener = new LogEmailMessageListener($this->createStub(LoggerInterface::class), $errorLogger);
        $listener->onFailedMessagEvent($event);
    }
}
