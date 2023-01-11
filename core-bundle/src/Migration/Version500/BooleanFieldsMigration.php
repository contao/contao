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
use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Converts empty string values of boolean fields to zeros.
 */
class BooleanFieldsMigration extends AbstractMigration
{
    public function __construct(private Connection $connection, private ContaoFramework $framework, private ResourceFinder $resourceFinder)
    {
    }

    public function shouldRun(): bool
    {
        foreach ($this->getTargets() as [$table, $column]) {
            $test = $this->connection->fetchOne("SELECT TRUE FROM $table WHERE `$column` = '' LIMIT 1");

            if (false !== $test) {
                return true;
            }
        }

        return false;
    }

    public function run(): MigrationResult
    {
        foreach ($this->getTargets() as [$table, $column]) {
            $this->connection->update($table, [$column => '0'], [$column => '']);
        }

        return $this->createResult(true);
    }

    private function getTargets(): array
    {
        $this->framework->initialize();

        $schemaManager = $this->connection->createSchemaManager();
        $targets = [];
        $processed = [];

        /** @var array<SplFileInfo> $files */
        $files = $this->resourceFinder->findIn('dca')->depth(0)->files()->name('*.php');

        foreach ($files as $file) {
            $tableName = $file->getBasename('.php');

            if (\in_array($tableName, $processed, true)) {
                continue;
            }

            $processed[] = $tableName;

            if (!$schemaManager->tablesExist([$tableName])) {
                continue;
            }

            $columns = $schemaManager->listTableColumns($tableName);

            Controller::loadDataContainer($tableName);

            foreach ($GLOBALS['TL_DCA'][$tableName]['fields'] ?? [] as $fieldName => $fieldConfig) {
                $fieldName = strtolower($fieldName);

                if (!isset($columns[$fieldName]) || Types::BOOLEAN !== ($fieldConfig['sql']['type'] ?? null)) {
                    continue;
                }

                $field = $columns[$fieldName];

                if ($field->getType() instanceof StringType) {
                    $targets[] = [$tableName, $fieldName];
                }
            }
        }

        return $targets;
    }
}
