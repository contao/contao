<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
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
	protected $resultSet;

	/**
	 * Current row index
	 * @var integer
	 */
	private $intIndex = -1;

	/**
	 * End indicator
	 * @var boolean
	 */
	private $blnDone = false;

	/**
	 * Modification indicator
	 * @var boolean
	 */
	private $blnModified = false;

	/**
	 * Result cache
	 * @var array
	 */
	protected $arrCache = array();


	/**
	 * Validate the connection resource and store the query string
	 *
	 * @param DoctrineStatement $statement The database statement
	 * @param string            $strQuery  The query string
     *
     * @todo Try to find a solution that works without fetchAll().
	 */
	public function __construct(DoctrineStatement $statement, $strQuery)
	{
		$this->resResult = $statement;
		$this->strQuery = $strQuery;
		$this->resultSet = $statement->fetchAll(\PDO::FETCH_ASSOC);
	}


	/**
	 * Automatically free the result
	 */
	public function __destruct()
	{
		$this->resultSet = null;
		$this->resResult->closeCursor();
	}


	/**
	 * Set a particular field of the current row
	 *
	 * @param mixed  $strKey   The field name
	 * @param string $varValue The field value
	 */
	public function __set($strKey, $varValue)
	{
		if (empty($this->arrCache))
		{
			$this->next();
		}

		$this->blnModified = true;
		$this->arrCache[$strKey] = $varValue;
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
		if (empty($this->arrCache))
		{
			$this->next();
		}

		return isset($this->arrCache[$strKey]);
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
				return $this->resResult->columnCount();
				break;

			case 'isModified':
				return $this->blnModified;
				break;

			default:
				if (empty($this->arrCache))
				{
					$this->next();
				}
				if (isset($this->arrCache[$strKey]))
				{
					return $this->arrCache[$strKey];
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
		if ($this->intIndex >= $this->count() - 1)
		{
			return false;
		}

		$this->arrCache = array_values($this->resultSet[++$this->intIndex]);

		return $this->arrCache;
	}


	/**
	 * Fetch the current row as associative array
	 *
	 * @return array|false The row as associative array or false if there is no row
	 */
	public function fetchAssoc()
	{
		if ($this->intIndex >= $this->count() - 1)
		{
			return false;
		}

		$this->arrCache = $this->resultSet[++$this->intIndex];

		return $this->arrCache;
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
	 * @return \Database\Result|boolean The result object or false if there is no first row
	 */
	public function first()
	{
		$this->intIndex = 0;

		$this->blnDone = false;
		$this->arrCache = $this->resultSet[$this->intIndex];

		return $this;
	}


	/**
	 * Go to the previous row of the current result
	 *
	 * @return \Database\Result|boolean The result object or false if there is no previous row
	 */
	public function prev()
	{
		if ($this->intIndex < 1)
		{
			return false;
		}

		$this->blnDone = false;
		$this->arrCache = $this->resultSet[--$this->intIndex];

		return $this;
	}


	/**
	 * Go to the next row of the current result
	 *
	 * @return \Database\Result|boolean The result object or false if there is no next row
	 */
	public function next()
	{
		if ($this->blnDone)
		{
			return false;
		}

		if (($arrRow = $this->fetchAssoc()) !== false)
		{
			return $this;
		}

		$this->blnDone = true;

		return false;
	}


	/**
	 * Go to the last row of the current result
	 *
	 * @return \Database\Result|boolean The result object or false if there is no last row
	 */
	public function last()
	{
		$this->intIndex = $this->count() - 1;

		$this->blnDone = true;
		$this->arrCache = $this->resultSet[$this->intIndex];

		return $this;
	}


	/**
	 * Return the number of rows in the result set
	 *
	 * @return integer The number of rows
	 */
	public function count()
	{
		return count($this->resultSet);
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
		if (empty($this->arrCache))
		{
			$this->next();
		}

		return $blnEnumerated ? array_values($this->arrCache) : $this->arrCache;
	}


	/**
	 * Reset the current result
	 *
	 * @return \Database\Result The result object
	 */
	public function reset()
	{
		$this->intIndex = -1;
		$this->blnDone = false;
		$this->arrCache = array();

		return $this;
	}
}
