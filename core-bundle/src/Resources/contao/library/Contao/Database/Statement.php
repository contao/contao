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
use Doctrine\DBAL\Driver\Result as DoctrineResult;
use Doctrine\DBAL\Exception\DriverException;

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
	 * @var DoctrineResult
	 */
	protected $statement;

	/**
	 * Query string
	 * @var string
	 */
	protected $strQuery;

	/**
	 * @var array
	 */
	private $arrSetParams = array();

	/**
	 * @var array
	 */
	private $arrLastUsedParams = array();

	/**
	 * Validate the connection resource and store the query string
	 *
	 * @param Connection $resConnection The connection resource
	 */
	public function __construct(Connection $resConnection)
	{
		$this->resConnection = $resConnection;
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

			case 'affectedRows':
				return $this->statement->rowCount();

			case 'insertId':
				return $this->resConnection->lastInsertId();
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
		if (!$strQuery)
		{
			throw new \Exception('Empty query string');
		}

		$this->strQuery = trim($strQuery);
		$this->arrLastUsedParams = array();

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
		if (substr_count($this->strQuery, '%s') !== 1 || !\in_array(strtoupper(substr($this->strQuery, 0, 6)), array('INSERT', 'UPDATE'), true))
		{
			trigger_deprecation('contao/core-bundle', '4.13', 'Using "%s()" is only supported for INSERT and UPDATE queries with the "%%s" placeholder. This will throw an exception in Contao 5.0.', __METHOD__);

			return $this;
		}

		$this->arrSetParams = array_values($arrParams);

		$arrParamNames = array_map(
			static function ($strName)
			{
				if (!preg_match('/^(?:[A-Za-z0-9_$]+|`[^`]+`)$/', $strName))
				{
					throw new \RuntimeException(sprintf('Invalid column name "%s" in %s()', $strName, __METHOD__));
				}

				return Database::quoteIdentifier($strName);
			},
			array_keys($arrParams)
		);

		// INSERT
		if (strncasecmp($this->strQuery, 'INSERT', 6) === 0)
		{
			$strQuery = sprintf(
				'(%s) VALUES (%s)',
				implode(', ', $arrParamNames),
				implode(', ', array_fill(0, \count($arrParams), '?'))
			);
		}

		// UPDATE
		else
		{
			if (!$arrParamNames)
			{
				throw new \InvalidArgumentException('Set array must not be empty for UPDATE queries');
			}

			$strQuery = 'SET ' . implode('=?, ', $arrParamNames) . '=?';
		}

		$this->strQuery = str_replace('%s', $strQuery, $this->strQuery);

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

		if (\count($arrParams) === 1 && \is_array($arrParams[0]))
		{
			trigger_deprecation('contao/core-bundle', '4.13', 'Using "%s()" with an array parameter has been deprecated and will no longer work in Contao 5.0. Use argument unpacking via ... instead."', __METHOD__);

			$arrParams = array_values($arrParams[0]);
		}

		return $this->query('', array_merge($this->arrSetParams, $arrParams));
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
	public function query($strQuery='', array $arrParams = array(), array $arrTypes = array())
	{
		if (!empty($strQuery))
		{
			$this->strQuery = trim($strQuery);
		}

		// Make sure there is a query string
		if (!$this->strQuery)
		{
			throw new \Exception('Empty query string');
		}

		$arrParams = array_map(
			static function ($varParam) use ($arrTypes)
			{
				// Automatically cast boolean to integer when no types are defined, otherwise
				// PDO will convert "false" to an empty string (see https://bugs.php.net/bug.php?id=57157)
				if (empty($arrTypes) && \is_bool($varParam))
				{
					return (int) $varParam;
				}

				if (\is_string($varParam) || \is_bool($varParam) || \is_float($varParam) || \is_int($varParam) || $varParam === null)
				{
					return $varParam;
				}

				return serialize($varParam);
			},
			$arrParams
		);

		$this->arrLastUsedParams = $arrParams;

		// Execute the query
		// TODO: remove the try/catch block in Contao 5.0
		try
		{
			$this->statement = $this->resConnection->executeQuery($this->strQuery, $arrParams, $arrTypes);
		}
		catch (DriverException|\ArgumentCountError $exception)
		{
			// SQLSTATE[HY000]: This command is not supported in the prepared statement protocol
			if ($exception->getCode() === 1295)
			{
				$this->resConnection->executeStatement($this->strQuery, $arrParams, $arrTypes);

				trigger_deprecation('contao/core-bundle', '4.13', 'Using "%s()" for statements (instead of queries) has been deprecated and will no longer work in Contao 5.0. Use "%s::executeStatement()" instead.', __METHOD__, Connection::class);

				return $this;
			}

			if (!$arrParams)
			{
				throw $exception;
			}

			$intTokenCount = substr_count(preg_replace("/('[^']*')/", '', $this->strQuery), '?');

			if (\count($arrParams) <= $intTokenCount)
			{
				throw $exception;
			}

			// If we get here, there are more parameters than tokens, so we slice the array and try to execute the query again
			$this->statement = $this->resConnection->executeQuery($this->strQuery, \array_slice($arrParams, 0, $intTokenCount), $arrTypes);

			// Only trigger the deprecation if the parameter count was the reason for the exception and the previous call did not throw
			if ($this->arrLastUsedParams === array(null))
			{
				trigger_deprecation('contao/core-bundle', '4.13', 'Using "%s::execute(null)" has been deprecated and will no longer work in Contao 5.0. Omit the NULL parameters instead.', __CLASS__);
			}
			else
			{
				trigger_deprecation('contao/core-bundle', '4.13', 'Passing more parameters than "?" tokens has been deprecated and will no longer work in Contao 5.0. Use the correct number of parameters instead.');
			}
		}

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
	 *
	 * @deprecated Deprecated since Contao 4.13, to be removed in Contao 5.0.
	 */
	protected function replaceWildcards($arrValues)
	{
		trigger_deprecation('contao/core-bundle', '4.13', 'Using "%s()" has been deprecated and will no longer work in Contao 5.0.', __METHOD__);

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
	 *
	 * @deprecated Deprecated since Contao 4.13, to be removed in Contao 5.0.
	 */
	protected function escapeParams($arrValues)
	{
		trigger_deprecation('contao/core-bundle', '4.13', 'Using "%s()" has been deprecated and will no longer work in Contao 5.0.', __METHOD__);

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
	 *
	 * @deprecated Deprecated since Contao 4.13, to be removed in Contao 5.0.
	 */
	public function explain()
	{
		trigger_deprecation('contao/core-bundle', '4.13', 'Using "%s()" has been deprecated and will no longer work in Contao 5.0.', __METHOD__);

		return $this->resConnection->fetchAssociative('EXPLAIN ' . $this->strQuery, $this->arrLastUsedParams);
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
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\Statement::executeUncached()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Statement::execute()" instead.');

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
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\Statement::executeCached()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Statement::execute()" instead.');

		return \call_user_func_array(array($this, 'execute'), \func_get_args());
	}
}

class_alias(Statement::class, 'Database\Statement');
