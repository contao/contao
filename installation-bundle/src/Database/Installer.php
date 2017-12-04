<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\Database;

use Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

class Installer
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var array
     */
    private $commands;

    /**
     * @var DcaSchemaProvider
     */
    private $schemaProvider;

    /**
     * @param Connection        $connection
     * @param DcaSchemaProvider $schemaProvider
     */
    public function __construct(Connection $connection, DcaSchemaProvider $schemaProvider)
    {
        $this->connection = $connection;
        $this->schemaProvider = $schemaProvider;
    }

    /**
     * Returns the commands as array.
     *
     * @return array
     */
    public function getCommands(): array
    {
        if (null === $this->commands) {
            $this->compileCommands();
        }

        return $this->commands;
    }

    /**
     * Executes a command.
     *
     * @param string $hash
     *
     * @throws \InvalidArgumentException
     */
    public function execCommand(string $hash): void
    {
        if (null === $this->commands) {
            $this->compileCommands();
        }

        foreach ($this->commands as $commands) {
            if (isset($commands[$hash])) {
                $this->connection->query($commands[$hash]);

                return;
            }
        }

        throw new \InvalidArgumentException(sprintf('Invalid hash: %s', $hash));
    }

    /**
     * Compiles the command required to update the database.
     */
    private function compileCommands(): void
    {
        $return = [
            'CREATE' => [],
            'ALTER_TABLE' => [],
            'ALTER_CHANGE' => [],
            'ALTER_ADD' => [],
            'DROP' => [],
            'ALTER_DROP' => [],
        ];

        $fromSchema = $this->dropNonContaoTables($this->connection->getSchemaManager()->createSchema());
        $toSchema = $this->dropNonContaoTables($this->schemaProvider->createSchema());
        $diff = $fromSchema->getMigrateToSql($toSchema, $this->connection->getDatabasePlatform());

        foreach ($diff as $sql) {
            switch (true) {
                case 0 === strpos($sql, 'CREATE TABLE '):
                    $return['CREATE'][md5($sql)] = $sql;
                    break;

                case 0 === strpos($sql, 'DROP TABLE '):
                    $return['DROP'][md5($sql)] = $sql;
                    break;

                case 0 === strpos($sql, 'CREATE INDEX '):
                case 0 === strpos($sql, 'CREATE UNIQUE INDEX '):
                case 0 === strpos($sql, 'CREATE FULLTEXT INDEX '):
                    $return['ALTER_ADD'][md5($sql)] = $sql;
                    break;

                case 0 === strpos($sql, 'DROP INDEX'):
                    $return['ALTER_CHANGE'][md5($sql)] = $sql;
                    break;

                case preg_match('/^(ALTER TABLE [^ ]+) /', $sql, $matches):
                    $prefix = $matches[1];
                    $sql = substr($sql, \strlen($prefix));
                    $parts = array_reverse(array_map('trim', explode(',', $sql)));

                    for ($i = 0, $count = \count($parts); $i < $count; ++$i) {
                        $part = $parts[$i];
                        $command = $prefix.' '.$part;

                        switch (true) {
                            case 0 === strpos($part, 'DROP '):
                                $return['ALTER_DROP'][md5($command)] = $command;
                                break;

                            case 0 === strpos($part, 'ADD '):
                                $return['ALTER_ADD'][md5($command)] = $command;
                                break;

                            case 0 === strpos($part, 'CHANGE '):
                            case 0 === strpos($part, 'RENAME '):
                                $return['ALTER_CHANGE'][md5($command)] = $command;
                                break;

                            default:
                                $parts[$i + 1] = $parts[$i + 1].','.$part;
                                break;
                        }
                    }
                    break;

                default:
                    throw new \RuntimeException(sprintf('Unsupported SQL schema diff: %s', $sql));
            }
        }

        $this->checkEngineAndCollation($return);

        $return = array_filter($return);

        // HOOK: allow third-party developers to modify the array (see #3281)
        if (isset($GLOBALS['TL_HOOKS']['sqlCompileCommands']) && \is_array($GLOBALS['TL_HOOKS']['sqlCompileCommands'])) {
            foreach ($GLOBALS['TL_HOOKS']['sqlCompileCommands'] as $callback) {
                $return = \System::importStatic($callback[0])->{$callback[1]}($return);
            }
        }

        $this->commands = $return;
    }

    /**
     * Removes tables from the schema that do not start with tl_.
     *
     * @param Schema $schema
     *
     * @return Schema
     */
    private function dropNonContaoTables(Schema $schema): Schema
    {
        $needle = $schema->getName().'.tl_';

        foreach ($schema->getTableNames() as $tableName) {
            if (0 !== stripos($tableName, $needle)) {
                $schema->dropTable($tableName);
            }
        }

        return $schema;
    }

    /**
     * Checks engine and collation and adds the ALTER TABLE queries.
     *
     * @param array $sql
     */
    private function checkEngineAndCollation(array &$sql): void
    {
        $params = $this->connection->getParams();
        $charset = $params['defaultTableOptions']['charset'];
        $collate = $params['defaultTableOptions']['collate'];
        $engine = $params['defaultTableOptions']['engine'];
        $tables = $this->connection->getSchemaManager()->listTableNames();

        foreach ($tables as $table) {
            if (0 !== strncmp($table, 'tl_', 3)) {
                continue;
            }

            $tableOptions = $this->connection
                ->query("SHOW TABLE STATUS LIKE '".$table."'")
                ->fetch(\PDO::FETCH_OBJ)
            ;

            if ($tableOptions->Engine !== $engine) {
                if ('InnoDB' === $engine) {
                    $command = 'ALTER TABLE '.$table.' ENGINE = '.$engine.' ROW_FORMAT = DYNAMIC';
                } else {
                    $command = 'ALTER TABLE '.$table.' ENGINE = '.$engine;
                }

                $sql['ALTER_TABLE'][md5($command)] = $command;
            }

            if ($tableOptions->Collation !== $collate) {
                $command = 'ALTER TABLE '.$table.' CONVERT TO CHARACTER SET '.$charset.' COLLATE '.$collate;

                $sql['ALTER_TABLE'][md5($command)] = $command;
            }
        }
    }
}
