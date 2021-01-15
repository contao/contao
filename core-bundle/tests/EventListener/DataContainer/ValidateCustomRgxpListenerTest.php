<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\EventListener\DataContainer\ValidateCustomRgxpListener;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Contracts\Translation\TranslatorInterface;

class ValidateCustomRgxpListenerTest extends TestCase
{
    public function testServiceAnnotation(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->never())
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $listener = new ValidateCustomRgxpListener($translator);

        $annotationReader = new AnnotationReader();
        $annotation = $annotationReader->getClassAnnotation(new \ReflectionClass($listener), Callback::class);

        $this->assertSame(
            [
                'table' => 'tl_form_field',
                'target' => 'fields.customRgxp.save',
                'priority' => null,
            ],
            (array) $annotation
        );
    }

    public function testThrowsExceptionIfInvalidRegex(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->once())
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $listener = new ValidateCustomRgxpListener($translator);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ERR.invalidCustomRgxp');

        $listener('foo');
    }

    public function testDoesNotThrowsExceptionIfValidRegex(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->never())
            ->method('trans')
            ->willReturnArgument(0)
        ;

        $listener = new ValidateCustomRgxpListener($translator);

        $this->assertSame('/foo/i', $listener('/foo/i'));
    }
}
