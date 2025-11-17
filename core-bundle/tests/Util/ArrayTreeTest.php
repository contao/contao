<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Util;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Util\ArrayTree;

class ArrayTreeTest extends TestCase
{
    public function testSetAndGetTree(): void
    {
        $tree = new ArrayTree();

        $tree->addContentNode('A');
        $tree->enterChildNode('B');
        $tree->addContentNode('B1');
        $tree->addContentNode('B2');
        $tree->up();
        $tree->enterChildNode();
        $tree->enterChildNode();
        $tree->addContentNode('C');
        $tree->up();
        $tree->up();
        $tree->addContentNode('D');

        $this->assertSame(
            [
                0 => 'A',
                'B' => [
                    'B1',
                    'B2',
                ],
                1 => [
                    ['C'],
                ],
                2 => 'D',
            ],
            $tree->toArray(),
            'return as array',
        );

        $dumpTreeAsString = static function (iterable $node) use (&$dumpTreeAsString): string {
            $return = '';

            foreach ($node as $key => $nodeOrValue) {
                if (is_iterable($nodeOrValue)) {
                    $return .= \sprintf(',%s:{%s}', $key, $dumpTreeAsString($nodeOrValue));
                } else {
                    $return .= \sprintf(',%s', $nodeOrValue);
                }
            }

            return substr($return, 1);
        };

        $this->assertSame('A,B:{B1,B2},1:{0:{C}},D', $dumpTreeAsString($tree), 'use as iterable');
    }

    public function testReturnsReferenceToSubTree(): void
    {
        $tree = new ArrayTree();

        $tree->enterChildNode('foo');
        $tree->addContentNode(1);

        $reference = $tree->current();

        $tree->enterChildNode('bar');
        $tree->addContentNode(2);
        $tree->up();
        $tree->addContentNode(3);
        $tree->up();
        $tree->addContentNode(4);

        $this->assertSame(
            [
                'foo' => [
                    1,
                    'bar' => [2],
                    3,
                ],
                4,
            ],
            $tree->toArray(),
            'full tree',
        );

        $this->assertSame(
            [
                1,
                'bar' => [2],
                3,
            ],
            $reference->toArray(),
            'sub tree',
        );
    }

    public function testAllowsEnteringAChildNodeMultipleTimes(): void
    {
        $tree = new ArrayTree();

        $tree->enterChildNode('foo');
        $tree->addContentNode(1);

        $tree->up();

        $tree->enterChildNode('foo');
        $tree->addContentNode(2);

        $this->assertSame(['foo' => [1, 2]], $tree->toArray());
    }

    public function testThrowsExceptionIfTryingToExceedTheRootLevel(): void
    {
        $tree = new ArrayTree();

        $tree->enterChildNode();
        $tree->up();

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Cannot go up - already at root level.');

        $tree->up();
    }
}
