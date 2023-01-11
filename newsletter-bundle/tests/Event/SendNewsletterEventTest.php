<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\Tests\Event;

use Contao\NewsletterBundle\Event\SendNewsletterEvent;
use PHPUnit\Framework\TestCase;

class SendNewsletterEventTest extends TestCase
{
    public function testSupportsSkipSending(): void
    {
        $event = new SendNewsletterEvent('foo@bar.baz', 'Foo');

        $this->assertFalse($event->isSkipSending());

        $event->setSkipSending(true);

        $this->assertTrue($event->isSkipSending());
    }

    public function testGetsAndSetsTheNewsletterText(): void
    {
        $event = new SendNewsletterEvent('foo@bar.baz', 'Foo');

        $this->assertSame('Foo', $event->getText());

        $event->setText('Bar');

        $this->assertSame('Bar', $event->getText());
    }

    public function testGetsAndSetsTheNewsletterHtml(): void
    {
        $event = new SendNewsletterEvent('foo@bar.baz', 'Foo', '<strong>bar</strong>');

        $this->assertSame('<strong>bar</strong>', $event->getHtml());

        $event->setHtml('<strong>foo</strong>');

        $this->assertSame('<strong>foo</strong>', $event->getHtml());
    }

    public function testGetsAndSetsTheRecipientAddress(): void
    {
        $event = new SendNewsletterEvent('foo@bar.baz', 'Foo');

        $this->assertSame('foo@bar.baz', $event->getRecipientAddress());

        $event->setRecipientAddress('bar@baz.foo');

        $this->assertSame('bar@baz.foo', $event->getRecipientAddress());
    }

    public function testGetsAndSetsTheRecipientData(): void
    {
        $event = new SendNewsletterEvent('foo@bar.baz', 'Foo');

        $this->assertEmpty($event->getRecipientData());
        $this->assertNull($event->getRecipientValue('company'));

        $event->setRecipientData(['company' => 'ACME']);
        $event->setRecipientValue('id', 42);

        $this->assertSame(['company' => 'ACME', 'id' => 42], $event->getRecipientData());
        $this->assertSame('ACME', $event->getRecipientValue('company'));
        $this->assertSame(42, $event->getRecipientValue('id'));
    }

    public function testGetsAndSetsTheNewsletterData(): void
    {
        $event = new SendNewsletterEvent('foo@bar.baz', 'Foo');

        $this->assertEmpty($event->getNewsletterData());
        $this->assertNull($event->getNewsletterValue('content'));

        $event->setNewsletterData(['content' => 'foo']);
        $event->setNewsletterValue('files', ['foo/bar.jpg']);

        $this->assertSame(['content' => 'foo', 'files' => ['foo/bar.jpg']], $event->getNewsletterData());
        $this->assertSame('foo', $event->getNewsletterValue('content'));
        $this->assertSame(['foo/bar.jpg'], $event->getNewsletterValue('files'));
    }

    public function testCanAllowAndDisallowHtml(): void
    {
        $event = new SendNewsletterEvent('foo@bar.baz', 'Foo');

        $this->assertTrue($event->isHtmlAllowed());

        $event->setHtmlAllowed(false);

        $this->assertFalse($event->isHtmlAllowed());
    }
}
