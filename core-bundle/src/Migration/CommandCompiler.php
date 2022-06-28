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
use Doctrine\DBAL\Schema\Schema;

class CommandCompiler
{
    /**
     * @internal Do not inherit from this class; decorate the "contao.migration.command_compiler" service instead
     */
    public function __construct(private readonly Connection $connection, private readonly SchemaProvider $schemaProvider)
    {
    }

    /**
     * @return list<string>
     */
    public function compileCommands(bool $doNotDropColumns = false): array
    {
        // Get a list of SQL commands from the schema diff
        $schemaManager = $this->connection->createSchemaManager();
        $fromSchema = $schemaManager->createSchema();
        $toSchema = $this->schemaProvider->createSchema();

        // If columns should not get dropped, we copy missing definitions
        // over to the $toSchema, so that they won't appear in the diff
        if ($doNotDropColumns) {
            foreach ($fromSchema->getTables() as $table) {
                $toSchemaTable = $toSchema->getTable($table->getName());

                foreach ($table->getColumns() as $column) {
                    if ($toSchemaTable->hasColumn($column->getName())) {
                        continue;
                    }

                    $options = [
                        'autoincrement' => $column->getAutoincrement(),
                        'columnDefinition' => $column->getColumnDefinition(),
                        'comment' => $column->getComment(),
                        'customSchemaOptions' => $column->getCustomSchemaOptions(),
                        'default' => $column->getDefault(),
                        'fixed' => $column->getFixed(),
                        'length' => $column->getLength(),
                        'notnull' => $column->getNotnull(),
                        'platformOptions' => $column->getPlatformOptions(),
                        'precision' => $column->getPrecision(),
                        'scale' => $column->getScale(),
                        'type' => $column->getType(),
                        'unsigned' => $column->getUnsigned(),
                    ];

                    $toSchemaTable->addColumn($column->getName(), $column->getType()->getName(), $options);
                }
            }
        }

        $diffCommands = $schemaManager
            ->createComparator()
            ->compareSchemas($fromSchema, $toSchema)
            ->toSql($this->connection->getDatabasePlatform())
        ;

        // Get a list of SQL commands that adjust the engine and collation options
        $engineAndCollationCommands = $this->compileEngineAndCollationCommands($fromSchema, $toSchema);

        return array_unique([...$diffCommands, ...$engineAndCollationCommands]);
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
            } elseif ($innodb && $dynamic) {
                if (false === stripos($tableOptions['Create_options'], 'row_format=dynamic')) {
                    $command = 'ALTER TABLE '.$tableName.' ENGINE = '.$engine.' ROW_FORMAT = DYNAMIC';

                    if (false !== stripos($tableOptions['Create_options'], 'key_block_size=')) {
                        $command .= ' KEY_BLOCK_SIZE = 0';
                    }

                    $commands[] = $command;
                }
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
