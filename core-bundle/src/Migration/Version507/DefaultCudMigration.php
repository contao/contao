<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version507;

use Contao\CoreBundle\EventListener\DataContainer\CudPermissionListener;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class DefaultCudMigration extends AbstractMigration
{
    public function __construct(
        private readonly Connection $connection,
        private readonly CudPermissionListener $permissionListener,
    ) {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_user', 'tl_user_group'])) {
            return false;
        }

        return !\array_key_exists('cud', $schemaManager->listTableColumns('tl_user'))
            && !\array_key_exists('cud', $schemaManager->listTableColumns('tl_user_group'));
    }

    public function run(): MigrationResult
    {
        $this->connection->executeStatement('ALTER TABLE tl_user ADD COLUMN `cud` blob NULL');
        $this->connection->executeStatement('ALTER TABLE tl_user_group ADD COLUMN `cud` blob NULL');

        $default = serialize($this->getDefault());

        $this->connection->executeStatement("UPDATE tl_user SET cud=? WHERE inherit IN ('extend', 'custom') AND admin = false", [$default]);
        $this->connection->executeStatement('UPDATE tl_user_group SET cud=?', [$default]);

        return $this->createResult(true);
    }

    private function getDefault(): array
    {
        $default = [];
        $options = $this->permissionListener->getCudOptions();

        foreach ($options as $table => $operations) {
            foreach ($operations as $operation) {
                $default[] = $table.'::'.$operation;
            }
        }

        return $default;
    }
}
