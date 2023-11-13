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
	 * @var string|null
	 */
	protected $strQuery;

	/**
	 * @var array
	 */
	private $arrSetParams = array();

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
		if (substr_count((string) $this->strQuery, '%s') !== 1 || !\in_array(strtoupper(substr($this->strQuery, 0, 6)), array('INSERT', 'UPDATE'), true))
		{
			throw new \InvalidArgumentException(sprintf('Using "%s()" is only supported for INSERT and UPDATE queries with the "%%s" placeholder.', __METHOD__));
		}

		$this->arrSetParams = array_values($arrParams);

		$arrParamNames = array_map(
			static function ($strName) {
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
		return $this->query('', array_merge($this->arrSetParams, \func_get_args()));
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
			static function ($varParam) use ($arrTypes) {
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

		// Execute the query
		$this->statement = $this->resConnection->executeQuery($this->strQuery, $arrParams, $arrTypes);

		// No result set available
		if ($this->statement->columnCount() < 1)
		{
			return $this;
		}

		// Instantiate a result object
		return new Result($this->statement, $this->strQuery);
	}
}
