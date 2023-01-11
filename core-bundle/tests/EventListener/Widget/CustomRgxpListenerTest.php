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

use Contao\CoreBundle\EventListener\Widget\CustomRgxpListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Widget;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\DocParser;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomRgxpListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([[AnnotationRegistry::class, ['failedToAutoload']], DocParser::class]);

        parent::tearDown();
    }

    public function testReturnsFalseIfNotCustomRgxpType(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->never())
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $listener = new CustomRgxpListener($translator);

        $this->assertFalse($listener('foobar', 'input', $this->createMock(Widget::class)));
    }

    public function testReturnsTrueIfNoCustomRgxpSet(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->never())
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $listener = new CustomRgxpListener($translator);

        $this->assertTrue($listener(CustomRgxpListener::RGXP_NAME, 'input', $this->createMock(Widget::class)));
    }

    public function testAddsErrorIfInputDoesNotMatchCustomRgxp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->once())
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $widget = $this->mockClassWithProperties(Widget::class, ['customRgxp' => '/^foo/i']);
        $widget
            ->expects($this->once())
            ->method('addError')
            ->with('ERR.customRgxp')
        ;

        $listener = new CustomRgxpListener($translator);

        $this->assertTrue($listener(CustomRgxpListener::RGXP_NAME, 'notfoo', $widget));
    }

    public function testDoesNotAddErrorIfInputMatchesCustomRgxp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->never())
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $widget = $this->mockClassWithProperties(Widget::class, ['customRgxp' => '/^foo/i']);
        $widget
            ->expects($this->never())
            ->method('addError')
            ->with('ERR.customRgxp')
        ;

        $listener = new CustomRgxpListener($translator);

        $this->assertTrue($listener(CustomRgxpListener::RGXP_NAME, 'foobar', $widget));
    }
}
