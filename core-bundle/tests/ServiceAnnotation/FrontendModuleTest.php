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

use Contao\CoreBundle\Fragment\Reference\FrontendModuleReference;
use Contao\CoreBundle\ServiceAnnotation\FrontendModule;
use Doctrine\Common\Annotations\AnnotationException;
use PHPUnit\Framework\TestCase;

class FrontendModuleTest extends TestCase
{
    public function testReturnsTheTagName(): void
    {
        $annotation = new FrontendModule(['category' => 'foobar']);

        $this->assertSame(FrontendModuleReference::TAG_NAME, $annotation->getName());
    }

    public function testTheNameCannotBeSet(): void
    {
        $annotation = new FrontendModule(['category' => 'foobar', 'name' => 'foobar']);

        $this->assertSame(FrontendModuleReference::TAG_NAME, $annotation->getName());
    }

    public function testReturnsTheArguments(): void
    {
        $annotation = new FrontendModule(['type' => 'foobar', 'category' => 'foobar', 'template' => 'mod_foobar', 'renderer' => 'esi']);

        $this->assertSame(['type' => 'foobar', 'category' => 'foobar', 'template' => 'mod_foobar', 'renderer' => 'esi'], $annotation->getAttributes());
    }

    public function testDoesNotReturnOptionalArguments(): void
    {
        $annotation = new FrontendModule(['category' => 'foobar']);

        $this->assertSame(['category' => 'foobar'], $annotation->getAttributes());
    }

    public function testIgnoresUnknownAttributes(): void
    {
        $annotation = new FrontendModule(['category' => 'foobar', 'foo' => 'bar']);

        $this->assertSame(['category' => 'foobar'], $annotation->getAttributes());
    }

    public function testReturnsAdditionalAttributes(): void
    {
        $annotation = new FrontendModule(['category' => 'foobar', 'attributes' => ['foo' => 'bar']]);

        $this->assertSame(['foo' => 'bar', 'category' => 'foobar'], $annotation->getAttributes());
    }

    public function testThrowsExceptionIfTheTableAttributeIsNotSet(): void
    {
        $this->expectException(AnnotationException::class);
        $this->expectExceptionMessage('[Type Error] Attribute "category" of @Contao\CoreBundle\ServiceAnnotation\FrontendModule should not be null.');

        new FrontendModule([]);
    }
}
