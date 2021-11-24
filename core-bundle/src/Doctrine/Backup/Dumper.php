<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Doctrine\Backup;

use Contao\CoreBundle\Doctrine\Backup\Config\CreateConfig;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\BinaryType;

class Dumper implements DumperInterface
{
    public function dump(Connection $connection, CreateConfig $config): \Generator
    {
        try {
            yield from $this->doDump($connection, $config);
        } catch (\Exception $exception) {
            if ($exception instanceof BackupManagerException) {
                throw $exception;
            }

            throw new BackupManagerException($exception->getMessage(), 0, $exception);
        }
    }

    public function doDump(Connection $connection, CreateConfig $config): \Generator
    {
        yield 'SET FOREIGN_KEY_CHECKS = 0;';

        $schemaManager = $connection->createSchemaManager();
        $platform = $connection->getDatabasePlatform();

        foreach ($this->getTablesToDump($schemaManager, $config) as $table) {
            yield from $this->dumpSchema($platform, $table);
            yield from $this->dumpData($connection, $table);
        }

        yield from $this->dumpViews($schemaManager, $platform);

        // Triggers are currently not supported (contributions welcome!)

        yield 'SET FOREIGN_KEY_CHECKS = 1;';
    }

    private function dumpViews(AbstractSchemaManager $schemaManager, AbstractPlatform $platform): \Generator
    {
        foreach ($schemaManager->listViews() as $view) {
            yield sprintf('-- BEGIN VIEW %s', $view->getName());
            yield $platform->getCreateViewSQL($view->getQuotedName($platform), $view->getSql()).';';
        }
    }

    private function dumpSchema(AbstractPlatform $platform, Table $table): \Generator
    {
        yield sprintf('-- BEGIN STRUCTURE %s', $table->getName());
        yield sprintf('DROP TABLE IF EXISTS `%s`;', $table->getName());

        foreach ($platform->getCreateTableSQL($table) as $statement) {
            yield $statement.';';
        }
    }

    private function dumpData(Connection $connection, Table $table): \Generator
    {
        yield sprintf('-- BEGIN DATA %s', $table->getName());
        $values = [];

        foreach ($table->getColumns() as $column) {
            if ($column->getType() instanceof BinaryType) {
                $values[] = sprintf('HEX(`%s`) AS `%s`', $column->getName(), $column->getName());
            } else {
                $values[] = sprintf('`%s` AS `%s`', $column->getName(), $column->getName());
            }
        }

        $rows = $connection->executeQuery(sprintf('SELECT %s FROM `%s`', implode(', ', $values), $table->getName()));

        foreach ($rows->fetchAllAssociative() as $row) {
            $insertColumns = [];
            $insertValues = [];

            foreach ($row as $columnName => $value) {
                $insertColumns[] = sprintf('`%s`', $columnName);
                $insertValues[] = $this->formatValueForDump($value, $table->getColumn($columnName), $connection);
            }

            yield sprintf(
                'INSERT INTO `%s` (%s) VALUES (%s);',
                $table->getName(),
                implode(', ', $insertColumns),
                implode(', ', $insertValues)
            );
        }
    }

    private function formatValueForDump($value, Column $column, Connection $connection): string
    {
        if (null === $value) {
            return 'NULL';
        }

        if ($column->getType() instanceof BinaryType) {
            return sprintf('UNHEX(%s)', $connection->quote($value));
        }

        return $connection->quote($value);
    }

    /**
     * @return array<Table>
     */
    private function getTablesToDump(AbstractSchemaManager $schemaManager, CreateConfig $config): array
    {
        $allTables = $schemaManager->listTables();
        $filteredTables = [];

        foreach ($allTables as $table) {
            if (\in_array($table->getName(), $config->getTablesToIgnore(), true)) {
                continue;
            }

            $filteredTables[] = $table;
        }

        return $filteredTables;
    }
}
