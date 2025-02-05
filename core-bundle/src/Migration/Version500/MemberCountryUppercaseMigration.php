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

/**
 * @internal
 */
class MemberCountryUppercaseMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_member'])) {
            return false;
        }

        if (!isset($schemaManager->listTableColumns('tl_member')['country'])) {
            return false;
        }

        $test = $this->connection->fetchOne('SELECT TRUE FROM tl_member WHERE CAST(country AS BINARY) != CAST(UPPER(country) AS BINARY) LIMIT 1');

        return false !== $test;
    }

    public function run(): MigrationResult
    {
        $this->connection->executeStatement('UPDATE tl_member SET country = UPPER(country) WHERE CAST(country AS BINARY) != CAST(UPPER(country) AS BINARY)');

        return $this->createResult(true);
    }
}
