<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\ServiceAnnotation;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Doctrine\Common\Annotations\AnnotationException;
use PHPUnit\Framework\TestCase;

class HookTest extends TestCase
{
    public function testReturnsTheTagName(): void
    {
        $annotation = new Hook(['hook' => 'foobar']);

        $this->assertSame('contao.hook', $annotation->getName());
    }

    public function testTheNameCannotBeSet(): void
    {
        $annotation = new Hook(['name' => 'foobar', 'hook' => 'foobar']);

        $this->assertSame('contao.hook', $annotation->getName());
    }

    public function testReturnsTheArguments(): void
    {
        $annotation = new Hook(['hook' => 'foobar', 'priority' => 17]);

        $this->assertSame(['hook' => 'foobar', 'priority' => 17], $annotation->getAttributes());
    }

    public function testDoesNotReturnPriorityIfNotSet(): void
    {
        $annotation = new Hook(['hook' => 'foobar']);

        $this->assertSame(['hook' => 'foobar'], $annotation->getAttributes());
    }

    public function testIgnoresUnknownAttributes(): void
    {
        $annotation = new Hook(['hook' => 'foobar', 'foo' => 'bar']);

        $this->assertSame(['hook' => 'foobar'], $annotation->getAttributes());
    }

    public function testThrowsExceptionIfTheHookAttributeIsNotSet(): void
    {
        $this->expectException(AnnotationException::class);
        $this->expectExceptionMessage('[Type Error] Attribute "hook" of @Contao\CoreBundle\ServiceAnnotation\Hook should not be null.');

        new Hook([]);
    }
}
