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
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ComparatorConfig;
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

    public function compileTargetSchema(bool $skipDropStatements = false): Schema
    {
        $schemaManager = $this->connection->createSchemaManager();
        $toSchema = $this->schemaProvider->createSchema();

        if ($skipDropStatements) {
            $this->copyMissingTablesAndColumns($schemaManager->introspectSchema(), $toSchema);
        }

        return $toSchema;
    }

    /**
     * @return list<string>
     */
    public function compileCommands(bool $skipDropStatements = false): array
    {
        $schemaManager = $this->connection->createSchemaManager();
        $toSchema = $this->schemaProvider->createSchema();
        $fromSchema = $schemaManager->introspectSchema();

        if ($skipDropStatements) {
            $this->copyMissingTablesAndColumns($fromSchema, $toSchema);
        }

        $comparator = $schemaManager->createComparator(new ComparatorConfig(false, false));

        // Get a list of SQL statements from the schema diff
        $schemaDiff = $comparator->compareSchemas($fromSchema, $toSchema);

        $diffCommands = $this->connection->getDatabasePlatform()->getAlterSchemaSQL($schemaDiff);

        // Get a list of SQL statements that adjust the engine and collation options
        $engineAndCollationCommands = $this->compileEngineAndCollationCommands($fromSchema, $toSchema);

        return array_unique([...$diffCommands, ...$engineAndCollationCommands]);
    }

    public function runAll(bool $skipDropStatements = false): void
    {
        $lastCommands = [];

        while (true) {
            $commands = $this->compileCommands($skipDropStatements);

            if ([] === array_diff($commands, $lastCommands)) {
                return;
            }

            $lastCommands = $commands;

            do {
                $commandExecuted = false;
                $lastException = null;

                foreach ($commands as $key => $command) {
                    try {
                        $this->executeSqlCommand($command);
                        $commandExecuted = true;
                        unset($commands[$key]);
                    } catch (\Throwable $e) {
                        $lastException = $e;
                    }
                }
            } while ($commandExecuted);

            if ($lastException) {
                throw $lastException;
            }
        }
    }

    public function executeSqlCommand(string $command): void
    {
        try {
            $this->connection->executeQuery($command);
        } catch (\Throwable $exception) {
            $this->fixFailedSqlCommand($command, $exception);
        }
    }

    /**
     * If tables or columns should be preserved, we copy the missing definitions over
     * to the $toSchema, so that no DROP commands will be issued in the diff.
     */
    private function copyMissingTablesAndColumns(Schema $fromSchema, Schema $toSchema): void
    {
        foreach ($fromSchema->getTables() as $table) {
            $tableName = $table->getObjectName()->getUnqualifiedName()->getValue();

            if (!$toSchema->hasTable($tableName)) {
                $this->copyTableDefinition($toSchema, $table);

                continue;
            }

            $toSchemaTable = $toSchema->getTable($tableName);

            foreach ($table->getColumns() as $column) {
                if (!$toSchemaTable->hasColumn($column->getObjectName()->getIdentifier()->getValue())) {
                    $this->copyColumnDefinition($toSchemaTable, $column);
                }
            }
        }
    }

    private function copyTableDefinition(Schema $targetSchema, Table $table): void
    {
        new \ReflectionClass(Schema::class)
            ->getMethod('_addTable')
            ->invoke($targetSchema, $table)
        ;
    }

    private function copyColumnDefinition(Table $targetTable, Column $column): void
    {
        new \ReflectionClass(Table::class)
            ->getMethod('_addColumn')
            ->invoke($targetTable, $column)
        ;
    }

    /**
     * MySQL can run into the error "1118 (42000): Row size too large" when adding or
     * deleting columns. In MariaDB since version 10.4.0 this can also happen if a
     * larger number of columns got deleted in the past because of the "Instant DROP
     * COLUMN" feature. If we encounter such an error, we retry the affected query
     * with the InnoDB strict mode disabled. Additionally, we optimize the table to
     * prevent future errors due to the "Instant DROP COLUMN" feature. This approach
     * involuntarily enables using too many or too large columns. To mitigate that,
     * the migrate command shows a warning in these cases.
     *
     * @see DatabaseMigrationChecks::compileSchemaWarnings()
     */
    private function fixFailedSqlCommand(string $command, \Throwable $exception): void
    {
        if (!$exception instanceof DriverException || 1118 !== $exception->getCode() || !str_contains($exception->getMessage(), 'Row size too large') || !str_starts_with($command, 'ALTER TABLE ') || 1 !== (int) $this->connection->fetchOne('SELECT @@innodb_strict_mode')) {
            throw $exception;
        }

        $this->connection->executeQuery('SET SESSION innodb_strict_mode = 0');

        try {
            $this->connection->executeQuery($command);
            $table = explode(' ', substr($command, 12), 2)[0];
            $this->connection->executeQuery("OPTIMIZE TABLE $table");
        } finally {
            $this->connection->executeQuery('SET SESSION innodb_strict_mode = 1');
        }
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
            $tableName = $table->getObjectName()->getUnqualifiedName()->getValue();
            $deleteIndexes = false;

            if (!str_starts_with($tableName, 'tl_')) {
                continue;
            }

            $tableOptions = $this->connection->fetchAssociative(
                'SHOW TABLE STATUS WHERE Name = ? AND Engine IS NOT NULL AND Create_options IS NOT NULL AND Collation IS NOT NULL',
                [$tableName],
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
            } elseif ($innodb && $dynamic && 'dynamic' !== strtolower($tableOptions['Row_format'])) {
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

            // Delete the indexes if the engine changes in case the existing indexes are too
            // long. The migration then needs to be run multiple times to re-create the
            // indexes with the correct length.
            if ($deleteIndexes) {
                if (!$fromSchema->hasTable($tableName)) {
                    continue;
                }

                $platform = $this->connection->getDatabasePlatform();

                foreach ($fromSchema->getTable($tableName)->getIndexes() as $index) {
                    $indexName = $index->getObjectName()->getIdentifier()->getValue();

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
