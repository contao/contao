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
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

class FrontendModuleTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * @group legacy
     */
    public function testReturnsTheTagName(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.4: %s Use the #[AsFrontendModule] attribute instead.');

        $annotation = new FrontendModule(['category' => 'foobar']);

        $this->assertSame(FrontendModuleReference::TAG_NAME, $annotation->getName());
    }

    public function testReturnsTheArguments(): void
    {
        $annotation = new FrontendModule([
            'value' => 'foobar',
            'category' => 'foobar',
            'template' => 'mod_foobar',
            'renderer' => 'esi',
        ]);

        $this->assertSame(
            [
                'category' => 'foobar',
                'template' => 'mod_foobar',
                'renderer' => 'esi',
                'type' => 'foobar',
            ],
            $annotation->getAttributes(),
        );
    }

    public function testDoesNotReturnOptionalArguments(): void
    {
        $annotation = new FrontendModule(['category' => 'foobar']);

        $this->assertSame(['category' => 'foobar'], $annotation->getAttributes());
    }

    public function testReturnsAdditionalAttributes(): void
    {
        $annotation = new FrontendModule(['category' => 'foobar', 'foo' => 'bar']);

        $this->assertSame(['category' => 'foobar', 'foo' => 'bar'], $annotation->getAttributes());
    }
}
