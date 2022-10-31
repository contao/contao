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
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

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
        $rows = $this->connection->fetchAllAssociative("
            SELECT
                id, webfonts, head
            FROM
                tl_layout
            WHERE
                webfonts != ''
        ");

        foreach ($rows as $row) {
            $this->connection->executeStatement(
                'UPDATE tl_layout SET head = :head WHERE id = :id',
                [
                    'id' => $row['id'],
                    'head' => $row['head']."\n".'<link rel="stylesheet" href="https://fonts.googleapis.com/css?family='.str_replace('|', '%7C', $row['webfonts']).'">',
                ]
            );
        }

        $this->connection->executeStatement('ALTER TABLE tl_layout DROP webfonts');

        return $this->createResult(true);
    }
}
