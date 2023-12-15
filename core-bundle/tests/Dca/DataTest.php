<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Dca;

use Contao\CoreBundle\Dca\Data;
use Contao\CoreBundle\Dca\Observer\DataObserverInterface;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    public function testGetsDataViaDotNotation(): void
    {
        $source = [
            'foo' => [
                'bar' => 'baz',
            ],
        ];

        $data = new Data($source);

        $this->assertSame('baz', $data->get('foo.bar'));
        $this->assertNull($data->get('baz'));
    }

    public function testReturnsItsSourceArray(): void
    {
        $source = ['foo' => 'bar'];
        $data = new Data($source);

        $this->assertSame($source, $data->all());
    }

    public function testReturnsDataSubset(): void
    {
        $parent = new Data(['baz' => ['foo']]);
        $subset = $parent->getData('baz');

        $this->assertSame(['foo'], $subset->all());
    }

    public function testAllowsFallbackForUndefinedDataSubset(): void
    {
        $parent = new Data([]);
        $subset = $parent->getData('foo', ['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], $subset->all());
    }

    public function testThrowsExceptionForInvalidSubset(): void
    {
        $parent = new Data(['baz' => ['foo']]);

        $this->expectException(\LogicException::class);

        $parent->getData('bar');
    }

    public function testIsAwareOfItsPath(): void
    {
        $this->assertSame('', (new Data([], ''))->getPath());
        $this->assertSame('foo.bar', (new Data([], 'foo.bar'))->getPath());
    }

    public function testPassesSubpathToDataSubset(): void
    {
        $parent = new Data(['baz' => ['foo']], 'foo.bar');
        $subset = $parent->getData('baz');

        $this->assertSame('foo.bar.baz', $subset->getPath());
    }

    public function testIsAwareOfItsRootData(): void
    {
        $root = new Data();
        $subset = new Data();

        $subset->setRoot($root);

        $this->assertSame($root, $root->getRoot());
        $this->assertSame($root, $subset->getRoot());
    }

    public function testPassesRootToDataSubset(): void
    {
        $root = new Data();
        $parent = new Data(['baz' => []]);
        $parent->setRoot($root);

        $subset = $parent->getData('baz');

        $this->assertSame($root, $subset->getRoot());
    }

    public function testReturnsDataSubsetWithObservers(): void
    {
        $data = new Data(['foo' => ['bar' => 'baz']]);
        $observerA = $this->createMock(DataObserverInterface::class);
        $observerB = $this->createMock(DataObserverInterface::class);

        $data->attachReadObserver($observerA);
        $data->attachReadObserver($observerB);

        $part = $data->getData('foo');

        $observerA
            ->expects($this->once())
            ->method('update')
            ->with($part)
        ;

        $observerB
            ->expects($this->once())
            ->method('update')
            ->with($part)
        ;

        $part->get('bar');
    }

    // TODO: Add tests for writeObservers.
    // TODO: Add tests for attaching observers.
}
