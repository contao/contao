<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Library
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao\Database\Doctrine;

use Doctrine\DBAL\Cache\ArrayStatement;


/**
 * Doctrine database statement class
 *
 * @author Tristan Lins <https://github.com/tristanlins>
 */
class Statement extends \Database\Statement
{
    /**
     * Connection ID
     *
     * @var \Doctrine\DBAL\Connection
     */
    protected $resConnection;

    /**
     * Connection ID
     *
     * @var \Doctrine\DBAL\Statement
     */
    protected $statement;

    /**
     * @var array
     */
    protected $parameters = array();

    /**
     * @param array $parameters
     *
     * @return array
     */
    public function prepareParameters(array $parameters)
    {
        return array_map(
            array($this, 'prepareParameter'),
            $parameters
        );
    }

    /**
     * @param array $parameters
     *
     * @return array
     */
    public function prepareParameter($parameter)
    {
        if (is_array($parameter) or is_object($parameter)) {
            $parameter = serialize($parameter);
        } else {
            if (is_bool($parameter)) {
                $parameter = $parameter ? '1' : '';
            }
        }
        return $parameter;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare($strQuery)
    {
        if (!strlen($strQuery)) {
            throw new \RuntimeException('Empty query string');
        }

        $this->resResult = null;
        $this->strQuery  = ltrim($strQuery);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function set($parameters)
    {
        $keys = array();
        foreach ($parameters as $key => $value) {
            $value = $this->prepareParameter($value);

            $key                = $this->resConnection->quoteIdentifier($key);
            $keys[$key]         = '?';
            $this->parameters[] = $value;
        }

        // INSERT
        if (strncasecmp($this->strQuery, 'INSERT', 6) === 0) {
            $strQuery = sprintf(
                '(%s) VALUES (%s)',
                implode(', ', array_keys($keys)),
                implode(', ', $keys)
            );
        } // UPDATE
        elseif (strncasecmp($this->strQuery, 'UPDATE', 6) === 0) {
            $arrSet = array();

            foreach ($keys as $key => $identifier) {
                $arrSet[] = $key . '=' . $identifier;
            }

            $strQuery = 'SET ' . implode(', ', $arrSet);
        } else {
            throw new \InvalidArgumentException('Cannot handle set on this query');
        }

        $this->strQuery = str_replace('%s', $strQuery, $this->strQuery);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $parameters = func_get_args();

        if (is_array($parameters[0])) {
            $parameters = array_values($parameters[0]);
        }
        $parameters = $this->prepareParameters($parameters);

        $this->parameters = array_values(
            array_merge(
                $this->parameters,
                $parameters
            )
        );

        $this->statement = $this->resConnection->executeQuery(
            $this->strQuery,
            $this->parameters,
            array(),
            $this->queryCacheProfile
        );

        if (!preg_match('#^(SELECT|SHOW)#iS', $this->strQuery)) {
            $this->debugQuery();
            return $this;
        }

        $result = new Result($this->statement, $this->strQuery);
        if (!$this->statement instanceof ArrayStatement) {
            $this->debugQuery($result);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function executeUncached()
    {
        $parameters = func_get_args();

        if (is_array($parameters[0])) {
            $parameters = array_values($parameters[0]);
        }
        $parameters = $this->prepareParameters($parameters);

        $this->parameters = array_values(
            array_merge(
                $this->parameters,
                $parameters
            )
        );

        $this->statement = $this->resConnection->executeQuery(
            $this->strQuery,
            $this->parameters
        );

        if (!preg_match('#^(SELECT|SHOW)#iS', $this->strQuery)) {
            $this->debugQuery();
            return $this;
        }

        $result = new Result($this->statement, $this->strQuery);
        $this->debugQuery($result);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function executeCached()
    {
        $parameters = func_get_args();

        if (is_array($parameters[0])) {
            $parameters = array_values($parameters[0]);
        }

        return $this->executeUncached($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function query($strQuery = '')
    {
        if (!empty($strQuery)) {
            $this->strQuery = ltrim($strQuery);
        }

        // Make sure there is a query string
        if ($this->strQuery == '') {
            throw new \RuntimeException('Empty query string');
        }

        return $this->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function prepare_query($strQuery)
    {
        throw new \RuntimeException('Not implemented yet');
    }

    /**
     * {@inheritdoc}
     */
    protected function string_escape($strString)
    {
        return $this->resConnection->quote($strString);
    }

    /**
     * {@inheritdoc}
     */
    protected function limit_query($intRows, $intOffset)
    {
        if (strncasecmp($this->strQuery, 'SELECT', 6) === 0) {
            $this->strQuery .= ' LIMIT ' . $intOffset . ',' . $intRows;
        } else {
            $this->strQuery .= ' LIMIT ' . $intRows;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute_query()
    {
        throw new \RuntimeException('Not implemented yet');
    }

    /**
     * {@inheritdoc}
     */
    protected function get_error()
    {
        $info = $this->statement->errorInfo();
        return 'SQLSTATE ' . $info[0] . ': error ' . $info[1] . ': ' . $info[2];
    }

    /**
     * {@inheritdoc}
     */
    protected function affected_rows()
    {
        return $this->statement->rowCount();
    }

    /**
     * {@inheritdoc}
     */
    protected function insert_id()
    {
        return $this->resConnection->lastInsertId();
    }

    /**
     * {@inheritdoc}
     */
    protected function explain_query()
    {
        return $this->resConnection
            ->executeQuery('EXPLAIN ' . $this->strQuery, $this->parameters)
            ->fetch();
    }

    /**
     * {@inheritdoc}
     */
    protected function createResult($resResult, $strQuery)
    {
        throw new \RuntimeException('Not implemented yet');
    }

    /**
     * {@inheritdoc}
     */
    protected function debugQuery($objResult=null)
    {
        return;
    }
}

// Backwards compatibility
class_alias('Contao\\Database\\Doctrine\\Statement', 'Database_Statement');
