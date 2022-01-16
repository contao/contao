<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Event;

use Contao\CoreBundle\Twig\Event\RenderTemplateEvent;
use PHPUnit\Framework\TestCase;

class RenderTemplateEventTest extends TestCase
{
    public function testGetAndSetValues(): void
    {
        $event = new RenderTemplateEvent('foo.html.twig', ['foo' => 'bar']);

        $this->assertTrue($event->hasValue('foo'));
        $this->assertFalse($event->hasValue('baz'));
        $this->assertSame('bar', $event->getValue('foo'));
        $this->assertSame(['foo' => 'bar'], $event->getContext());

        $event->setValue('baz', 41);
        $event->setValue('baz', 42); // should allow overwriting

        $this->assertTrue($event->hasValue('baz'));
        $this->assertSame(42, $event->getValue('baz'));
        $this->assertSame(['foo' => 'bar', 'baz' => 42], $event->getContext());

        $event->setContext(['some' => 'context']);

        $this->assertSame(['some' => 'context'], $event->getContext());
    }

    public function testThrowsWhenAccessingAnInvalidKey(): void
    {
        $event = new RenderTemplateEvent('foo.html.twig', ['foo' => 'bar']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The context of \'foo.html.twig\' did not contain the requested key \'baz\'.');

        $event->getValue('baz');
    }
}
