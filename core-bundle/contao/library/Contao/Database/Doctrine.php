<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\Database;

use Contao\Database;
use Contao\Database\Doctrine\Statement;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Doctrine database class
 *
 * @author Tristan Lins <https://github.com/tristanlins>
 */
class Doctrine extends Database
{
    /**
     * @var Connection
     */
    protected $resConnection;


    /**
     * {@inheritdoc}
     */
    protected function connect()
    {
        /** @var KernelInterface $kernel */
        global $kernel;

        $this->resConnection = $kernel->getContainer()->get('doctrine.dbal.default_connection');
    }


    /**
     * {@inheritdoc}
     */
    protected function disconnect()
    {
        unset($this->resConnection);
    }


    /**
     * {@inheritdoc}
     */
    public function listTables($databaseName = null, $noCache = false)
    {
        $schemaManager = $this->resConnection->getSchemaManager();
        $tableNames    = $schemaManager->listTableNames();

        return $tableNames;
    }


    /**
     * {@inheritdoc}
     */
    protected function get_error()
    {
        if ($this->resConnection) {
            $info = $this->resConnection->errorInfo();
            return 'SQLSTATE ' . $info[0] . ': error ' . $info[1] . ': ' . $info[2];
        } else {
            return new \RuntimeException('Not connected');
        }
    }


    /**
     * {@inheritdoc}
     */
    protected function find_in_set($strKey, $strSet, $blnIsField = false)
    {
        if ($blnIsField) {
            return "FIND_IN_SET(" . $this->resConnection->quoteIdentifier($strKey) . ", " . $strSet . ")";
        } else {
            return "FIND_IN_SET(" . $this->resConnection->quoteIdentifier($strKey) . ", " . $this->resConnection->quote(
                $strSet
            ) . ")";
        }
    }


    /**
     * {@inheritdoc}
     */
    protected function begin_transaction()
    {
        $this->resConnection->beginTransaction();
    }


    /**
     * {@inheritdoc}
     */
    protected function commit_transaction()
    {
        $this->resConnection->commit();
    }


    /**
     * {@inheritdoc}
     */
    protected function rollback_transaction()
    {
        $this->resConnection->rollBack();
    }


    /**
     * {@inheritdoc}
     */
    protected function list_fields($strTable)
    {
        $arrReturn = array();
        $arrFields = $this->query(sprintf('SHOW COLUMNS FROM `%s`', $strTable))->fetchAllAssoc();

        foreach ($arrFields as $k => $v) {
            $arrChunks = preg_split('/(\([^\)]+\))/', $v['Type'], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

            $arrReturn[$k]['name'] = $v['Field'];
            $arrReturn[$k]['type'] = $arrChunks[0];

            if (!empty($arrChunks[1])) {
                $arrChunks[1] = str_replace(array('(', ')'), array('', ''), $arrChunks[1]);
                $arrSubChunks = explode(',', $arrChunks[1]);

                $arrReturn[$k]['length'] = trim($arrSubChunks[0]);

                if (!empty($arrSubChunks[1])) {
                    $arrReturn[$k]['precision'] = trim($arrSubChunks[1]);
                }
            }

            if (!empty($arrChunks[2])) {
                $arrReturn[$k]['attributes'] = trim($arrChunks[2]);
            }

            if (!empty($v['Key'])) {
                switch ($v['Key']) {
                    case 'PRI':
                        $arrReturn[$k]['index'] = 'PRIMARY';
                        break;

                    case 'UNI':
                        $arrReturn[$k]['index'] = 'UNIQUE';
                        break;

                    case 'MUL':
                        // Ignore
                        break;

                    default:
                        $arrReturn[$k]['index'] = 'KEY';
                        break;
                }
            }

            $arrReturn[$k]['null']    = ($v['Null'] == 'YES') ? 'NULL' : 'NOT NULL';
            $arrReturn[$k]['default'] = $v['Default'];
            $arrReturn[$k]['extra']   = $v['Extra'];
        }

        $arrIndexes = $this->query("SHOW INDEXES FROM `$strTable`")->fetchAllAssoc();

        foreach ($arrIndexes as $arrIndex) {
            $arrReturn[$arrIndex['Key_name']]['name']           = $arrIndex['Key_name'];
            $arrReturn[$arrIndex['Key_name']]['type']           = 'index';
            $arrReturn[$arrIndex['Key_name']]['index_fields'][] = $arrIndex['Column_name'];
            $arrReturn[$arrIndex['Key_name']]['index']          = (($arrIndex['Non_unique'] == 0) ? 'UNIQUE' : 'KEY');
        }

        return $arrReturn;
    }


    /**
     * {@inheritdoc}
     */
    protected function set_database($strDatabase)
    {
        throw new \RuntimeException('Not implemented yet');
    }


    /**
     * {@inheritdoc}
     */
    protected function lock_tables($arrTables)
    {
        $arrLocks = array();

        foreach ($arrTables as $table => $mode) {
            $arrLocks[] = $this->resConnection->quoteIdentifier($table) . ' ' . $mode;
        }

        $this->resConnection->exec('LOCK TABLES ' . implode(', ', $arrLocks) . ';');
    }


    /**
     * {@inheritdoc}
     */
    protected function unlock_tables()
    {
        $this->resConnection->exec('UNLOCK TABLES;');
    }


    /**
     * {@inheritdoc}
     */
    protected function get_size_of($strTable)
    {
        $statement = $this->resConnection->executeQuery(
            'SHOW TABLE STATUS LIKE ' . $this->resConnection->quote($strTable)
        );
        $status    = $statement->fetch(\PDO::FETCH_ASSOC);

        return ($status['Data_length'] + $status['Index_length']);
    }


    /**
     * {@inheritdoc}
     */
    protected function get_next_id($strTable)
    {
        $statement = $this->resConnection->executeQuery(
            'SHOW TABLE STATUS LIKE ' . $this->resConnection->quote($strTable)
        );
        $status    = $statement->fetch(\PDO::FETCH_ASSOC);

        return $status['Auto_increment'];
    }


    /**
     * {@inheritdoc}
     */
    protected function get_uuid()
    {
        static $ids;

        if (empty($ids)) {
            $statement = $this->resConnection->executeQuery(
                implode(' UNION ALL ', array_fill(0, 10, "SELECT UNHEX(REPLACE(UUID(), '-', '')) AS uuid"))
            );

            $ids = $statement->fetchAll(\PDO::FETCH_COLUMN);
        }

        return array_pop($ids);
    }


    /**
     * {@inheritdoc}
     */
    protected function createStatement($connection, $blnDisableAutocommit)
    {
        $statement = new Statement($connection, $blnDisableAutocommit);
        return $statement;
    }
}
