<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version600;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Index\IndexedColumn;
use Doctrine\DBAL\Schema\Index\IndexType;

class CronJobMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_cron_job'])) {
            return false;
        }

        $table = $schemaManager->introspectTableByUnquotedName('tl_cron_job');

        if (!$table->hasColumn('name') || !$table->hasIndex('name')) {
            return false;
        }

        $column = $table->getColumn('name');
        $index = $table->getIndex('name');

        return 'ascii_bin' !== $column->getCollation()
            && IndexType::UNIQUE !== $index->getType()
            && ['name'] === array_map(static fn (IndexedColumn $column) => $column->getColumnName()->getIdentifier()->getValue(), $index->getIndexedColumns());
    }

    public function run(): MigrationResult
    {
        $names = [];

        foreach ($this->connection->fetchAllAssociative('SELECT id, name FROM tl_cron_job') as $row) {
            $name = preg_replace_callback('/[^\x00-\x7F]+/', static fn (array $match): string => bin2hex($match[0]), $row['name']);

            if (\strlen($name) > 255) {
                return $this->createResult(false, \sprintf('The normalized name of cron job ID %s exceeds 255 characters. Shorten its name before running this migration.', $row['id']));
            }

            if ($name !== $row['name']) {
                $names[$row['id']] = $name;
            }
        }

        $this->connection->executeStatement('ALTER TABLE tl_cron_job DROP INDEX name');

        foreach ($names as $id => $name) {
            $this->connection->update('tl_cron_job', ['name' => $name], ['id' => $id]);
        }

        $this->connection->executeStatement('ALTER TABLE tl_cron_job MODIFY name VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL');

        $this->connection->executeStatement(<<<'SQL'
            DELETE older FROM tl_cron_job older
            INNER JOIN tl_cron_job newer ON older.name = newer.name
            WHERE older.lastRun < newer.lastRun OR (older.lastRun = newer.lastRun AND older.id < newer.id)
            SQL,
        );

        $this->connection->executeStatement('ALTER TABLE tl_cron_job ADD UNIQUE INDEX name (name)');

        return $this->createResult(true);
    }
}
