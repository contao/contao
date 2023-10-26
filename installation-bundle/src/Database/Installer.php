<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\InstallationBundle\Database;

use Contao\CoreBundle\Doctrine\Schema\SchemaProvider;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;

class Installer
{
    private Connection $connection;
    private ?array $commands = null;
    private array $commandOrder;
    private SchemaProvider $schemaProvider;

    /**
     * @internal
     */
    public function __construct(Connection $connection, SchemaProvider $schemaProvider)
    {
        $this->connection = $connection;
        $this->schemaProvider = $schemaProvider;
    }

    /**
     * @return array<string>
     */
    public function getCommands(bool $byGroup = true): array
    {
        if (null === $this->commands) {
            $this->compileCommands();
        }

        if ($byGroup || !$this->commands) {
            return $this->commands;
        }

        $commandsByHash = array_merge(...array_values($this->commands));

        uksort(
            $commandsByHash,
            function ($a, $b) {
                $indexA = array_search($a, $this->commandOrder, true);
                $indexB = array_search($b, $this->commandOrder, true);

                if (false === $indexA) {
                    $indexA = \count($this->commandOrder);
                }

                if (false === $indexB) {
                    $indexB = \count($this->commandOrder);
                }

                return $indexA - $indexB;
            }
        );

        return $commandsByHash;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function execCommand(string $hash): void
    {
        if (null === $this->commands) {
            $this->compileCommands();
        }

        foreach ($this->commands as $commands) {
            if (isset($commands[$hash])) {
                $this->connection->executeStatement($commands[$hash]);

                return;
            }
        }

        throw new \InvalidArgumentException(sprintf('Invalid hash: %s', $hash));
    }

    /**
     * Compiles the command required to update the database.
     */
    public function compileCommands(): void
    {
        $return = [
            'CREATE' => [],
            'ALTER_TABLE' => [],
            'ALTER_CHANGE' => [],
            'ALTER_ADD' => [],
            'DROP' => [],
            'ALTER_DROP' => [],
        ];

        $order = [];

        // Create the from and to schema
        $schemaManager = $this->connection->createSchemaManager();
        $fromSchema = $schemaManager->createSchema();
        $toSchema = $this->schemaProvider->createSchema();

        $diff = $schemaManager
            ->createComparator()
            ->compareSchemas($fromSchema, $toSchema)
            ->toSql($this->connection->getDatabasePlatform())
        ;

        foreach ($diff as $sql) {
            switch (true) {
                case 0 === strncmp($sql, 'CREATE TABLE ', 13):
                    $return['CREATE'][md5($sql)] = $sql;
                    $order[] = md5($sql);
                    break;

                case 0 === strncmp($sql, 'DROP TABLE ', 11):
                    $return['DROP'][md5($sql)] = $sql;
                    $order[] = md5($sql);
                    break;

                case 0 === strncmp($sql, 'CREATE INDEX ', 13):
                case 0 === strncmp($sql, 'CREATE UNIQUE INDEX ', 20):
                case 0 === strncmp($sql, 'CREATE FULLTEXT INDEX ', 22):
                    $return['ALTER_ADD'][md5($sql)] = $sql;
                    $order[] = md5($sql);
                    break;

                case 0 === strncmp($sql, 'DROP INDEX', 10):
                    $return['ALTER_CHANGE'][md5($sql)] = $sql;
                    $order[] = md5($sql);
                    break;

                case preg_match('/^(ALTER TABLE [^ ]+) /', $sql, $matches):
                    $prefix = $matches[1];
                    $sql = substr($sql, \strlen($prefix));
                    $parts = array_reverse(array_map('trim', explode(',', $sql)));

                    for ($i = 0, $count = \count($parts); $i < $count; ++$i) {
                        $part = $parts[$i];
                        $command = $prefix.' '.$part;

                        switch (true) {
                            case 0 === strncmp($part, 'DROP ', 5):
                                $return['ALTER_DROP'][md5($command)] = $command;
                                $order[] = md5($command);
                                break;

                            case 0 === strncmp($part, 'ADD ', 4):
                                $return['ALTER_ADD'][md5($command)] = $command;
                                $order[] = md5($command);
                                break;

                            case 0 === strncmp($part, 'CHANGE ', 7):
                            case 0 === strncmp($part, 'RENAME ', 7):
                                $return['ALTER_CHANGE'][md5($command)] = $command;
                                $order[] = md5($command);
                                break;

                            default:
                                $parts[$i + 1] .= ','.$part;
                                break;
                        }
                    }
                    break;

                default:
                    throw new \RuntimeException(sprintf('Unsupported SQL schema diff: %s', $sql));
            }
        }

        $this->checkEngineAndCollation($return, $order, $fromSchema, $toSchema);

        $return = array_filter($return);

        // HOOK: allow third-party developers to modify the array (see #3281)
        if (isset($GLOBALS['TL_HOOKS']['sqlCompileCommands']) && \is_array($GLOBALS['TL_HOOKS']['sqlCompileCommands'])) {
            foreach ($GLOBALS['TL_HOOKS']['sqlCompileCommands'] as $callback) {
                $return = System::importStatic($callback[0])->{$callback[1]}($return);
            }
        }

        $this->commands = $return;
        $this->commandOrder = $order;
    }

    /**
     * Checks engine and collation and adds the ALTER TABLE queries.
     */
    private function checkEngineAndCollation(array &$sql, array &$order, Schema $fromSchema, Schema $toSchema): void
    {
        $tables = $toSchema->getTables();
        $dynamic = $this->hasDynamicRowFormat();

        foreach ($tables as $table) {
            $tableName = $table->getName();
            $alterTables = [];
            $deleteIndexes = false;

            if (0 !== strncmp($tableName, 'tl_', 3)) {
                continue;
            }

            $this->setLegacyOptions($table);

            $tableOptions = $this->connection->fetchAssociative(
                'SHOW TABLE STATUS WHERE Name = ? AND Engine IS NOT NULL AND Create_options IS NOT NULL AND Collation IS NOT NULL',
                [$tableName]
            );

            if (false === $tableOptions) {
                continue;
            }

            $engine = $table->getOption('engine');
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
                $alterTables[md5($command)] = $command;
            } elseif ($innodb && $dynamic) {
                if ('dynamic' !== strtolower($tableOptions['Row_format'])) {
                    $command = 'ALTER TABLE '.$tableName.' ENGINE = '.$engine.' ROW_FORMAT = DYNAMIC';

                    if (false !== stripos($tableOptions['Create_options'], 'key_block_size=')) {
                        $command .= ' KEY_BLOCK_SIZE = 0';
                    }

                    $alterTables[md5($command)] = $command;
                }
            }

            if ($table->hasOption('collation')) {
                $collate = $table->getOption('collation');
            } else {
                $collate = $table->getOption('collate');
            }

            if ($tableOptions['Collation'] !== $collate) {
                $charset = $table->getOption('charset');
                $command = 'ALTER TABLE '.$tableName.' CONVERT TO CHARACTER SET '.$charset.' COLLATE '.$collate;
                $deleteIndexes = true;
                $alterTables[md5($command)] = $command;
            }

            // Delete the indexes if the engine changes in case the existing
            // indexes are too long. The migration then needs to be run muliple
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

                    $indexCommand = $platform->getDropIndexSQL($indexName, $tableName);
                    $strKey = md5($indexCommand);

                    if (!isset($sql['ALTER_CHANGE'][$strKey])) {
                        $sql['ALTER_TABLE'][$strKey] = $indexCommand;
                        $order[] = $strKey;
                    }
                }
            }

            foreach ($alterTables as $k => $alterTable) {
                $sql['ALTER_TABLE'][$k] = $alterTable;
                $order[] = $k;
            }
        }
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

    /**
     * Adds the legacy table options to remain backwards compatibility with database.sql files.
     */
    private function setLegacyOptions(Table $table): void
    {
        if (!$table->hasOption('engine')) {
            $table->addOption('engine', 'MyISAM');
        }

        if (!$table->hasOption('charset')) {
            $table->addOption('charset', 'utf8');
        }

        if (!$table->hasOption('collate')) {
            $table->addOption('collate', 'utf8_general_ci');
        }
    }
}
