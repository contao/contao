<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpKernel\KernelInterface;


/**
 * Handle the database communication
 *
 * The class is responsible for connecting to the database, listing tables and
 * fields, handling transactions and locking tables. It also creates the related
 * Database\Statement and Database\Result objects.
 *
 * Usage:
 *
 *     $db   = Database::getInstance();
 *     $stmt = $db->prepare("SELECT * FROM tl_user WHERE id=?");
 *     $res  = $stmt->execute(4);
 *
 * @property string $error The last error message
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class Database
{

	/**
	 * Object instances (Singleton)
	 * @var array
	 */
	protected static $arrInstances = array();

	/**
	 * Connection ID
	 * @var Connection
	 */
	protected $resConnection;

	/**
	 * Disable autocommit
	 * @var boolean
	 */
	protected $blnDisableAutocommit = false;

	/**
	 * Cache
	 * @var array
	 */
	protected $arrCache = array();


	/**
	 * Establish the database connection
	 *
	 * @param string $strConnection The connection ID
	 *
	 * @throws \Exception If a connection cannot be established
	 */
	protected function __construct($strConnection='doctrine.dbal.default_connection')
	{
		/** @var KernelInterface $kernel */
		global $kernel;

		$this->resConnection = $kernel->getContainer()->get($strConnection);

		if (!is_object($this->resConnection))
		{
			throw new \Exception(sprintf('Could not connect to database (%s)', $this->error));
		}
	}


	/**
	 * Close the database connection
	 */
	public function __destruct()
	{
		unset($this->resConnection);
	}


	/**
	 * Prevent cloning of the object (Singleton)
	 */
	final public function __clone() {}


	/**
	 * Return an object property
	 *
	 * @param string $strKey The property name
	 *
	 * @return string|null The property value
	 */
	public function __get($strKey)
	{
		if ($strKey == 'error')
		{
			$info = $this->resConnection->errorInfo();

			return 'SQLSTATE ' . $info[0] . ': error ' . $info[1] . ': ' . $info[2];
		}

		return null;
	}


	/**
	 * Instantiate the Database object (Factory)
	 *
	 * @param string $strConnection The connection ID
	 *
	 * @return \Database The Database object
	 */
	public static function getInstance($strConnection='doctrine.dbal.default_connection')
	{
		if (!isset(static::$arrInstances[$strConnection]))
		{
			static::$arrInstances[$strConnection] = new static($strConnection);
		}

		return static::$arrInstances[$strConnection];
	}


	/**
	 * Prepare a query and return a Database\Statement object
	 *
	 * @param string $strQuery The query string
	 *
	 * @return \Database\Statement The Database\Statement object
	 */
	public function prepare($strQuery)
	{
		$objStatement = new \Database\Statement($this->resConnection, $this->blnDisableAutocommit);

		return $objStatement->prepare($strQuery);
	}


	/**
	 * Execute a query and return a Database\Result object
	 *
	 * @param string $strQuery The query string
	 *
	 * @return \Database\Result|object The Database\Result object
	 */
	public function execute($strQuery)
	{
		return $this->prepare($strQuery)->execute();
	}


	/**
	 * Execute a raw query and return a Database\Result object
	 *
	 * @param string $strQuery The query string
	 *
	 * @return \Database\Result|object The Database\Result object
	 */
	public function query($strQuery)
	{
		$objStatement = new \Database\Statement($this->resConnection, $this->blnDisableAutocommit);

		return $objStatement->query($strQuery);
	}


	/**
	 * Auto-generate a FIND_IN_SET() statement
	 *
	 * @param string  $strKey     The field name
	 * @param mixed   $varSet     The set to find the key in
	 * @param boolean $blnIsField If true, the set will not be quoted
	 *
	 * @return string The FIND_IN_SET() statement
	 */
	public function findInSet($strKey, $varSet, $blnIsField=false)
	{
		if (is_array($varSet))
		{
			$varSet = implode(',', $varSet);
		}

		if (!$blnIsField)
		{
			$varSet = $this->resConnection->quote($varSet);
		}

		return "FIND_IN_SET(" . $this->resConnection->quoteIdentifier($strKey) . ", " . $varSet . ")";
	}


	/**
	 * Return all tables as array
	 *
	 * @param string  $strDatabase No longer used
	 * @param boolean $blnNoCache  If true, the cache will be bypassed
	 *
	 * @return array An array of table names
	 */
	public function listTables($strDatabase=null, $blnNoCache=false)
	{
		if ($blnNoCache || !isset($this->arrCache['listTables']))
		{
			$this->arrCache['listTables'] = $this->resConnection->getSchemaManager()->listTableNames();
		}

		return $this->arrCache['listTables'];
	}


	/**
	 * Determine if a particular database table exists
	 *
	 * @param string  $strTable    The table name
	 * @param string  $strDatabase The optional database name
	 * @param boolean $blnNoCache  If true, the cache will be bypassed
	 *
	 * @return boolean True if the table exists
	 */
	public function tableExists($strTable, $strDatabase=null, $blnNoCache=false)
	{
		if ($strTable == '')
		{
			return false;
		}

		return in_array($strTable, $this->listTables($strDatabase, $blnNoCache));
	}


	/**
	 * Return all columns of a particular table as array
	 *
	 * @param string  $strTable   The table name
	 * @param boolean $blnNoCache If true, the cache will be bypassed
	 *
	 * @return array An array of column names
	 */
	public function listFields($strTable, $blnNoCache=false)
	{
		if ($blnNoCache || !isset($this->arrCache[$strTable]))
		{
			$arrReturn = array();
			$objFields = $this->query("SHOW FULL COLUMNS FROM $strTable");

			while ($objFields->next())
			{
				$arrTmp = array();
				$arrChunks = preg_split('/(\([^\)]+\))/', $objFields->Type, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);

				$arrTmp['name'] = $objFields->Field;
				$arrTmp['type'] = $arrChunks[0];

				if (!empty($arrChunks[1]))
				{
					$arrChunks[1] = str_replace(array('(', ')'), '', $arrChunks[1]);

					// Handle enum fields (see #6387)
					if ($arrChunks[0] == 'enum')
					{
						$arrTmp['length'] = $arrChunks[1];
					}
					else
					{
						$arrSubChunks = explode(',', $arrChunks[1]);
						$arrTmp['length'] = trim($arrSubChunks[0]);

						if (!empty($arrSubChunks[1]))
						{
							$arrTmp['precision'] = trim($arrSubChunks[1]);
						}
					}
				}

				if (!empty($arrChunks[2]))
				{
					$arrTmp['attributes'] = trim($arrChunks[2]);
				}

				if ($objFields->Key != '')
				{
					switch ($objFields->Key)
					{
						case 'PRI':
							$arrTmp['index'] = 'PRIMARY';
							break;

						case 'UNI':
							$arrTmp['index'] = 'UNIQUE';
							break;

						case 'MUL':
							// Ignore
							break;

						default:
							$arrTmp['index'] = 'KEY';
							break;
					}
				}

				// Do not modify the order!
				$arrTmp['collation'] = $objFields->Collation;
				$arrTmp['null'] = ($objFields->Null == 'YES') ? 'NULL' : 'NOT NULL';
				$arrTmp['default'] = $objFields->Default;
				$arrTmp['extra'] = $objFields->Extra;
				$arrTmp['origtype'] = $objFields->Type;

				$arrReturn[] = $arrTmp;
			}

			$objIndex = $this->query("SHOW INDEXES FROM `$strTable`");

			while ($objIndex->next())
			{
				$arrReturn[$objIndex->Key_name]['name'] = $objIndex->Key_name;
				$arrReturn[$objIndex->Key_name]['type'] = 'index';
				$arrReturn[$objIndex->Key_name]['index_fields'][] = $objIndex->Column_name;
				$arrReturn[$objIndex->Key_name]['index'] = (($objIndex->Non_unique == 0) ? 'UNIQUE' : 'KEY');
			}

			$this->arrCache[$strTable] = $arrReturn;
		}

		return $this->arrCache[$strTable];
	}


	/**
	 * Determine if a particular column exists
	 *
	 * @param string  $strField   The field name
	 * @param string  $strTable   The table name
	 * @param boolean $blnNoCache If true, the cache will be bypassed
	 *
	 * @return boolean True if the field exists
	 */
	public function fieldExists($strField, $strTable, $blnNoCache=false)
	{
		if ($strField == '' || $strTable == '')
		{
			return false;
		}

		foreach ($this->listFields($strTable, $blnNoCache) as $arrField)
		{
			if ($arrField['name'] == $strField)
			{
				return true;
			}
		}

		return false;
	}


	/**
	 * Determine if a particular index exists
	 *
	 * @param string  $strName    The index name
	 * @param string  $strTable   The table name
	 * @param boolean $blnNoCache If true, the cache will be bypassed
	 *
	 * @return boolean True if the index exists
	 */
	public function indexExists($strName, $strTable, $blnNoCache=false)
	{
		if ($strName == '' || $strTable == '')
		{
			return false;
		}

		foreach ($this->listFields($strTable, $blnNoCache) as $arrField)
		{
			if ($arrField['name'] == $strName && $arrField['type'] == 'index')
			{
				return true;
			}
		}

		return false;
	}


	/**
	 * Return the field names of a particular table as array
	 *
	 * @param string  $strTable   The table name
	 * @param boolean $blnNoCache If true, the cache will be bypassed
	 *
	 * @return array An array of field names
	 */
	public function getFieldNames($strTable, $blnNoCache=false)
	{
		$arrNames = array();
		$arrFields = $this->listFields($strTable, $blnNoCache);

		foreach ($arrFields as $arrField)
		{
			$arrNames[] = $arrField['name'];
		}

		return $arrNames;
	}


	/**
	 * Check whether a field value in the database is unique
	 *
	 * @param string  $strTable The table name
	 * @param string  $strField The field name
	 * @param mixed   $varValue The field value
	 * @param integer $intId    The ID of a record to exempt
	 *
	 * @return boolean True if the field value is unique
	 */
	public function isUniqueValue($strTable, $strField, $varValue, $intId=null)
	{
		$strQuery = "SELECT * FROM $strTable WHERE $strField=?";

		if ($intId !== null)
		{
			$strQuery .= " AND id!=?";
		}

		$objUnique = $this->prepare($strQuery)
						  ->limit(1)
						  ->execute($varValue, $intId);

		return $objUnique->numRows ? false : true;
	}


	/**
	 * Return the IDs of all child records of a particular record (see #2475)
	 *
	 * @author Andreas Schempp
	 *
	 * @param mixed   $arrParentIds An array of parent IDs
	 * @param string  $strTable     The table name
	 * @param boolean $blnSorting   True if the table has a sorting field
	 * @param array   $arrReturn    The array to be returned
	 * @param string  $strWhere     Additional WHERE condition
	 *
	 * @return array An array of child record IDs
	 */
	public function getChildRecords($arrParentIds, $strTable, $blnSorting=false, $arrReturn=array(), $strWhere='')
	{
		if (!is_array($arrParentIds))
		{
			$arrParentIds = array($arrParentIds);
		}

		if (empty($arrParentIds))
		{
			return $arrReturn;
		}

		$arrParentIds = array_map('intval', $arrParentIds);
		$objChilds = $this->query("SELECT id, pid FROM " . $strTable . " WHERE pid IN(" . implode(',', $arrParentIds) . ")" . ($strWhere ? " AND $strWhere" : "") . ($blnSorting ? " ORDER BY " . $this->findInSet('pid', $arrParentIds) . ", sorting" : ""));

		if ($objChilds->numRows > 0)
		{
			if ($blnSorting)
			{
				$arrChilds = array();
				$arrOrdered = array();

				while ($objChilds->next())
				{
					$arrChilds[] = $objChilds->id;
					$arrOrdered[$objChilds->pid][] = $objChilds->id;
				}

				foreach (array_reverse(array_keys($arrOrdered)) as $pid)
				{
					$pos = (int) array_search($pid, $arrReturn);
					array_insert($arrReturn, $pos+1, $arrOrdered[$pid]);
				}

				$arrReturn = $this->getChildRecords($arrChilds, $strTable, $blnSorting, $arrReturn, $strWhere);
			}
			else
			{
				$arrChilds = $objChilds->fetchEach('id');
				$arrReturn = array_merge($arrChilds, $this->getChildRecords($arrChilds, $strTable, $blnSorting, $arrReturn, $strWhere));
			}
		}

		return $arrReturn;
	}


	/**
	 * Return the IDs of all parent records of a particular record
	 *
	 * @param integer $intId    The ID of the record
	 * @param string  $strTable The table name
	 *
	 * @return array An array of parent record IDs
	 */
	public function getParentRecords($intId, $strTable)
	{
		$arrReturn = array();

		// Currently supports a nesting-level of 10
		$objPages = $this->prepare("SELECT id, @pid:=pid FROM $strTable WHERE id=?" . str_repeat(" UNION SELECT id, @pid:=pid FROM $strTable WHERE id=@pid", 9))
						 ->execute($intId);

		while ($objPages->next())
		{
			$arrReturn[] = $objPages->id;
		}

		return $arrReturn;
	}


	/**
	 * Change the current database
	 *
	 * @param string $strDatabase The name of the target database
	 */
	public function setDatabase($strDatabase)
	{
		$this->resConnection->exec("USE $strDatabase");
	}


	/**
	 * Begin a transaction
	 */
	public function beginTransaction()
	{
		$this->resConnection->beginTransaction();
	}


	/**
	 * Commit a transaction
	 */
	public function commitTransaction()
	{
		$this->resConnection->commit();
	}


	/**
	 * Rollback a transaction
	 */
	public function rollbackTransaction()
	{
		$this->resConnection->rollBack();
	}


	/**
	 * Lock one or more tables
	 *
	 * @param array $arrTables An array of table names to be locked
	 */
	public function lockTables($arrTables)
	{
		$arrLocks = array();

		foreach ($arrTables as $table=>$mode)
		{
			$arrLocks[] = $this->resConnection->quoteIdentifier($table) . ' ' . $mode;
		}

		$this->resConnection->exec('LOCK TABLES ' . implode(', ', $arrLocks) . ';');
	}


	/**
	 * Unlock all tables
	 */
	public function unlockTables()
	{
		$this->resConnection->exec('UNLOCK TABLES;');
	}


	/**
	 * Return the table size in bytes
	 *
	 * @param string $strTable The table name
	 *
	 * @return integer The table size in bytes
	 */
	public function getSizeOf($strTable)
	{
		$statement = $this->resConnection->executeQuery('SHOW TABLE STATUS LIKE ' . $this->resConnection->quote($strTable));
		$status = $statement->fetch(\PDO::FETCH_ASSOC);

		return ($status['Data_length'] + $status['Index_length']);
	}


	/**
	 * Return the next autoincrement ID of a table
	 *
	 * @param string $strTable The table name
	 *
	 * @return integer The autoincrement ID
	 */
	public function getNextId($strTable)
	{
		$statement = $this->resConnection->executeQuery('SHOW TABLE STATUS LIKE ' . $this->resConnection->quote($strTable));
		$status = $statement->fetch(\PDO::FETCH_ASSOC);

		return $status['Auto_increment'];
	}


	/**
	 * Return a universal unique identifier
	 *
	 * @return string The UUID string
	 */
	public function getUuid()
	{
		static $ids;

		if (empty($ids))
		{
			$statement = $this->resConnection->executeQuery(implode(' UNION ALL ', array_fill(0, 10, "SELECT UNHEX(REPLACE(UUID(), '-', '')) AS uuid")));
			$ids = $statement->fetchAll(\PDO::FETCH_COLUMN);
		}

		return array_pop($ids);
	}


	/**
	 * Execute a query and do not cache the result
	 *
	 * @param string $strQuery The query string
	 *
	 * @return \Database\Result|object The Database\Result object
	 *
	 * @deprecated Use \Database::execute() instead
	 */
	public function executeUncached($strQuery)
	{
		return $this->execute($strQuery);
	}


	/**
	 * Always execute the query and add or replace an existing cache entry
	 *
	 * @param string $strQuery The query string
	 *
	 * @return \Database\Result|object The Database\Result object
	 *
	 * @deprecated Use \Database::execute() instead
	 */
	public function executeCached($strQuery)
	{
		return $this->execute($strQuery);
	}
}
