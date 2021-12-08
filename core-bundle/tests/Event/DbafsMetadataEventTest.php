<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Event;

use Contao\CoreBundle\Event\DbafsMetadataEvent;
use Contao\CoreBundle\Tests\TestCase;

class DbafsMetadataEventTest extends TestCase
{
    public function testSetAndGetValues(): void
    {
        $rowData = [
            'uuid' => '12345',
            'path' => 'foo/bar',
            'baz' => 42,
        ];

        $event = new DbafsMetadataEvent('tl_files', $rowData);

        $this->assertSame('tl_files', $event->getTable());
        $this->assertSame('12345', $event->getUuid());
        $this->assertSame('foo/bar', $event->getPath());
        $this->assertSame($rowData, $event->getRow());

        $this->assertEmpty($event->getExtraMetadata());
        $event->set('baz-data', $event->getRow()['baz']);
        $this->assertSame(['baz-data' => 42], $event->getExtraMetadata());
    }

    /**
     * @dataProvider provideRowData
     */
    public function testEnforcesRequiredValues(array $row, string $expectedExceptionMessage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        new DbafsMetadataEvent('tl_foo', $row);
    }

    public function provideRowData(): \Generator
    {
        yield 'path missing' => [
            ['foo' => 'bar', 'uuid' => '12345'],
            "Row must contain key 'path'.",
        ];

        yield 'path has wrong type' => [
            ['path' => 123, 'uuid' => '12345'],
            "Row key 'path' must be of type string, got integer.",
        ];

        yield 'uuid missing' => [
            ['path' => 'foo/bar', 'baz' => 42],
            "Row must contain key 'uuid'.",
        ];

        yield 'uuid has wrong type' => [
            ['uuid' => new \stdClass(), 'path' => 'foo/bar'],
            "Row key 'uuid' must be of type string, got object.",
        ];
    }
}
