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
    private $commands = [];

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
     * Compiles the command required to update the database.
     *
     * @return array The commands
     */
    public function compileCommands()
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

        // HOOK: allow third-party developers to modify the array (see #3281)
        // FIXME: dispatch an event!
        #if (isset($GLOBALS['TL_HOOKS']['sqlCompileCommands']) && is_array($GLOBALS['TL_HOOKS']['sqlCompileCommands'])) {
        #    foreach ($GLOBALS['TL_HOOKS']['sqlCompileCommands'] as $callback) {
        #        $this->import($callback[0]);
        #        $return = $this->$callback[0]->$callback[1]($return);
        #    }
        #}

        $this->commands = $return;

        return $return;
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

        // HOOK: allow third-party developers to modify the array (see #3281)
        // FIXME: dispatch an event!
        #if (isset($GLOBALS['TL_HOOKS']['sqlGetFromDB']) && is_array($GLOBALS['TL_HOOKS']['sqlGetFromDB'])) {
        #    foreach ($GLOBALS['TL_HOOKS']['sqlGetFromDB'] as $callback) {
        #        $this->import($callback[0]);
        #        $return = $this->$callback[0]->$callback[1]($return);
        #    }
        #}

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

        // HOOK: allow third-party developers to modify the array (see #6425)
        // FIXME: dispatch an event!
        #if (isset($GLOBALS['TL_HOOKS']['sqlGetFromDca']) && is_array($GLOBALS['TL_HOOKS']['sqlGetFromDca'])) {
        #    foreach ($GLOBALS['TL_HOOKS']['sqlGetFromDca'] as $callback) {
        #        $this->import($callback[0]);
        #        $return = $this->$callback[0]->$callback[1]($return);
        #    }
        #}

        return $return;
    }

    /**
     * Returns the target database structure from the database.sql files.
     *
     * @return array An array of tables and fields
     */
    private function getFromFile()
    {
        $files = $this->finder->findIn('config')->depth(0)->files()->name('database.sql');

        if (0 === iterator_count($files)) {
            return [];
        }

        $table = '';
        $return = [];
        $keys = ['KEY', 'PRIMARY', 'PRIMARY KEY', 'FOREIGN', 'FOREIGN KEY', 'INDEX', 'UNIQUE', 'FULLTEXT', 'CHECK'];

        foreach ($files as $file) {
            $data = file($file);

            foreach ($data as $k => $v) {
                $keyName = [];
                $subpatterns = [];

                // Unset comments and empty lines
                if (preg_match('/^[#-]+/', $v) || !strlen(trim($v))) {
                    unset($data[$k]);
                    continue;
                }

                // Store the table names
                if (preg_match('/^CREATE TABLE `([^`]+)`/i', $v, $subpatterns)) {
                    $table = $subpatterns[1];
                }

                // Get the table options
                elseif ($table != '' && preg_match('/^\)([^;]+);/', $v, $subpatterns)) {
                    $return[$table]['TABLE_OPTIONS'] = $subpatterns[1];
                    $table = '';
                }

                // Add the fields
                elseif ($table != '') {
                    preg_match('/^[^`]*`([^`]+)`/', trim($v), $keyName);
                    $first = preg_replace('/\s[^\n\r]+/', '', $keyName[0]);
                    $key = $keyName[1];

                    // Create definitions
                    if (in_array($first, $keys)) {
                        if (0 === strncmp($first, 'PRIMARY', 7)) {
                            $key = 'PRIMARY';
                        }

                        $return[$table]['TABLE_CREATE_DEFINITIONS'][$key] = preg_replace('/,$/', '', trim($v));
                    } else {
                        $return[$table]['TABLE_FIELDS'][$key] = preg_replace('/,$/', '', trim($v));
                    }
                }
            }
        }

        // HOOK: allow third-party developers to modify the array (see #3281)
        // FIXME: dispatch an event!
        #if (isset($GLOBALS['TL_HOOKS']['sqlGetFromFile']) && is_array($GLOBALS['TL_HOOKS']['sqlGetFromFile'])) {
        #    foreach ($GLOBALS['TL_HOOKS']['sqlGetFromFile'] as $callback) {
        #        $this->import($callback[0]);
        #        $return = $this->$callback[0]->$callback[1]($return);
        #    }
        #}

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
}
