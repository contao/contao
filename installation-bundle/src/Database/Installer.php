<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\Database;

use Contao\CoreBundle\Config\ResourceFinder;
use Contao\DcaExtractor;
use Contao\SqlFileParser;
use Doctrine\DBAL\Connection;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Translation\Translator;

/**
 * Handles the database installation.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class Installer
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var ResourceFinder
     */
    private $finder;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var array
     */
    private $commands;

    /**
     * Constructor.
     *
     * @param Connection     $connection The database connection
     * @param ResourceFinder $finder     The Contao resource finder
     * @param Translator     $translator The translator object
     */
    public function __construct(Connection $connection, ResourceFinder $finder, Translator $translator)
    {
        $this->connection = $connection;
        $this->finder = $finder;
        $this->translator = $translator;
    }

    /**
     * Returns the commands as array.
     *
     * @return array The commands
     */
    public function getCommands()
    {
        if (null === $this->commands) {
            $this->compileCommands();
        }

        return $this->commands;
    }

    /**
     * Executes a command.
     *
     * @param string $hash The hash
     *
     * @throws \InvalidArgumentException If the hash is invalid
     */
    public function execCommand($hash)
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

        throw new \InvalidArgumentException('Invalid hash ' . $hash);
    }

    /**
     * Returns the current database structure.
     *
     * @return array An array of tables and fields
     */
    private function getFromDb()
    {
        $tables = $this->connection->fetchAll("SHOW TABLE STATUS LIKE 'tl_%'");

        if (empty($tables)) {
            return [];
        }

        $return = [];

        foreach ($tables as $table) {
            $sql = $this->connection
                ->getDatabasePlatform()
                ->getListTableColumnsSQL($table['Name'], $this->connection->getDatabase())
            ;

            $columns = $this->connection->fetchAll($sql);

            foreach ($columns as $column) {
                $field = [
                    'name' => $this->quote($column['Field']),
                    'type' => $column['Type'],
                ];

                if (isset($column['Collation']) && $column['Collation'] !== $table['Collation']) {
                    $field['collation'] = 'COLLATE ' . $column['Collation'];
                }

                $field['null'] = ('YES' === $column['Null']) ? 'NULL' : 'NOT NULL';

                if (!empty($column['Extra'])) {
                    $field['extra'] = $column['Extra'];
                }

                if (isset($column['Default'])) {
                    $field['default'] = 'default ' . $this->connection->quote($column['Default']);
                }

                $return[$table['Name']]['TABLE_FIELDS'][$column['Field']] = trim(implode(' ', $field));
            }

            $sql = $this->connection
                ->getDatabasePlatform()
                ->getListTableIndexesSQL($table['Name'], $this->connection->getDatabase())
            ;

            $tmp = [];
            $indexes = $this->connection->fetchAll($sql);

            foreach ($indexes as $index) {
                $name = $index['Key_name'];

                if (isset($tmp[$name])) {
                    $tmp[$name]['columns'][] = $this->quoteColumn($index);
                    continue;
                }

                if ('PRIMARY' === $name) {
                    $tmp[$name]['key'] = 'PRIMARY KEY ';
                } elseif ('0' === $index['Non_Unique']) {
                    $tmp[$name]['key'] = 'UNIQUE KEY ' . $this->quote($name);
                } else {
                    $tmp[$name]['key'] = 'KEY ' . $this->quote($name);
                }

                $tmp[$name]['columns'] = [$this->quoteColumn($index)];
            }

            foreach ($tmp as $name => $conf) {
                $return[$table['Name']]['TABLE_CREATE_DEFINITIONS'][$name] =
                    $conf['key'] . ' (' . implode(', ', $conf['columns']) . ')'
                ;
            }
        }

        return $return;
    }

    /**
     * Returns the target database structure from the DCA.
     *
     * @return array An array of tables and fields
     */
    private function getFromDca()
    {
        $return = [];
        $processed = [];

        /** @var SplFileInfo[] $files */
        $files = $this->finder->findIn('dca')->depth(0)->files()->name('*.php');

        foreach ($files as $file) {
            if (in_array($file->getBasename(), $processed)) {
                continue;
            }

            $processed[] = $file->getBasename();

            $table = $file->getBasename('.php');
            $extract = DcaExtractor::getInstance($table);

            if ($extract->isDbTable()) {
                $return[$table] = $extract->getDbInstallerArray();
            }
        }

        ksort($return);

        return $return;
    }

    /**
     * Returns the target database structure from the database.sql files.
     *
     * @return array An array of tables and fields
     */
    private function getFromFile()
    {
        $return = [];

        /** @var SplFileInfo[] $files */
        $files = $this->finder->findIn('config')->depth(0)->files()->name('database.sql');

        foreach ($files as $file) {
            $return = array_merge_recursive($return, SqlFileParser::parse($file));
        }

        ksort($return);

        return $return;
    }

    /**
     * Quotes an identifier.
     *
     * @param mixed $str The identifier
     *
     * @return mixed The quoted identifier
     */
    private function quote($str)
    {
        return $this->connection->quoteIdentifier($str);
    }

    /**
     * Quotes an index column.
     *
     * @param array $tableIndex The index configuration
     *
     * @return string The quoted index column
     */
    private function quoteColumn(array $tableIndex)
    {
        $column = $this->quote($tableIndex['Column_Name']);

        if (!empty($tableIndex['Sub_Part'])) {
            $column .= '(' . $tableIndex['Sub_Part'] . ')';
        }

        return $column;
    }

    /**
     * Compiles the command required to update the database.
     */
    private function compileCommands()
    {
        $create = [];
        $drop = [];
        $return = [];

        $sqlCurrent = $this->getFromDb();
        $sqlTarget = $this->getFromDca();
        $sqlLegacy = $this->getFromFile();

        // Manually merge the legacy definitions (see #4766)
        if (!empty($sqlLegacy)) {
            foreach ($sqlLegacy as $table => $categories) {
                foreach ($categories as $category => $fields) {
                    if (is_array($fields)) {
                        foreach ($fields as $name => $sql) {
                            $sqlTarget[$table][$category][$name] = $sql;
                        }
                    } else {
                        $sqlTarget[$table][$category] = $fields;
                    }
                }
            }
        }

        // Create tables
        foreach (array_diff(array_keys($sqlTarget), array_keys($sqlCurrent)) as $table) {
            $create[] = $table;
            $definitions = '';

            if (!empty($sqlTarget[$table]['TABLE_CREATE_DEFINITIONS'])) {
                $definitions = ",\n  " . implode(",\n  ", $sqlTarget[$table]['TABLE_CREATE_DEFINITIONS']);
            }

            $command = sprintf(
                "CREATE TABLE %s (\n  %s%s\n)%s;",
                $this->quote($table),
                implode(",\n  ", $sqlTarget[$table]['TABLE_FIELDS']),
                $definitions,
                $sqlTarget[$table]['TABLE_OPTIONS']
            );

            $return['CREATE'][md5($command)] = $command;
        }

        // Drop tables
        foreach (array_diff(array_keys($sqlCurrent), array_keys($sqlTarget)) as $table) {
            $drop[] = $table;
            $command = sprintf('DROP TABLE %s;', $this->quote($table));
            $return['DROP'][md5($command)] = $command;
        }

        // Add or change columns
        foreach ($sqlTarget as $table => $categories) {
            if (in_array($table, $create)) {
                continue;
            }

            if (is_array($categories['TABLE_FIELDS'])) {
                foreach ($categories['TABLE_FIELDS'] as $name => $sql) {
                    if (!isset($sqlCurrent[$table]['TABLE_FIELDS'][$name])) {
                        $command = sprintf(
                            'ALTER TABLE %s ADD %s;',
                            $this->quote($table),
                            $sql
                        );

                        $return['ALTER_ADD'][md5($command)] = $command;
                    } elseif ($sqlCurrent[$table]['TABLE_FIELDS'][$name] != $sql) {
                        $command = sprintf(
                            'ALTER TABLE %s CHANGE %s %s;',
                            $this->quote($table),
                            $this->quote($name),
                            $sql
                        );

                        $return['ALTER_CHANGE'][md5($command)] = $command;
                    }
                }
            }

            if (is_array($categories['TABLE_CREATE_DEFINITIONS'])) {
                foreach ($categories['TABLE_CREATE_DEFINITIONS'] as $name => $sql) {
                    if (!isset($sqlCurrent[$table]['TABLE_CREATE_DEFINITIONS'][$name])) {
                        $command = sprintf(
                            'ALTER TABLE %s ADD %s;',
                            $this->quote($table),
                            $sql
                        );

                        $return['ALTER_ADD'][md5($command)] = $command;
                    } elseif (
                        $sqlCurrent[$table]['TABLE_CREATE_DEFINITIONS'][$name] != str_replace('FULLTEXT ', '', $sql)
                    ) {
                        $command = sprintf(
                            'ALTER TABLE %s DROP INDEX %s, ADD %s;',
                            $this->quote($table),
                            $this->quote($name),
                            $sql
                        );

                        $return['ALTER_CHANGE'][md5($command)] = $command;
                    }
                }
            }

            // Move auto_increment fields to the end of the array
            if (is_array($return['ALTER_ADD'])) {
                foreach (preg_grep('/auto_increment/i', $return['ALTER_ADD']) as $key => $sql) {
                    unset($return['ALTER_ADD'][$key]);
                    $return['ALTER_ADD'][$key] = $sql;
                }
            }

            if (is_array($return['ALTER_CHANGE'])) {
                foreach (preg_grep('/auto_increment/i', $return['ALTER_CHANGE']) as $key => $sql) {
                    unset($return['ALTER_CHANGE'][$key]);
                    $return['ALTER_CHANGE'][$key] = $sql;
                }
            }
        }

        // Drop fields
        foreach ($sqlCurrent as $table => $categories) {
            if (!in_array($table, $drop)) {
                if (is_array($categories['TABLE_CREATE_DEFINITIONS'])) {
                    foreach ($categories['TABLE_CREATE_DEFINITIONS'] as $name => $sql) {
                        if (!isset($sqlTarget[$table]['TABLE_CREATE_DEFINITIONS'][$name])) {
                            $command = sprintf(
                                'ALTER TABLE %s DROP INDEX %s;',
                                $this->quote($table),
                                $this->quote($name)
                            );

                            $return['ALTER_DROP'][md5($command)] = $command;
                        }
                    }
                }

                if (is_array($categories['TABLE_FIELDS'])) {
                    foreach ($categories['TABLE_FIELDS'] as $name => $sql) {
                        if (!isset($sqlTarget[$table]['TABLE_FIELDS'][$name])) {
                            $command = sprintf(
                                'ALTER TABLE %s DROP %s;',
                                $this->quote($table),
                                $this->quote($name)
                            );

                            $return['ALTER_DROP'][md5($command)] = $command;
                        }
                    }
                }
            }
        }

        $this->commands = $return;
    }
}
