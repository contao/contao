<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Database;

use Doctrine\DBAL\Driver\Statement as DoctrineStatement;

/**
 * Lazy load the result set rows
 *
 * The class functions as a wrapper for the database result set and lazy loads
 * the result rows when they are first requested.
 *
 * Usage:
 *
 *     while ($result->next())
 *     {
 *         echo $result->name;
 *         print_r($result->row());
 *     }
 *
 * @property string  $query      The query string
 * @property integer $numRows    The number of rows in the result
 * @property integer $numFields  The number of fields in the result
 * @property boolean $isModified True if the result has been modified
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class Result
{
	/**
	 * Database result
	 * @var DoctrineStatement
	 */
	protected $resResult;

	/**
	 * Query string
	 * @var string
	 */
	protected $strQuery;

	/**
	 * Result set
	 * @var array
	 */
	protected $resultSet = array();

	/**
	 * Current row index
	 * @var integer
	 */
	private $intIndex = -1;

	/**
	 * Number of rows
	 * @var integer
	 */
	private $rowCount;

	/**
	 * Modified values of current row
	 * @var array
	 */
	private $arrModified = array();

	/**
	 * Validate the connection resource and store the query string
	 *
	 * @param DoctrineStatement $statement The database statement
	 * @param string            $strQuery  The query string
	 */
	public function __construct(DoctrineStatement $statement, $strQuery)
	{
		$this->resResult = $statement;
		$this->strQuery = $strQuery;
	}

	/**
	 * Automatically free the result
	 */
	public function __destruct()
	{
		if ($this->resResult)
		{
			$this->resResult->closeCursor();
		}
	}

	/**
	 * Set a particular field of the current row
	 *
	 * @param mixed  $strKey   The field name
	 * @param string $varValue The field value
	 */
	public function __set($strKey, $varValue)
	{
		if ($this->intIndex === -1)
		{
			$this->next();
		}

		$this->arrModified[$strKey] = $varValue;
	}

	/**
	 * Check whether a field exists
	 *
	 * @param mixed $strKey The field name
	 *
	 * @return boolean True if the field exists
	 */
	public function __isset($strKey)
	{
		if ($this->intIndex === -1)
		{
			$this->next();
		}

		return isset($this->resultSet[$this->intIndex][$strKey]) || isset($this->arrModified[$strKey]);
	}

	/**
	 * Return an object property or a field of the current row
	 *
	 * @param string $strKey The field name
	 *
	 * @return mixed|null The field value or null
	 */
	public function __get($strKey)
	{
		switch ($strKey)
		{
			case 'query':
				return $this->strQuery;
				break;

			case 'numRows':
				return $this->count();
				break;

			case 'numFields':
				if ($this->resResult)
				{
					return $this->resResult->columnCount();
				}

				if (isset($this->resultSet[0]))
				{
					return \count($this->resultSet[0]);
				}

				return 0;
				break;

			case 'isModified':
				return \count($this->arrModified) !== 0;
				break;

			default:
				if ($this->intIndex === -1)
				{
					$this->next();
				}

				if (isset($this->arrModified[$strKey]))
				{
					return $this->arrModified[$strKey];
				}

				if (isset($this->resultSet[$this->intIndex][$strKey]))
				{
					return $this->resultSet[$this->intIndex][$strKey];
				}
				break;
		}

		return null;
	}

	/**
	 * Fetch the current row as enumerated array
	 *
	 * @return array|false The row as enumerated array or false if there is no row
	 */
	public function fetchRow()
	{
		if ($row = $this->fetchAssoc())
		{
			return array_values($row);
		}

		return false;
	}

	/**
	 * Fetch the current row as associative array
	 *
	 * @return array|false The row as associative array or false if there is no row
	 */
	public function fetchAssoc()
	{
		$this->preload($this->intIndex + 1);

		if ($this->intIndex >= \count($this->resultSet) - 1)
		{
			return false;
		}

		$this->arrModified = array();

		return $this->resultSet[++$this->intIndex];
	}

	/**
	 * Fetch a particular field of each row of the result
	 *
	 * @param string $strKey The field name
	 *
	 * @return array An array of field values
	 */
	public function fetchEach($strKey)
	{
		$this->reset();
		$arrReturn = array();

		while (($arrRow = $this->fetchAssoc()) !== false)
		{
			if ($strKey != 'id' && isset($arrRow['id']))
			{
				$arrReturn[$arrRow['id']] = $arrRow[$strKey];
			}
			else
			{
				$arrReturn[] = $arrRow[$strKey];
			}
		}

		return $arrReturn;
	}

	/**
	 * Fetch all rows as associative array
	 *
	 * @return array An array with all rows
	 */
	public function fetchAllAssoc()
	{
		$this->reset();
		$arrReturn = array();

		while (($arrRow = $this->fetchAssoc()) !== false)
		{
			$arrReturn[] = $arrRow;
		}

		return $arrReturn;
	}

	/**
	 * Get the column information and return it as array
	 *
	 * @param integer $intOffset The field offset
	 *
	 * @return array An array with the column information
	 */
	public function fetchField($intOffset=0)
	{
		$arrFields = array_values($this->resultSet[$this->intIndex]);

		return $arrFields[$intOffset];
	}

	/**
	 * Go to the first row of the current result
	 *
	 * @return Result|boolean The result object or false if there is no first row
	 */
	public function first()
	{
		$this->intIndex = 0;
		$this->preload(0);

		$this->arrModified = array();

		return $this;
	}

	/**
	 * Go to the previous row of the current result
	 *
	 * @return Result|boolean The result object or false if there is no previous row
	 */
	public function prev()
	{
		if ($this->intIndex < 1)
		{
			return false;
		}

		$this->preload(--$this->intIndex);

		$this->arrModified = array();

		return $this;
	}

	/**
	 * Go to the next row of the current result
	 *
	 * @return Result|boolean The result object or false if there is no next row
	 */
	public function next()
	{
		if ($this->fetchAssoc() !== false)
		{
			return $this;
		}

		return false;
	}

	/**
	 * Go to the last row of the current result
	 *
	 * @return Result|boolean The result object or false if there is no last row
	 */
	public function last()
	{
		$this->intIndex = $this->count() - 1;

		$this->preload($this->intIndex);

		$this->arrModified = array();

		return $this;
	}

	/**
	 * Return the number of rows in the result set
	 *
	 * @return integer The number of rows
	 */
	public function count()
	{
		if ($this->rowCount === null)
		{
			$this->rowCount = $this->resResult->rowCount();

			// rowCount() might incorrectly return 0 for some platforms
			if ($this->rowCount < 1)
			{
				$this->preload(PHP_INT_MAX);
				$this->rowCount = \count($this->resultSet);
			}
		}

		return $this->rowCount;
	}

	/**
	 * Return the current row as associative array
	 *
	 * @param boolean $blnEnumerated If true, an enumerated array will be returned
	 *
	 * @return array The row as array
	 */
	public function row($blnEnumerated=false)
	{
		if ($this->intIndex === -1)
		{
			$this->next();
		}

		if (!$this->isModified)
		{
			return $blnEnumerated ? array_values($this->resultSet[$this->intIndex]) : $this->resultSet[$this->intIndex];
		}

		$row = array_merge($this->resultSet[$this->intIndex], $this->arrModified);

		return $blnEnumerated ? array_values($row) : $row;
	}

	/**
	 * Reset the current result
	 *
	 * @return Result The result object
	 */
	public function reset()
	{
		$this->intIndex = -1;
		$this->arrModified = array();

		return $this;
	}

	/**
	 * Preload all rows up to the specified index from the underlying statement
	 * and store them in the resultSet array.
	 *
	 * @param int $index
	 */
	private function preload($index)
	{
		// Optimize memory usage for single row results
		if ($index === 0 && $this->resResult && $this->resResult->rowCount() === 1)
		{
			++$index;
		}

		while ($this->resResult && \count($this->resultSet) <= $index)
		{
			$row = $this->resResult->fetch(\PDO::FETCH_ASSOC);

			if ($row === false)
			{
				$this->rowCount = \count($this->resultSet);
				$this->resResult->closeCursor();
				$this->resResult = null;
				break;
			}

			$this->resultSet[] = $row;
		}
	}
}

class_alias(Result::class, 'Database\Result');
