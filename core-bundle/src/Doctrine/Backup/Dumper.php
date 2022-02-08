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

    /**
     * @phpstan-param AbstractSchemaManager<AbstractPlatform> $schemaManager
     */
    private function dumpViews(AbstractSchemaManager $schemaManager, AbstractPlatform $platform): \Generator
    {
        foreach ($schemaManager->listViews() as $view) {
            yield sprintf('-- BEGIN VIEW %s', $view->getName());
            yield sprintf('CREATE OR REPLACE VIEW %s AS %s;', $view->getQuotedName($platform), $view->getSql());
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
            $values[] = sprintf('`%s` AS `%s`', $column->getName(), $column->getName());
        }

        $rows = $connection->executeQuery(sprintf('SELECT %s FROM `%s`', implode(', ', $values), $table->getName()));

        foreach ($rows->iterateAssociative() as $row) {
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

    /**
     * @param mixed $value
     */
    private function formatValueForDump($value, Column $column, Connection $connection): string
    {
        if (null === $value) {
            return 'NULL';
        }

        // non-ASCII values
        if (
            \is_string($value)
            && preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\xFF]/', $value)
            && (
                !\in_array(strtolower($column->getPlatformOptions()['charset'] ?? ''), ['utf8', 'utf8mb4'], true)
                || !preg_match('//u', $value)
            )
        ) {
            return '0x'.bin2hex($value);
        }

        return $connection->quote($value);
    }

    /**
     * @param AbstractSchemaManager<AbstractPlatform> $schemaManager
     *
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
