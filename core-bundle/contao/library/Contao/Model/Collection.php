<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Model;

use Contao\Database\Result;
use Contao\Model;

/**
 * The class handles traversing a set of models and lazy loads the database
 * result rows upon their first usage.
 *
 * @template T of Model
 */
class Collection implements \ArrayAccess, \Countable, \IteratorAggregate
{
	/**
	 * Table name
	 * @var string
	 */
	protected $strTable;

	/**
	 * Current index
	 * @var integer
	 */
	protected $intIndex = -1;

	/**
	 * Models
	 * @var T[]
	 */
	protected $arrModels = array();

	/**
	 * Create a new collection
	 *
	 * @param T[]    $arrModels An array of models
	 * @param string $strTable  The table name
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct(array $arrModels, $strTable)
	{
		$arrModels = array_values($arrModels);

		foreach ($arrModels as $objModel)
		{
			if (!$objModel instanceof Model)
			{
				throw new \InvalidArgumentException('Invalid type: ' . \gettype($objModel));
			}
		}

		$this->arrModels = $arrModels;
		$this->strTable  = $strTable;
	}

	/**
	 * Set an object property
	 *
	 * @param string $strKey   The property name
	 * @param mixed  $varValue The property value
	 */
	public function __set($strKey, $varValue)
	{
		if ($this->intIndex < 0)
		{
			$this->first();
		}

		$this->arrModels[$this->intIndex]->$strKey = $varValue;
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
		if ($this->intIndex < 0)
		{
			$this->first();
		}

		return $this->arrModels[$this->intIndex]->$strKey ?? null;
	}

	/**
	 * Check whether a property is set
	 *
	 * @param string $strKey The property name
	 *
	 * @return boolean True if the property is set
	 */
	public function __isset($strKey)
	{
		if ($this->intIndex < 0)
		{
			$this->first();
		}

		return isset($this->arrModels[$this->intIndex]->$strKey);
	}

	/**
	 * Create a new collection from a database result
	 *
	 * @param Result $objResult The database result object
	 * @param string $strTable  The table name
	 *
	 * @return static The model collection
	 */
	public static function createFromDbResult(Result $objResult, $strTable)
	{
		$arrModels = array();

		/** @var class-string<Model> $strClass */
		$strClass = Model::getClassFromTable($strTable);

		while ($objResult->next())
		{
			$objModel = Registry::getInstance()->fetch($strTable, $objResult->{$strClass::getPk()});

			if ($objModel !== null)
			{
				$objModel->mergeRow($objResult->row());
				$arrModels[] = $objModel;
			}
			else
			{
				$arrModels[] = new $strClass($objResult);
			}
		}

		return new static($arrModels, $strTable);
	}

	/**
	 * Return the current row as associative array
	 *
	 * @return array The current row as array
	 */
	public function row()
	{
		if ($this->intIndex < 0)
		{
			$this->first();
		}

		return $this->arrModels[$this->intIndex]->row();
	}

	/**
	 * Set the current row from an array
	 *
	 * @param array $arrData The row data as array
	 *
	 * @return static The model collection object
	 */
	public function setRow(array $arrData)
	{
		if ($this->intIndex < 0)
		{
			$this->first();
		}

		$this->arrModels[$this->intIndex]->setRow($arrData);

		return $this;
	}

	/**
	 * Save the current model
	 *
	 * @return static The model collection object
	 */
	public function save()
	{
		if ($this->intIndex < 0)
		{
			$this->first();
		}

		$this->arrModels[$this->intIndex]->save();

		return $this;
	}

	/**
	 * Delete the current model and return the number of affected rows
	 *
	 * @return integer The number of affected rows
	 */
	public function delete()
	{
		if ($this->intIndex < 0)
		{
			$this->first();
		}

		return $this->arrModels[$this->intIndex]->delete();
	}

	/**
	 * Return the models as array
	 *
	 * @return T[] An array of models
	 */
	public function getModels()
	{
		return $this->arrModels;
	}

	/**
	 * Lazy load related records
	 *
	 * @param string $strKey The property name
	 *
	 * @return Collection<T>|T The model or a model collection if there are multiple rows
	 */
	public function getRelated($strKey)
	{
		if ($this->intIndex < 0)
		{
			$this->first();
		}

		return $this->arrModels[$this->intIndex]->getRelated($strKey);
	}

	/**
	 * Return the number of rows in the result set
	 *
	 * @return integer The number of rows
	 */
	#[\ReturnTypeWillChange]
	public function count()
	{
		return \count($this->arrModels);
	}

	/**
	 * Go to the first row
	 *
	 * @return static The model collection object
	 */
	public function first()
	{
		$this->intIndex = 0;

		return $this;
	}

	/**
	 * Go to the previous row
	 *
	 * @return static|false The model collection object or false if there is no previous row
	 */
	public function prev()
	{
		if ($this->intIndex < 1)
		{
			return false;
		}

		--$this->intIndex;

		return $this;
	}

	/**
	 * Return the current model
	 *
	 * @return T The model object
	 */
	public function current()
	{
		if ($this->intIndex < 0)
		{
			$this->first();
		}

		return $this->arrModels[$this->intIndex];
	}

	/**
	 * Go to the next row
	 *
	 * @return static|false The model collection object or false if there is no next row
	 */
	public function next()
	{
		if (!isset($this->arrModels[$this->intIndex + 1]))
		{
			return false;
		}

		++$this->intIndex;

		return $this;
	}

	/**
	 * Go to the last row
	 *
	 * @return static The model collection object
	 */
	public function last()
	{
		$this->intIndex = \count($this->arrModels) - 1;

		return $this;
	}

	/**
	 * Reset the model
	 *
	 * @return static The model collection object
	 */
	public function reset()
	{
		$this->intIndex = -1;

		return $this;
	}

	/**
	 * Fetch a column of each row
	 *
	 * @param string $strKey The property name
	 *
	 * @return array An array with all property values
	 */
	public function fetchEach($strKey)
	{
		$this->reset();
		$return = array();

		while ($this->next())
		{
			$strPk = $this->current()->getPk();

			if ($strKey != 'id' && isset($this->$strPk))
			{
				$return[$this->$strPk] = $this->$strKey;
			}
			else
			{
				$return[] = $this->$strKey;
			}
		}

		return $return;
	}

	/**
	 * Fetch all columns of every row
	 *
	 * @return array An array with all rows and columns
	 */
	public function fetchAll()
	{
		$this->reset();
		$return = array();

		while ($this->next())
		{
			$return[] = $this->row();
		}

		return $return;
	}

	/**
	 * Check whether an offset exists
	 *
	 * @param integer $offset The offset
	 *
	 * @return boolean True if the offset exists
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists($offset)
	{
		return isset($this->arrModels[$offset]);
	}

	/**
	 * Retrieve a particular offset
	 *
	 * @param integer $offset The offset
	 *
	 * @return T|null The model or null
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($offset)
	{
		return $this->arrModels[$offset];
	}

	/**
	 * Set a particular offset
	 *
	 * @param integer $offset The offset
	 * @param mixed   $value  The value to set
	 *
	 * @throws \RuntimeException The collection is immutable
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet($offset, $value)
	{
		throw new \RuntimeException('This collection is immutable');
	}

	/**
	 * Unset a particular offset
	 *
	 * @param integer $offset The offset
	 *
	 * @throws \RuntimeException The collection is immutable
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset($offset)
	{
		throw new \RuntimeException('This collection is immutable');
	}

	/**
	 * Retrieve the iterator object
	 *
	 * @return \ArrayIterator The iterator object
	 */
	#[\ReturnTypeWillChange]
	public function getIterator()
	{
		return new \ArrayIterator($this->arrModels);
	}
}
