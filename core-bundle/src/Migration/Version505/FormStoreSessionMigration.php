<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version505;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class FormStoreSessionMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist('tl_form')) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_form');

        return !isset($columns['storesession']);
    }

    public function run(): MigrationResult
    {
        $this->connection->executeQuery('ALTER TABLE tl_form ADD storeSession tinyint(1) NOT NULL default 0');
        $this->connection->executeQuery('UPDATE tl_form SET storeSession = 1');

        return $this->createResult(true);
    }
}
