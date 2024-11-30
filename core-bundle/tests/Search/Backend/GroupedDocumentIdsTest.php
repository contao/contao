<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Search\Backend;

use Contao\CoreBundle\Search\Backend\GroupedDocumentIds;
use PHPUnit\Framework\TestCase;

class GroupedDocumentIdsTest extends TestCase
{
    public function testConstructorWithValidInput(): void
    {
        $groupedDocumentIds = new GroupedDocumentIds(['foo' => ['bar', 'baz']]);

        $this->assertFalse($groupedDocumentIds->isEmpty());
        $this->assertSame(['foo'], $groupedDocumentIds->getTypes());
        $this->assertSame(['bar', 'baz'], $groupedDocumentIds->getDocumentIdsForType('foo'));
    }

    public function testConstructorWithInvalidInputThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new GroupedDocumentIds(['foo' => 'not-an-array']);
    }

    public function testIsEmpty(): void
    {
        $groupedDocumentIds = new GroupedDocumentIds();

        $this->assertTrue($groupedDocumentIds->isEmpty());
    }

    public function testHas(): void
    {
        $groupedDocumentIds = new GroupedDocumentIds(['foo' => ['bar']]);

        $this->assertTrue($groupedDocumentIds->has('foo', 'bar'));
        $this->assertFalse($groupedDocumentIds->has('foo', 'baz'));
        $this->assertFalse($groupedDocumentIds->has('type2', 'bar'));
    }

    public function testGetDocumentIdsForType(): void
    {
        $groupedDocumentIds = new GroupedDocumentIds(['foo' => ['bar', 'baz']]);

        $this->assertSame(['bar', 'baz'], $groupedDocumentIds->getDocumentIdsForType('foo'));
        $this->assertSame([], $groupedDocumentIds->getDocumentIdsForType('other'));
    }

    public function testGetTypes(): void
    {
        $groupedDocumentIds = new GroupedDocumentIds(['foo' => ['bar'], 'other' => ['baz']]);

        $this->assertSame(['foo', 'other'], $groupedDocumentIds->getTypes());
    }

    public function testAddIdToType(): void
    {
        $groupedDocumentIds = new GroupedDocumentIds();
        $groupedDocumentIds->addIdToType('foo', 'bar');

        $this->assertTrue($groupedDocumentIds->has('foo', 'bar'));
        $this->assertSame(['bar'], $groupedDocumentIds->getDocumentIdsForType('foo'));

        // Adding duplicate ID
        $groupedDocumentIds->addIdToType('foo', 'bar');
        $this->assertSame(['bar'], $groupedDocumentIds->getDocumentIdsForType('foo'));
    }

    public function testRemoveIdFromType(): void
    {
        $groupedDocumentIds = new GroupedDocumentIds(['foo' => ['bar', 'baz']]);
        $groupedDocumentIds->removeIdFromType('foo', 'bar');

        $this->assertFalse($groupedDocumentIds->has('foo', 'bar'));
        $this->assertSame(['baz'], $groupedDocumentIds->getDocumentIdsForType('foo'));

        // Removing the last ID should remove the type
        $groupedDocumentIds->removeIdFromType('foo', 'baz');
        $this->assertFalse($groupedDocumentIds->has('foo', 'baz'));
        $this->assertSame([], $groupedDocumentIds->getDocumentIdsForType('foo'));
        $this->assertFalse($groupedDocumentIds->has('foo', 'baz'));
    }

    public function testToArray(): void
    {
        $typeToIds = ['foo' => ['bar'], 'type2' => ['baz']];
        $groupedDocumentIds = new GroupedDocumentIds($typeToIds);

        $this->assertSame($typeToIds, $groupedDocumentIds->toArray());
    }

    public function testFromArray(): void
    {
        $typeToIds = ['foo' => ['bar'], 'type2' => ['baz']];
        $groupedDocumentIds = GroupedDocumentIds::fromArray($typeToIds);

        $this->assertInstanceOf(GroupedDocumentIds::class, $groupedDocumentIds);
        $this->assertSame($typeToIds, $groupedDocumentIds->toArray());
    }
}
