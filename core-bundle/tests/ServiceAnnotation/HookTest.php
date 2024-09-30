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
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

class HookTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * @group legacy
     */
    public function testReturnsTheTagName(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.4: %s Use the #[AsHook] attribute instead.');

        $annotation = new Hook();

        $this->assertSame('contao.hook', $annotation->getName());
    }

    public function testReturnsTheArguments(): void
    {
        $annotation = new Hook();
        $annotation->value = 'foobar';
        $annotation->priority = 17;

        $this->assertSame(['hook' => 'foobar', 'priority' => 17], $annotation->getAttributes());
    }

    public function testDoesNotReturnThePriorityIfNotSet(): void
    {
        $annotation = new Hook();
        $annotation->value = 'foobar';

        $this->assertSame(['hook' => 'foobar'], $annotation->getAttributes());
    }
}
