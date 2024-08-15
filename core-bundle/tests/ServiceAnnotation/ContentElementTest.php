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

use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Contao\CoreBundle\ServiceAnnotation\ContentElement;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

class ContentElementTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * @group legacy
     */
    public function testReturnsTheTagName(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.4: %s Use the #[AsContentElement] attribute instead.');

        $annotation = new ContentElement(['category' => 'foobar']);

        $this->assertSame(ContentElementReference::TAG_NAME, $annotation->getName());
    }

    public function testReturnsTheArguments(): void
    {
        $annotation = new ContentElement([
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
        $annotation = new ContentElement(['category' => 'foobar']);

        $this->assertSame(['category' => 'foobar'], $annotation->getAttributes());
    }

    public function testReturnsAdditionalAttributes(): void
    {
        $annotation = new ContentElement(['category' => 'foobar', 'foo' => 'bar']);

        $this->assertSame(['category' => 'foobar', 'foo' => 'bar'], $annotation->getAttributes());
    }
}
