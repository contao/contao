<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version503;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class FrontendModulesMigration extends AbstractMigration
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ContaoFramework $framework,
    ) {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_user_group'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_user_group');

        return !isset($columns['frontendmodules']);
    }

    public function run(): MigrationResult
    {
        $this->framework->initialize();

        $this->connection->executeStatement('
            ALTER TABLE
                tl_user_group
            ADD
                frontendModules BLOB DEFAULT NULL
        ');

        $this->connection->executeStatement(
            'UPDATE tl_user_group SET frontendModules = :frontendModules',
            [
                'frontendModules' => serialize(array_keys(array_merge(...array_values($GLOBALS['FE_MOD'])))),
            ],
        );

        return $this->createResult(true);
    }
}
