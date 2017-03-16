<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\Database;

use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Handles the database installation.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Andreas Schempp <https://github.com/aschempp>
 */
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
     * Constructor.
     *
     * @param Connection          $connection
     * @param DcaSchemaProvider   $schemaProvider
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
     * @param string $hash
     *
     * @throws \InvalidArgumentException
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

        throw new \InvalidArgumentException(sprintf('Invalid hash: %s', $hash));
    }

    /**
     * Compiles the command required to update the database.
     */
    private function compileCommands()
    {
        $return = [
            'CREATE' => [],
            'ALTER_CHANGE' => [],
            'ALTER_ADD' => [],
            'DROP' => [],
            'ALTER_DROP' => [],
        ];

        $fromSchema = $this->connection->getSchemaManager()->createSchema();
        $toSchema = $this->schemaProvider->createSchema();

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
                    $sql = substr($sql, strlen($prefix));
                    $parts = array_reverse(array_map('trim', explode(',', $sql)));

                    for ($i=0, $count=count($parts); $i<$count; $i++) {
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
                                $parts[$i+1] = $parts[$i+1] . ',' . $part;
                                break;
                        }
                    }
                    break;

                default:
                    throw new \RuntimeException(sprintf('Unsupported SQL schema diff: %s', $sql));
            }
        }

        $this->commands = array_filter($return);
    }
}
