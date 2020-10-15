<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version411;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class MergeWebfontsMigration extends AbstractMigration
{
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
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist('tl_layout')) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_layout');

        if (!isset($columns['webfonts'])) {
            return false;
        }

        return true;
    }

    public function run(): MigrationResult
    {
        $tableQuoted = $this->connection->quoteIdentifier('tl_layout');
        $webfontsFieldQuoted = $this->connection->quoteIdentifier('webfonts');
        $headFieldQuoted = $this->connection->quoteIdentifier('head');

        $rows = $this->connection->fetchAllAssociative("
            SELECT
                id, $webfontsFieldQuoted, $headFieldQuoted
            FROM
                $tableQuoted
            WHERE
                $webfontsFieldQuoted != ''
        ");

        foreach ($rows as $row) {
            $this->connection
                ->prepare("UPDATE $tableQuoted SET $headFieldQuoted = :head WHERE id = :id")
                ->execute([
                    ':id' => $row['id'],
                    ':head' => $row['head']."\n".'<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=' . str_replace('|', '%7C', $row['webfonts']) . '">',
                ])
            ;
        }

        $this->connection->executeStatement("ALTER TABLE tl_layout DROP $webfontsFieldQuoted");

        return $this->createResult(true);
    }
}
