<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Database;

use Contao\Database;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement as DoctrineStatement;

/**
 * Create and execute queries
 *
 * The class creates the database queries replacing the wildcards and escaping
 * the values. It then executes the query and returns a result object.
 *
 * Usage:
 *
 *     $db = Database::getInstance();
 *     $stmt = $db->prepare("SELECT * FROM tl_member WHERE city=?");
 *     $stmt->limit(10);
 *     $res = $stmt->execute('London');
 *
 * @property string  $query        The query string
 * @property string  $error        The last error message
 * @property integer $affectedRows The number of affected rows
 * @property integer $insertId     The last insert ID
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class Statement
{

	/**
	 * Connection ID
	 * @var Connection
	 */
	protected $resConnection;

	/**
	 * Database statement
	 * @var DoctrineStatement
	 */
	protected $statement;

	/**
	 * Query string
	 * @var string
	 */
	protected $strQuery;

	/**
	 * Autocommit indicator
	 * @var boolean
	 */
	protected $blnDisableAutocommit = false;

	/**
	 * Result cache
	 * @var array
	 */
	protected static $arrCache = array();

	/**
	 * Validate the connection resource and store the query string
	 *
	 * @param Connection $resConnection        The connection resource
	 * @param boolean    $blnDisableAutocommit Optionally disable autocommitting
	 */
	public function __construct(Connection $resConnection, $blnDisableAutocommit=false)
	{
		$this->resConnection = $resConnection;
		$this->blnDisableAutocommit = $blnDisableAutocommit;
	}

	/**
	 * Return an object property
	 *
	 * @param string $strKey The property name
	 *
	 * @return mixed|null The property value or null
	 */
	public function __get($strKey)
	{
		switch ($strKey)
		{
			case 'query':
				return $this->strQuery;
				break;

			case 'error':
				$info = $this->statement->errorInfo();

				return 'SQLSTATE ' . $info[0] . ': error ' . $info[1] . ': ' . $info[2];
				break;

			case 'affectedRows':
				return $this->statement->rowCount();
				break;

			case 'insertId':
				return $this->resConnection->lastInsertId();
				break;
		}

		return null;
	}

	/**
	 * Prepare a query string so the following functions can handle it
	 *
	 * @param string $strQuery The query string
	 *
	 * @return Statement The statement object
	 *
	 * @throws \Exception If $strQuery is empty
	 */
	public function prepare($strQuery)
	{
		if ($strQuery == '')
		{
			throw new \Exception('Empty query string');
		}

		$this->strQuery = trim($strQuery);

		// Auto-generate the SET/VALUES subpart
		if (strncasecmp($this->strQuery, 'INSERT', 6) === 0 || strncasecmp($this->strQuery, 'UPDATE', 6) === 0)
		{
			$this->strQuery = str_replace('%s', '%p', $this->strQuery);
		}

		// Replace wildcards
		$arrChunks = preg_split("/('[^']*')/", $this->strQuery, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);

		foreach ($arrChunks as $k=>$v)
		{
			if (substr($v, 0, 1) == "'")
			{
				continue;
			}

			$arrChunks[$k] = str_replace('?', '%s', $v);
		}

		$this->strQuery = implode('', $arrChunks);

		return $this;
	}

	/**
	 * Autogenerate the SET/VALUES subpart of a query from an associative array
	 *
	 * Usage:
	 *
	 *     $set = array(
	 *         'firstname' => 'Leo',
	 *         'lastname'  => 'Feyer'
	 *     );
	 *     $stmt->prepare("UPDATE tl_member %s")->set($set);
	 *
	 * @param array $arrParams The associative array
	 *
	 * @return Statement The statement object
	 */
	public function set($arrParams)
	{
		$strQuery = '';
		$arrParams = $this->escapeParams($arrParams);

		// INSERT
		if (strncasecmp($this->strQuery, 'INSERT', 6) === 0)
		{
			$strQuery = sprintf('(%s) VALUES (%s)',
								implode(', ', array_map('Database::quoteIdentifier', array_keys($arrParams))),
								str_replace('%', '%%', implode(', ', $arrParams)));
		}

		// UPDATE
		elseif (strncasecmp($this->strQuery, 'UPDATE', 6) === 0)
		{
			$arrSet = array();

			foreach ($arrParams as $k=>$v)
			{
				$arrSet[] = Database::quoteIdentifier($k) . '=' . $v;
			}

			$strQuery = 'SET ' . str_replace('%', '%%', implode(', ', $arrSet));
		}

		$this->strQuery = str_replace('%p', $strQuery, $this->strQuery);

		return $this;
	}

	/**
	 * Handle limit and offset
	 *
	 * @param integer $intRows   The maximum number of rows
	 * @param integer $intOffset The number of rows to skip
	 *
	 * @return Statement The statement object
	 */
	public function limit($intRows, $intOffset=0)
	{
		if ($intRows <= 0)
		{
			$intRows = 30;
		}

		if ($intOffset < 0)
		{
			$intOffset = 0;
		}

		if (strncasecmp($this->strQuery, 'SELECT', 6) === 0)
		{
			$this->strQuery .= ' LIMIT ' . $intOffset . ',' . $intRows;
		}
		else
		{
			$this->strQuery .= ' LIMIT ' . $intRows;
		}

		return $this;
	}

	/**
	 * Execute the query and return the result object
	 *
	 * @return Result The result object
	 */
	public function execute()
	{
		$arrParams = \func_get_args();

		if (!empty($arrParams) && \is_array($arrParams[0]))
		{
			$arrParams = array_values($arrParams[0]);
		}

		$this->replaceWildcards($arrParams);

		return $this->query();
	}

	/**
	 * Directly send a query string to the database
	 *
	 * @param string $strQuery The query string
	 *
	 * @return Result|Statement The result object or the statement object if there is no result set
	 *
	 * @throws \Exception If the query string is empty
	 */
	public function query($strQuery='')
	{
		if (!empty($strQuery))
		{
			$this->strQuery = trim($strQuery);
		}

		// Make sure there is a query string
		if ($this->strQuery == '')
		{
			throw new \Exception('Empty query string');
		}

		// Execute the query
		$this->statement = $this->resConnection->executeQuery($this->strQuery);

		// No result set available
		if ($this->statement->columnCount() < 1)
		{
			return $this;
		}

		// Instantiate a result object
		return new Result($this->statement, $this->strQuery);
	}

	/**
	 * Replace the wildcards in the query string
	 *
	 * @param array $arrValues The values array
	 *
	 * @throws \Exception If $arrValues has too few values to replace the wildcards in the query string
	 */
	protected function replaceWildcards($arrValues)
	{
		$arrValues = $this->escapeParams($arrValues);
		$this->strQuery = preg_replace('/(?<!%)%([^bcdufosxX%])/', '%%$1', $this->strQuery);

		// Replace wildcards
		if (!$this->strQuery = @vsprintf($this->strQuery, $arrValues))
		{
			throw new \Exception('Too few arguments to build the query string');
		}
	}

	/**
	 * Escape the values and serialize objects and arrays
	 *
	 * @param array $arrValues The values array
	 *
	 * @return array The array with the escaped values
	 */
	protected function escapeParams($arrValues)
	{
		foreach ($arrValues as $k=>$v)
		{
			switch (\gettype($v))
			{
				case 'string':
					$arrValues[$k] = $this->resConnection->quote($v);
					break;

				case 'boolean':
					$arrValues[$k] = ($v === true) ? 1 : 0;
					break;

				case 'object':
					$arrValues[$k] = $this->resConnection->quote(serialize($v));
					break;

				case 'array':
					$arrValues[$k] = $this->resConnection->quote(serialize($v));
					break;

				default:
					$arrValues[$k] = $v ?? 'NULL';
					break;
			}
		}

		return $arrValues;
	}

	/**
	 * Explain the current query
	 *
	 * @return string The explanation string
	 */
	public function explain()
	{
		return $this->resConnection->executeQuery('EXPLAIN ' . $this->strQuery)->fetch();
	}

	/**
	 * Bypass the cache and always execute the query
	 *
	 * @return Result The result object
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Statement::execute() instead.
	 */
	public function executeUncached()
	{
		@trigger_error('Using Statement::executeUncached() has been deprecated and will no longer work in Contao 5.0. Use Statement::execute() instead.', E_USER_DEPRECATED);

		return \call_user_func_array(array($this, 'execute'), \func_get_args());
	}

	/**
	 * Always execute the query and add or replace an existing cache entry
	 *
	 * @return Result The result object
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Statement::execute() instead.
	 */
	public function executeCached()
	{
		@trigger_error('Using Statement::executeCached() has been deprecated and will no longer work in Contao 5.0. Use Statement::execute() instead.', E_USER_DEPRECATED);

		return \call_user_func_array(array($this, 'execute'), \func_get_args());
	}
}

class_alias(Statement::class, 'Database\Statement');
