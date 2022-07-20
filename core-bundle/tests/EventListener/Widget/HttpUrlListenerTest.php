<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\Widget;

use Contao\CoreBundle\EventListener\Widget\HttpUrlListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Widget;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\DocParser;
use Symfony\Contracts\Translation\TranslatorInterface;

class HttpUrlListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([[AnnotationRegistry::class, ['failedToAutoload']], DocParser::class]);

        parent::tearDown();
    }

    public function testReturnsFalseIfNotHttpurlType(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->never())
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $listener = new HttpUrlListener($translator);

        $this->assertFalse($listener('foobar', 'input', $this->createMock(Widget::class)));
    }

    public function testReturnsTrueIfNoString(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->never())
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $listener = new HttpUrlListener($translator);

        $this->assertTrue($listener(HttpUrlListener::RGXP_NAME, [], $this->createMock(Widget::class)));
    }

    public function testAddsErrorIfInputIsNotAbsoluteUrl(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->once())
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $widget = $this->createMock(Widget::class);
        $widget
            ->expects($this->once())
            ->method('addError')
            ->with('ERR.invalidHttpUrl')
        ;

        $listener = new HttpUrlListener($translator);

        $this->assertTrue($listener(HttpUrlListener::RGXP_NAME, 'example.com', $widget));
    }

    public function testAddsErrorIfInputIsNotValidUrl(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->once())
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $widget = $this->createMock(Widget::class);
        $widget
            ->expects($this->once())
            ->method('addError')
            ->with('ERR.url')
        ;

        $listener = new HttpUrlListener($translator);

        $this->assertTrue($listener(HttpUrlListener::RGXP_NAME, 'https://example.com\\', $widget));
    }

    public function testDoesNotAddErrorIfInputIsAbsoluteUrl(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->never())
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $widget = $this->createMock(Widget::class);
        $widget
            ->expects($this->never())
            ->method('addError')
            ->with('ERR.invalidHttpUrl')
        ;

        $listener = new HttpUrlListener($translator);

        $this->assertTrue($listener(HttpUrlListener::RGXP_NAME, 'https://example.com', $widget));
    }
}
