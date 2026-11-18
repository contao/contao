<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Migration\Version503;

use Contao\CoreBundle\Migration\Version503\UploaderMigration;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DropZone;
use Contao\FileUpload;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;

class UploaderMigrationTest extends TestCase
{
    public function testDoesNotRunIfTheTableDoesNotExist(): void
    {
        $migration = new UploaderMigration($this->mockConnection(false));

        $this->assertFalse($migration->shouldRun());
    }

    public function testDoesNotRunIfTheFieldDoesNotExist(): void
    {
        $migration = new UploaderMigration($this->mockConnection(true, []));

        $this->assertFalse($migration->shouldRun());
    }

    public function testDoesNotRunIfThereAreNoShortClassNames(): void
    {
        $migration = new UploaderMigration($this->mockConnection(true, ['uploader' => true], false));

        $this->assertFalse($migration->shouldRun());
    }

    public function testRunsIfThereAreShortClassNames(): void
    {
        $migration = new UploaderMigration($this->mockConnection(true, ['uploader' => true], 1));

        $this->assertTrue($migration->shouldRun());
    }

    public function testMigratesTheShortClassNames(): void
    {
        $updates = [];

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(2))
            ->method('update')
            ->willReturnCallback(
                static function (string $table, array $data, array $criteria) use (&$updates): int {
                    $updates[] = [$table, $data, $criteria];

                    return 1;
                },
            )
        ;

        $migration = new UploaderMigration($connection);

        $this->assertTrue($migration->run()->isSuccessful());

        $this->assertSame(
            [
                ['tl_user', ['uploader' => DropZone::class], ['uploader' => 'DropZone']],
                ['tl_user', ['uploader' => FileUpload::class], ['uploader' => 'FileUpload']],
            ],
            $updates,
        );
    }

    private function mockConnection(bool $tableExists, array|null $columns = null, false|int|null $result = null): Connection
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with(['tl_user'])
            ->willReturn($tableExists)
        ;

        $schemaManager
            ->expects(null === $columns ? $this->never() : $this->once())
            ->method('listTableColumns')
            ->with('tl_user')
            ->willReturn($columns ?? [])
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        $connection
            ->expects(null === $result ? $this->never() : $this->once())
            ->method('fetchOne')
            ->with("SELECT TRUE FROM tl_user WHERE uploader IN ('DropZone', 'FileUpload') LIMIT 1")
            ->willReturn($result ?? false)
        ;

        return $connection;
    }
}
