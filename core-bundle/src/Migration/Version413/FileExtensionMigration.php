<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version413;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class FileExtensionMigration extends AbstractMigration
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_files'])) {
            return false;
        }

        return false !== $this->connection->fetchOne("SELECT * FROM tl_files WHERE CAST(extension AS BINARY) REGEXP BINARY '[[:upper:]]' LIMIT 1");
    }

    public function run(): MigrationResult
    {
        $this->connection->executeStatement("UPDATE tl_files SET extension = LOWER(extension) WHERE CAST(extension AS BINARY) REGEXP BINARY '[[:upper:]]'");

        return $this->createResult(true);
    }
}
