<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\CoreBundle\Mailer\InlineImageEmbedder;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Email;
use Contao\Environment;
use Contao\System;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email as EmailMessage;

class EmailTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([Environment::class, System::class]);

        parent::tearDown();
    }

    public function testEmbedsImagesIntoTheHtmlBody(): void
    {
        $sentMessage = null;

        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(
                static function (EmailMessage $message) use (&$sentMessage): void {
                    $sentMessage = $message;
                },
            )
        ;

        $container = new ContainerBuilder();
        $container->setParameter('kernel.charset', 'UTF-8');
        $container->set('mailer', $mailer);
        $container->set('contao.mailer.inline_image_embedder', new InlineImageEmbedder($this->createContaoFrameworkStub(), $this->getFixturesDir()));

        System::setContainer($container);
        Environment::set('base', 'https://example.com/');

        $email = new Email();
        $email->from = 'sender@example.com';
        $email->html = '<p><img src="https://example.com/images/dummy.jpg"></p>';
        $email->sendTo('recipient@example.com');

        $this->assertInstanceOf(EmailMessage::class, $sentMessage);
        $this->assertStringContainsString('src="cid:images/dummy.jpg"', $sentMessage->getHtmlBody());
        $this->assertCount(1, $sentMessage->getAttachments());
    }
}
