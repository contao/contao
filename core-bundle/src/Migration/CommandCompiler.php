<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration;

use Contao\CoreBundle\Doctrine\Schema\SchemaProvider;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;

class CommandCompiler
{
    /**
     * @internal
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly SchemaProvider $schemaProvider,
    ) {
    }

    /**
     * @return list<string>
     */
    public function compileCommands(bool $skipDropStatements = false): array
    {
        $schemaManager = $this->connection->createSchemaManager();
        $toSchema = $this->schemaProvider->createSchema();

        // Backwards compatibility with doctrine/dbal < 3.5
        if (method_exists($schemaManager, 'introspectSchema')) {
            $fromSchema = $schemaManager->introspectSchema();
        } else {
            $fromSchema = $schemaManager->createSchema();
        }

        // If tables or columns should be preserved, we copy the missing
        // definitions over to the $toSchema, so that no DROP commands
        // will be issued in the diff.
        if ($skipDropStatements) {
            foreach ($fromSchema->getTables() as $table) {
                if (!$toSchema->hasTable($table->getName())) {
                    $this->copyTableDefinition($toSchema, $table);

                    continue;
                }

                $toSchemaTable = $toSchema->getTable($table->getName());

                foreach ($table->getColumns() as $column) {
                    if (!$toSchemaTable->hasColumn($column->getName())) {
                        $this->copyColumnDefinition($toSchemaTable, $column);
                    }
                }
            }
        }

        // Get a list of SQL statements from the schema diff
        $diffCommands = $schemaManager
            ->createComparator()
            ->compareSchemas($fromSchema, $toSchema)
            ->toSql($this->connection->getDatabasePlatform())
        ;

        // Get a list of SQL statements that adjust the engine and collation options
        $engineAndCollationCommands = $this->compileEngineAndCollationCommands($fromSchema, $toSchema);

        return array_unique([...$diffCommands, ...$engineAndCollationCommands]);
    }

    private function copyTableDefinition(Schema $targetSchema, Table $table): void
    {
        (new \ReflectionClass(Schema::class))
            ->getMethod('_addTable')
            ->invoke($targetSchema, $table)
        ;
    }

    private function copyColumnDefinition(Table $targetTable, Column $column): void
    {
        (new \ReflectionClass(Table::class))
            ->getMethod('_addColumn')
            ->invoke($targetTable, $column)
        ;
    }

    /**
     * Checks engine and collation and adds the ALTER TABLE queries.
     *
     * @return list<string>
     */
    private function compileEngineAndCollationCommands(Schema $fromSchema, Schema $toSchema): array
    {
        $tables = $toSchema->getTables();
        $dynamic = $this->hasDynamicRowFormat();

        $commands = [];

        foreach ($tables as $table) {
            $tableName = $table->getName();
            $deleteIndexes = false;

            if (!str_starts_with($tableName, 'tl_')) {
                continue;
            }

            $tableOptions = $this->connection->fetchAssociative(
                'SHOW TABLE STATUS WHERE Name = ? AND Engine IS NOT NULL AND Create_options IS NOT NULL AND Collation IS NOT NULL',
                [$tableName]
            );

            if (false === $tableOptions) {
                continue;
            }

            $engine = $table->hasOption('engine') ? $table->getOption('engine') : '';
            $innodb = 'innodb' === strtolower($engine);

            if (strtolower($tableOptions['Engine']) !== strtolower($engine)) {
                if ($innodb && $dynamic) {
                    $command = 'ALTER TABLE '.$tableName.' ENGINE = '.$engine.' ROW_FORMAT = DYNAMIC';

                    if (false !== stripos($tableOptions['Create_options'], 'key_block_size=')) {
                        $command .= ' KEY_BLOCK_SIZE = 0';
                    }
                } else {
                    $command = 'ALTER TABLE '.$tableName.' ENGINE = '.$engine;
                }

                $deleteIndexes = true;
                $commands[] = $command;
            } elseif ($innodb && $dynamic && false === stripos($tableOptions['Create_options'], 'row_format=dynamic')) {
                $command = 'ALTER TABLE '.$tableName.' ENGINE = '.$engine.' ROW_FORMAT = DYNAMIC';

                if (false !== stripos($tableOptions['Create_options'], 'key_block_size=')) {
                    $command .= ' KEY_BLOCK_SIZE = 0';
                }

                $commands[] = $command;
            }

            $collate = '';
            $charset = $table->hasOption('charset') ? $table->getOption('charset') : '';

            if ($table->hasOption('collation')) {
                $collate = $table->getOption('collation');
            } elseif ($table->hasOption('collate')) {
                $collate = $table->getOption('collate');
            }

            if ($tableOptions['Collation'] !== $collate && '' !== $charset) {
                $command = 'ALTER TABLE '.$tableName.' CONVERT TO CHARACTER SET '.$charset.' COLLATE '.$collate;
                $deleteIndexes = true;
                $commands[] = $command;
            }

            // Delete the indexes if the engine changes in case the existing
            // indexes are too long. The migration then needs to be run multiple
            // times to re-create the indexes with the correct length.
            if ($deleteIndexes) {
                if (!$fromSchema->hasTable($tableName)) {
                    continue;
                }

                $platform = $this->connection->getDatabasePlatform();

                foreach ($fromSchema->getTable($tableName)->getIndexes() as $index) {
                    $indexName = $index->getName();

                    if ('primary' === strtolower($indexName)) {
                        continue;
                    }

                    $commands[] = $platform->getDropIndexSQL($indexName, $tableName);
                }
            }
        }

        return $commands;
    }

    private function hasDynamicRowFormat(): bool
    {
        $filePerTable = $this->connection->fetchAssociative("SHOW VARIABLES LIKE 'innodb_file_per_table'");

        // Dynamic rows require innodb_file_per_table to be enabled
        if (!\in_array(strtolower((string) $filePerTable['Value']), ['1', 'on'], true)) {
            return false;
        }

        $fileFormat = $this->connection->fetchAssociative("SHOW VARIABLES LIKE 'innodb_file_format'");

        // MySQL 8 and MariaDB 10.3 no longer have the "innodb_file_format" setting
        if (false === $fileFormat || '' === $fileFormat['Value']) {
            return true;
        }

        // Dynamic rows require the Barracuda file format in MySQL <8 and MariaDB <10.3
        return 'barracuda' === strtolower((string) $fileFormat['Value']);
    }
}
