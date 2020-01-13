<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version40900;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class CeAccessMigration extends AbstractMigration
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

        if (!$schemaManager->tablesExist(['tl_user_group'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_user_group');

        return !isset($columns['elements']);
    }

    public function run(): MigrationResult
    {
        $this->connection->query('
            ALTER TABLE
                tl_user_group
            ADD
                elements BLOB DEFAULT NULL,
            ADD
                fields BLOB DEFAULT NULL
        ');

        $stmt = $this->connection->prepare('
            UPDATE
                tl_user_group
            SET
                elements = :elements,
                fields = :fields
        ');

        $stmt->execute([
            ':elements' => serialize(array_keys(array_merge(...array_values($GLOBALS['TL_CTE'])))),
            ':fields' => serialize(array_keys($GLOBALS['TL_FFL'])),
        ]);

        return new MigrationResult(true, '');
    }
}
