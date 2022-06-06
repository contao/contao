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

use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\StringType;

/**
 * Converts empty string values of boolean fields to zeros.
 */
class BooleanFieldsMigration extends AbstractMigration
{
    public function __construct(private Connection $connection, private ContaoFramework $framework)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        foreach ($this->getTargets() as [$table, $column]) {
            if (!$schemaManager->tablesExist([$table]) || !isset($schemaManager->listTableColumns($table)[$column])) {
                continue;
            }

            $test = $this->connection->fetchOne("SELECT TRUE FROM $table WHERE `$column` = '' LIMIT 1;");

            if (false !== $test) {
                return true;
            }
        }

        return false;
    }

    public function run(): MigrationResult
    {
        $schemaManager = $this->connection->createSchemaManager();

        foreach ($this->getTargets() as [$table, $column]) {
            if (!$schemaManager->tablesExist([$table]) || !isset($schemaManager->listTableColumns($table)[$column])) {
                continue;
            }

            $this->connection->update($table, [$column => 0], [$column => '']);
        }

        return $this->createResult(true);
    }

    private function getTargets(): array
    {
        $this->framework->initialize();

        $schemaManager = $this->connection->createSchemaManager();
        $targets = [];

        foreach ($schemaManager->listTables() as $table) {
            $tableName = $table->getName();

            try {
                Controller::loadDataContainer($tableName);
            } catch (\Throwable) {
                continue;
            }

            foreach ($GLOBALS['TL_DCA'][$tableName]['fields'] ?? [] as $fieldName => $fieldConfig) {
                if (!\is_array($fieldConfig['sql'] ?? null) || 'boolean' !== ($fieldConfig['sql']['type'] ?? null)) {
                    continue;
                }

                $field = $table->getColumn(strtolower($fieldName));

                if ($field->getType() instanceof StringType) {
                    $targets[] = [$tableName, strtolower($fieldName)];
                }
            }
        }

        return $targets;
    }
}
