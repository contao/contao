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

use Contao\Controller;
use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @internal
 */
class RelLightboxMigration extends AbstractMigration
{
    private Connection $connection;
    private ContaoFramework $framework;
    private ResourceFinder $resourceFinder;

    public function __construct(Connection $connection, ContaoFramework $framework, ResourceFinder $resourceFinder)
    {
        $this->connection = $connection;
        $this->framework = $framework;
        $this->resourceFinder = $resourceFinder;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        foreach ($this->getTargets() as [$table, $column]) {
            if (!$schemaManager->tablesExist([$table]) || !isset($schemaManager->listTableColumns($table)[$column])) {
                continue;
            }

            $test = $this->connection->fetchOne(
                "SELECT TRUE FROM $table WHERE `$column` REGEXP ' rel=\"lightbox(\\\\[([^\\\\]]+)\\\\])?\"' LIMIT 1;"
            );

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

            $values = $this->connection->fetchAllKeyValue(
                "SELECT id, `$column` FROM $table WHERE `$column` REGEXP ' rel=\"lightbox(\\\\[([^\\\\]]+)\\\\])?\"'"
            );

            foreach ($values as $id => $value) {
                $value = preg_replace('/ rel="lightbox(\[([^\]]+)\])?"/', ' data-lightbox="$2"', $value, -1, $count);

                if ($count) {
                    $this->connection->update($table, [$column => $value], ['id' => (int) $id]);
                }
            }
        }

        return $this->createResult(true);
    }

    private function getTargets(): array
    {
        $this->framework->initialize();

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

            Controller::loadDataContainer($tableName);

            foreach ($GLOBALS['TL_DCA'][$tableName]['fields'] ?? [] as $fieldName => $fieldConfig) {
                $rte = $fieldConfig['eval']['rte'] ?? null;

                if (\is_string($rte) && 0 === strpos($rte, 'tiny')) {
                    $targets[] = [$tableName, strtolower($fieldName)];
                }
            }
        }

        return $targets;
    }
}
