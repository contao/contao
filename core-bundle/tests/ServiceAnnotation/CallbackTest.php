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

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Doctrine\Common\Annotations\AnnotationException;
use PHPUnit\Framework\TestCase;

class CallbackTest extends TestCase
{
    public function testReturnsTheTagName(): void
    {
        $annotation = new Callback(['table' => 'tl_foobar', 'target' => 'foo.bar']);

        $this->assertSame('contao.callback', $annotation->getName());
    }

    public function testTheNameCannotBeSet(): void
    {
        $annotation = new Callback(['name' => 'foobar', 'table' => 'tl_foobar', 'target' => 'foo.bar']);

        $this->assertSame('contao.callback', $annotation->getName());
    }

    public function testReturnsTheArguments(): void
    {
        $annotation = new Callback(['table' => 'tl_foobar', 'target' => 'foo.bar', 'priority' => 17]);

        $this->assertSame(['table' => 'tl_foobar', 'target' => 'foo.bar', 'priority' => 17], $annotation->getAttributes());
    }

    public function testDoesNotReturnPriorityIfNotSet(): void
    {
        $annotation = new Callback(['table' => 'tl_foobar', 'target' => 'foo.bar']);

        $this->assertSame(['table' => 'tl_foobar', 'target' => 'foo.bar'], $annotation->getAttributes());
    }

    public function testIgnoresUnknownAttributes(): void
    {
        $annotation = new Callback(['table' => 'tl_foobar', 'target' => 'foo.bar', 'foo' => 'bar']);

        $this->assertSame(['table' => 'tl_foobar', 'target' => 'foo.bar'], $annotation->getAttributes());
    }

    public function testThrowsExceptionIfTheTableAttributeIsNotSet(): void
    {
        $this->expectException(AnnotationException::class);
        $this->expectExceptionMessage('[Type Error] Attribute "table" of @Contao\CoreBundle\ServiceAnnotation\Callback should not be null.');

        new Callback([]);
    }

    public function testThrowsExceptionIfTheTargetAttributeIsNotSet(): void
    {
        $this->expectException(AnnotationException::class);
        $this->expectExceptionMessage('[Type Error] Attribute "target" of @Contao\CoreBundle\ServiceAnnotation\Callback should not be null.');

        new Callback(['table' => 'tl_foobar']);
    }
}
