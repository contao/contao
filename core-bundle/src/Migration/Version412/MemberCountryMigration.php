<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version412;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class MemberCountryMigration extends AbstractMigration
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

        if (!$schemaManager->tablesExist(['tl_member'])) {
            return false;
        }

        $pageColumns = $schemaManager->listTableColumns('tl_member');

        if (!isset($pageColumns['country'])) {
            return false;
        }

        $count = $this->connection->fetchOne('
            SELECT
                COUNT(*)
            FROM
                tl_member
            WHERE
                LENGTH(country) = 2 AND BINARY country != BINARY UPPER(country)
        ');

        return $count > 0;
    }

    public function run(): MigrationResult
    {
        $this->connection->executeQuery('
            UPDATE
                tl_member
            SET
                country = UPPER(country)
            WHERE
                LENGTH(country) = 2 AND BINARY country != BINARY UPPER(country)
        ');

        return $this->createResult(true);
    }
}
