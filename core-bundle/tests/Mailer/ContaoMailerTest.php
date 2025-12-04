<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Mailer;

use Contao\CoreBundle\Mailer\AvailableTransports;
use Contao\CoreBundle\Mailer\ContaoMailer;
use Contao\CoreBundle\Mailer\TransportConfig;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Header\UnstructuredHeader;

class ContaoMailerTest extends TestCase
{
    public function testSetsTransportForRequest(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->mailerTransport = 'foobar';

        $request = new Request();
        $request->attributes->set('pageModel', $pageModel);

        $requestStack = new RequestStack([$request]);

        $transport = $this->createMock(TransportInterface::class);
        $mailer = new Mailer($transport);

        $availableTransports = new AvailableTransports();
        $availableTransports->addTransport(new TransportConfig('foobar', 'Lorem Ipsum <lorem@example.com>'));

        $email = new Email();

        $contaoMailer = new ContaoMailer($mailer, $availableTransports, $requestStack);
        $contaoMailer->send($email);

        $this->assertTrue($email->getHeaders()->has('X-Transport'));
        $this->assertSame('foobar', $email->getHeaders()->get('X-Transport')->getBodyAsString());
    }

    public function testSetsFrom(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $mailer = new Mailer($transport);

        $email = new Email();
        $email->from('Foo Bar <foobar@example.com>');

        $contaoMailer = new ContaoMailer($mailer, new AvailableTransports(), new RequestStack(), 'Override From <override@example.com>');
        $contaoMailer->send($email);

        $from = $email->getFrom();

        $this->assertCount(1, $from);
        $this->assertSame('Override From', $from[0]->getName());
        $this->assertSame('override@example.com', $from[0]->getAddress());
        $this->assertNull($email->getReturnPath());
        $this->assertNull($email->getSender());
    }

    public function testSetsFromForTransport(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $mailer = new Mailer($transport);

        $availableTransports = new AvailableTransports();
        $availableTransports->addTransport(new TransportConfig('foobar', 'Lorem Ipsum <lorem@example.com>'));

        $email = new Email(new Headers(new UnstructuredHeader('X-Transport', 'foobar')));
        $email->from('Foo Bar <foobar@example.com>');

        $contaoMailer = new ContaoMailer($mailer, $availableTransports, new RequestStack(), 'Override From <override@example.com>');
        $contaoMailer->send($email);

        $from = $email->getFrom();

        $this->assertCount(1, $from);
        $this->assertSame('Lorem Ipsum', $from[0]->getName());
        $this->assertSame('lorem@example.com', $from[0]->getAddress());
        $this->assertNull($email->getReturnPath());
        $this->assertNull($email->getSender());
    }

    public function testSetsFromReturnPathAndSenderForTransport(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $mailer = new Mailer($transport);

        $availableTransports = new AvailableTransports();
        $availableTransports->addTransport(new TransportConfig('foobar', 'Lorem Ipsum <lorem@example.com>'));

        $email = new Email(new Headers(new UnstructuredHeader('X-Transport', 'foobar')));
        $email->from('Foo Bar <foobar@example.com>');
        $email->returnPath('return-path@example.com');
        $email->sender('sender@example.com');

        $contaoMailer = new ContaoMailer($mailer, $availableTransports, new RequestStack());
        $contaoMailer->send($email);

        $from = $email->getFrom();

        $this->assertCount(1, $from);
        $this->assertSame('Lorem Ipsum', $from[0]->getName());
        $this->assertSame('lorem@example.com', $from[0]->getAddress());
        $this->assertSame('lorem@example.com', $email->getReturnPath()->getAddress());
        $this->assertSame('lorem@example.com', $email->getSender()->getAddress());
    }

    public function testLeavesEnvelopeUntouched(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $mailer = new Mailer($transport);

        $availableTransports = new AvailableTransports();
        $availableTransports->addTransport(new TransportConfig('foobar', 'Lorem Ipsum <lorem@example.com>'));

        $email = new Email(new Headers(new UnstructuredHeader('X-Transport', 'foobar')));
        $envelope = new Envelope(Address::create('envelope-sender@example.com'), [Address::create('envelope-recipient@example.com')]);

        $contaoMailer = new ContaoMailer($mailer, $availableTransports, new RequestStack());
        $contaoMailer->send($email, $envelope);

        $this->assertSame('envelope-sender@example.com', $envelope->getSender()->getAddress());
    }
}
