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

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class FileExtensionMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection)
    {
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
