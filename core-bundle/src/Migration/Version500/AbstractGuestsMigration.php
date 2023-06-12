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
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

/**
 * This class can be used as a base class for migrations in extensions for
 * migrating the "show to guests only" setting to the user group.
 *
 * @see GuestsMigration for an example how to use this base class.
 */
abstract class AbstractGuestsMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        foreach ($this->getTables() as $table) {
            if (!$schemaManager->tablesExist([$table])) {
                continue;
            }

            $columns = $schemaManager->listTableColumns($table);

            if (!isset($columns['guests'], $columns['groups'])) {
                continue;
            }

            $test = $this->connection->fetchOne("
                SELECT TRUE
                FROM $table
                WHERE `guests`='1' AND (`groups` IS NULL OR `groups` NOT LIKE '%\"-1\"%')
                LIMIT 1
            ");

            if (false !== $test) {
                return true;
            }
        }

        return false;
    }

    public function run(): MigrationResult
    {
        $schemaManager = $this->connection->createSchemaManager();

        foreach ($this->getTables() as $table) {
            if (!$schemaManager->tablesExist([$table])) {
                continue;
            }

            $columns = $schemaManager->listTableColumns($table);

            if (!isset($columns['guests'], $columns['groups'])) {
                continue;
            }

            $values = $this->connection->fetchAllKeyValue("
                SELECT id, `groups`
                FROM $table
                WHERE `guests`='1' AND (`groups` IS NULL OR `groups` NOT LIKE '%\"-1\"%')
                LIMIT 1
            ");

            foreach ($values as $id => $value) {
                $groups = StringUtil::deserialize($value, true);
                $groups[] = '-1';

                $data = [
                    'protected' => 1,
                    '`groups`' => serialize($groups),
                ];

                $this->connection->update($table, $data, ['id' => (int) $id]);
            }
        }

        return $this->createResult(true);
    }

    /**
     * Returns an array of table names.
     *
     * @return list<string>
     */
    abstract protected function getTables(): array;
}
