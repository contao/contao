<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version409;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class CeAccessMigration extends AbstractMigration
{
    private Connection $connection;
    private ContaoFramework $framework;

    public function __construct(Connection $connection, ContaoFramework $framework)
    {
        $this->connection = $connection;
        $this->framework = $framework;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_user_group'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_user_group');

        return !isset($columns['elements']);
    }

    public function run(): MigrationResult
    {
        $this->framework->initialize();

        $this->connection->executeStatement('
            ALTER TABLE
                tl_user_group
            ADD
                elements BLOB DEFAULT NULL,
            ADD
                fields BLOB DEFAULT NULL
        ');

        $this->connection->executeStatement(
            'UPDATE tl_user_group SET elements = :elements, fields = :fields',
            [
                'elements' => serialize(array_keys(array_merge(...array_values($GLOBALS['TL_CTE'])))),
                'fields' => serialize(array_keys($GLOBALS['TL_FFL'])),
            ]
        );

        return $this->createResult(true);
    }
}
