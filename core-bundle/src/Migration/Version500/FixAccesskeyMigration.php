<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version500;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\BooleanType;

/**
 * Removes '0' from tl_page.accesskey as this was accidentally introduced (see #5586)
 */
class FixAccesskeyMigration extends AbstractMigration
{
    public function __construct(private Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_page'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_page');

        if (!isset($columns['accesskey'])) {
            return false;
        }

        return $columns['accesskey']->getType() instanceof BooleanType;
    }

    public function run(): MigrationResult
    {
        $this->connection->executeQuery("ALTER TABLE tl_page CHANGE accesskey accesskey CHAR(1) DEFAULT '' NOT NULL");
        $this->connection->executeQuery("UPDATE tl_page SET accesskey = '' WHERE accesskey = '0'");

        return $this->createResult(true);
    }
}
