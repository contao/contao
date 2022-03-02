<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Filesystem\Dbafs;

use Contao\CoreBundle\Filesystem\Dbafs\RetrieveDbafsMetadataEvent;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Uid\Uuid;

class RetrieveDbafsMetadataEventTest extends TestCase
{
    public function testSetAndGetValues(): void
    {
        $uuid = Uuid::v1();

        $rowData = [
            'uuid' => $uuid->toBinary(),
            'path' => 'foo/bar',
            'baz' => 42,
        ];

        $event = new RetrieveDbafsMetadataEvent('tl_files', $rowData);

        $this->assertSame('tl_files', $event->getTable());
        $this->assertSame($uuid->toBinary(), $event->getUuid()->toBinary());
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

        new RetrieveDbafsMetadataEvent('tl_foo', $row);
    }

    public function provideRowData(): \Generator
    {
        yield 'path missing' => [
            ['foo' => 'bar', 'uuid' => '12345'],
            'Row must contain key "path".',
        ];

        yield 'path has wrong type' => [
            ['path' => 123, 'uuid' => '12345'],
            'Row key "path" must be of type string, got integer.',
        ];

        yield 'uuid missing' => [
            ['path' => 'foo/bar', 'baz' => 42],
            'Row must contain key "uuid".',
        ];

        yield 'uuid has wrong type' => [
            ['uuid' => new \stdClass(), 'path' => 'foo/bar'],
            'Row key "uuid" must be of type string, got object.',
        ];
    }
}
