<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class KeepCanonicalMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_module'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_module');

        return !isset($columns['news_keepcanonical']);
    }

    public function run(): MigrationResult
    {
        $this->connection->executeStatement('
            ALTER TABLE tl_module
            ADD news_keepCanonical tinyint(1) NOT NULL default 0
        ');

        $this->connection->executeStatement("
            UPDATE tl_module
            SET news_keepCanonical = 1
            WHERE type = 'newsreader'
        ");

        return $this->createResult(true);
    }
}
