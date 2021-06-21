<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version412;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class MergeGuestsOnlyMigration extends AbstractMigration
{
    private const TABLES = ['tl_article', 'tl_content', 'tl_module', 'tl_page'];

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function shouldRun(): bool
    {
        foreach (self::TABLES as $table) {
            if ($this->shouldRunTable($table)) {
                return true;
            }
        }

        return false;
    }

    public function run(): MigrationResult
    {
        foreach (self::TABLES as $table) {
            if ($this->shouldRunTable($table)) {
                $this->runTable($table);
            }
        }

        return $this->createResult(true);
    }

    public function shouldRunTable(string $table): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist($table)) {
            return false;
        }

        $columns = $schemaManager->listTableColumns($table);

        if (!isset($columns['guests'])) {
            return false;
        }

        return true;
    }

    public function runTable(string $table): void
    {
        $rows = $this->connection->fetchAllAssociative("
            SELECT
                id, IF(protected = '1', `groups`, NULL) AS `groups`
            FROM
                $table
            WHERE
                guests = '1'
        ");

        foreach ($rows as $row) {
            $groups = StringUtil::deserialize($row['groups'], true);
            array_unshift($groups, -1);

            $this->connection
                ->prepare("UPDATE $table SET protected = '1', `groups` = :groups WHERE id = :id")
                ->executeStatement([
                    ':groups' => serialize($groups),
                    ':id' => $row['id'],
                ])
            ;
        }

        $this->connection->executeStatement("ALTER TABLE $table DROP guests");
    }
}
