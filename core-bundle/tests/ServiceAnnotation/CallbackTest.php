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

class CallbackTest extends TestCase
{
    public function testReturnsTheTagName(): void
    {
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
