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
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

class CallbackTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * @group legacy
     */
    public function testReturnsTheTagName(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.4: %s Use the #[AsCallback] attribute instead.');

        $annotation = new Callback();
        $annotation->table = 'tl_foobar';
        $annotation->target = 'foo.bar';

        $this->assertSame('contao.callback', $annotation->getName());
    }

    public function testReturnsTheArguments(): void
    {
        $annotation = new Callback();
        $annotation->table = 'tl_foobar';
        $annotation->target = 'foo.bar';
        $annotation->priority = 17;

        $this->assertSame(
            ['table' => 'tl_foobar', 'target' => 'foo.bar', 'priority' => 17],
            $annotation->getAttributes(),
        );
    }

    public function testDoesNotReturnThePriorityIfNotSet(): void
    {
        $annotation = new Callback();
        $annotation->table = 'tl_foobar';
        $annotation->target = 'foo.bar';

        $this->assertSame(['table' => 'tl_foobar', 'target' => 'foo.bar'], $annotation->getAttributes());
    }
}
