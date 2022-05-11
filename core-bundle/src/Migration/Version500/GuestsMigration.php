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
 * @internal
 */
class GuestsMigration extends AbstractMigration
{
    private static array $tables = [
        'tl_article',
        'tl_content',
        'tl_module',
        'tl_page',
    ];

    public function __construct(private Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        foreach (self::$tables as $table) {
            if (!$schemaManager->tablesExist([$table]) || !isset($schemaManager->listTableColumns($table)['guests'])) {
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

        foreach (self::$tables as $table) {
            if (!$schemaManager->tablesExist([$table]) || !isset($schemaManager->listTableColumns($table)['guests'])) {
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
                    'protected' => '1',
                    'groups' => serialize($groups),
                ];

                $this->connection->update($table, $data, ['id' => (int) $id]);
            }
        }

        return $this->createResult(true);
    }
}
