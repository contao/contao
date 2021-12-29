<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Command;

use Contao\CoreBundle\Command\MailerSendCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailerSendCommandTest extends TestCase
{
    public function testSendsEmail(): void
    {
        $from = 'from@example.com';
        $to = 'to@example.com';
        $subject = 'Foobar';
        $body = 'Lorem ipsum dolor sit amet.';

        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(
                static function (Email $message) use ($from, $to, $subject, $body): bool {
                    return
                        $message->getFrom()[0]->getAddress() === $from &&
                        $message->getTo()[0]->getAddress() === $to &&
                        $message->getSubject() === $subject &&
                        $message->getTextBody() === $body
                    ;
                }
            ))
        ;

        $command = new MailerSendCommand($mailer);

        $tester = new CommandTester($command);
        $tester->execute([
            '--from' => $from,
            '--to' => $to,
            '--subject' => $subject,
            '--body' => $body,
        ]);
    }

    public function testUsesCustomTransport(): void
    {
        $transport = 'foobar';

        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(static fn (Email $message): bool => $message->getHeaders()->getHeaderBody('X-Transport') === $transport))
        ;

        $command = new MailerSendCommand($mailer);

        $tester = new CommandTester($command);
        $tester->execute([
            '--from' => 'from@example.com',
            '--to' => 'to@example.com',
            '--subject' => 'Foobar',
            '--body' => 'Lorem ipsum dolor sit amet.',
            '--transport' => $transport,
        ]);
    }
}
