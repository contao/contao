<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\Database;

use Contao\CoreBundle\Config\ResourceFinder;
use Contao\DcaExtractor;
use Contao\SqlFileParser;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Translation\TranslatorInterface;

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
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var array
     */
    private $commands;

    /**
     * Constructor.
     *
     * @param Connection          $connection The database connection
     * @param ResourceFinder      $finder     The Contao resource finder
     * @param TranslatorInterface $translator The translator object
     */
    public function __construct(Connection $connection, ResourceFinder $finder, TranslatorInterface $translator)
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

        throw new \InvalidArgumentException('Invalid hash '.$hash);
    }

    /**
     * Compiles the command required to update the database.
     */
    private function compileCommands()
    {
        $return = ['CREATE' => [], 'ALTER_CHANGE' => [], 'ALTER_ADD' => [], 'DROP' => [], 'ALTER_DROP' => []];
        $fromSchema = $this->connection->getSchemaManager()->createSchema();
        $toSchema = System::getContainer()->get('contao.doctrine.schema_provider')->createSchema();

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
                    $return['ALTER_ADD'][md5($sql)] = $sql;
                    break;

                case 0 === strpos($sql, 'DROP INDEX'):
                    $return['ALTER_CHANGE'][md5($sql)] = $sql;
                    break;

                case preg_match('/^(ALTER TABLE [^ ]+) /', $sql, $matches):
                    $prefix = $matches[1];
                    $sql = substr($sql, strlen($prefix));
                    $parts = array_map('trim', explode(',', $sql));

                    foreach ($parts as $part) {
                        $command = $prefix . ' ' . $part;

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
                                throw new \RuntimeException(sprintf('Unsupported SQL schema diff "%s"', $command));
                        }
                    }
                    break;

                default:
                    throw new \RuntimeException(sprintf('Unsupported SQL schema diff "%s"', $sql));
            }
        }

        $this->commands = $return;
    }
}
