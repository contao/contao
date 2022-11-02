<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\Database\Result;
use Contao\Database\Statement;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;

/**
 * Handle the database communication
 *
 * The class is responsible for connecting to the database, listing tables and
 * fields, handling transactions and locking tables. It also creates the related
 * Statement and Result objects.
 *
 * Usage:
 *
 *     $db   = Database::getInstance();
 *     $stmt = $db->prepare("SELECT * FROM tl_user WHERE id=?");
 *     $res  = $stmt->execute(4);
 *
 * @property string $error The last error message
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
	 * @throws \Exception If a connection cannot be established
	 */
	protected function __construct()
	{
		$this->resConnection = System::getContainer()->get('database_connection');

		if (!\is_object($this->resConnection))
		{
			throw new \Exception(sprintf('Could not connect to database (%s)', $this->error));
		}
	}

	/**
	 * Close the database connection
	 */
	public function __destruct()
	{
		$this->resConnection = null;
	}

	/**
	 * Prevent cloning of the object (Singleton)
	 */
	final public function __clone()
	{
	}

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
	 * @param array $arrCustomConfig A configuration array
	 *
	 * @return Database The Database object
	 */
	public static function getInstance(array $arrCustomConfig=null)
	{
		$arrConfig = array();

		if (\is_array($arrCustomConfig))
		{
			$container = System::getContainer();

			$arrDefaultConfig = array
			(
				'dbHost'     => $container->hasParameter('database_host') ? $container->getParameter('database_host') : null,
				'dbPort'     => $container->hasParameter('database_port') ? $container->getParameter('database_port') : null,
				'dbUser'     => $container->hasParameter('database_user') ? $container->getParameter('database_user') : null,
				'dbPass'     => $container->hasParameter('database_password') ? $container->getParameter('database_password') : null,
				'dbDatabase' => $container->hasParameter('database_name') ? $container->getParameter('database_name') : null,
			);

			$arrConfig = array_merge($arrDefaultConfig, $arrCustomConfig);
		}

		// Sort the array before generating the key
		ksort($arrConfig);
		$strKey = md5(implode('', $arrConfig));

		if (!isset(static::$arrInstances[$strKey]))
		{
			static::$arrInstances[$strKey] = new static($arrConfig);
		}

		return static::$arrInstances[$strKey];
	}

	/**
	 * Prepare a query and return a Statement object
	 *
	 * @param string $strQuery The query string
	 *
	 * @return Statement The Statement object
	 */
	public function prepare($strQuery)
	{
		$objStatement = new Statement($this->resConnection);

		return $objStatement->prepare($strQuery);
	}

	/**
	 * Execute a query and return a Result object
	 *
	 * @param string $strQuery The query string
	 *
	 * @return Result The Result object
	 */
	public function execute($strQuery)
	{
		return $this->prepare($strQuery)->execute();
	}

	/**
	 * Execute a statement and return the number of affected rows
	 *
	 * @param string $strQuery The query string
	 *
	 * @return int The number of affected rows
	 */
	public function executeStatement(string $strQuery): int
	{
		return (int) $this->resConnection->executeStatement($strQuery);
	}

	/**
	 * Execute a raw query and return a Result object
	 *
	 * @param string $strQuery The query string
	 *
	 * @return Result The Result object
	 */
	public function query($strQuery)
	{
		$objStatement = new Statement($this->resConnection);

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
		if (\is_array($varSet))
		{
			$varSet = implode(',', $varSet);
		}

		if ($blnIsField)
		{
			$varSet = static::quoteIdentifier($varSet);
		}
		else
		{
			$varSet = $this->resConnection->quote($varSet);
		}

		return "FIND_IN_SET(" . static::quoteIdentifier($strKey) . ", " . $varSet . ")";
	}

	/**
	 * Return all tables as array
	 *
	 * @param string  $strDatabase The database name
	 * @param boolean $blnNoCache  If true, the cache will be bypassed
	 *
	 * @return array An array of table names
	 */
	public function listTables($strDatabase=null, $blnNoCache=false)
	{
		if ($blnNoCache || !isset($this->arrCache[$strDatabase]))
		{
			$strOldDatabase = $this->resConnection->getDatabase();

			// Change the database
			if ($strDatabase !== null && $strDatabase != $strOldDatabase)
			{
				$this->setDatabase($strDatabase);
			}

			$this->arrCache[$strDatabase] = $this->resConnection->createSchemaManager()->listTableNames();

			// Restore the database
			if ($strDatabase !== null && $strDatabase != $strOldDatabase)
			{
				$this->setDatabase($strOldDatabase);
			}
		}

		return $this->arrCache[$strDatabase];
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
		if (!$strTable)
		{
			return false;
		}

		return \in_array($strTable, $this->listTables($strDatabase, $blnNoCache));
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
				$arrChunks = preg_split('/(\([^)]+\))/', $objFields->Type, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);

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

				if ($objFields->Key)
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
				$strColumnName = $objIndex->Column_name;

				if ($objIndex->Sub_part)
				{
					$strColumnName .= '(' . $objIndex->Sub_part . ')';
				}

				$arrReturn[$objIndex->Key_name]['name'] = $objIndex->Key_name;
				$arrReturn[$objIndex->Key_name]['type'] = 'index';
				$arrReturn[$objIndex->Key_name]['index_fields'][] = $strColumnName;
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
		if (!$strField || !$strTable)
		{
			return false;
		}

		foreach ($this->listFields($strTable, $blnNoCache) as $arrField)
		{
			if ($arrField['name'] == $strField && $arrField['type'] != 'index')
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
		if (!$strName || !$strTable)
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
			if ($arrField['type'] != 'index')
			{
				$arrNames[] = $arrField['name'];
			}
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
		$strQuery = "SELECT * FROM $strTable WHERE " . static::quoteIdentifier($strField) . "=?";
		$params = array($varValue);

		if ($intId !== null)
		{
			$strQuery .= " AND id!=?";
			$params[] = $intId;
		}

		$objUnique = $this->prepare($strQuery)
						  ->limit(1)
						  ->execute(...$params);

		return $objUnique->numRows ? false : true;
	}

	/**
	 * Return the IDs of all child records of a particular record (see #2475)
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
		if (!\is_array($arrParentIds))
		{
			$arrParentIds = array($arrParentIds);
		}

		if (empty($arrParentIds))
		{
			return $arrReturn;
		}

		$arrParentIds = array_map('\intval', $arrParentIds);
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
					ArrayUtil::arrayInsert($arrReturn, $pos+1, $arrOrdered[$pid]);
				}

				$arrReturn = $this->getChildRecords($arrChilds, $strTable, $blnSorting, $arrReturn, $strWhere);
			}
			else
			{
				$arrChilds = $objChilds->fetchEach('id');
				$arrReturn = array_merge($arrChilds, $this->getChildRecords($arrChilds, $strTable, $blnSorting, $arrReturn, $strWhere));
			}
		}

		return array_map('\intval', $arrReturn);
	}

	/**
	 * Return the IDs of all parent records of a particular record
	 *
	 * @param integer $intId    The ID of the record
	 * @param string  $strTable The table name
	 * @param bool    $skipId   Omit the provided ID in the result set
	 *
	 * @return array An array of parent record IDs
	 */
	public function getParentRecords($intId, $strTable, bool $skipId = false)
	{
		// Limit to a nesting level of 10
		$ids = $this->prepare("SELECT id, @pid:=pid FROM $strTable WHERE id=?" . str_repeat(" UNION SELECT id, @pid:=pid FROM $strTable WHERE id=@pid", 9))
					->execute($intId)
					->fetchEach('id');

		// Trigger recursion in case our query returned exactly 10 IDs in which case we might have higher parent records
		if (\count($ids) === 10)
		{
			$ids = array_merge($ids, $this->getParentRecords(end($ids), $strTable, true));
		}

		if ($skipId && ($key = array_search($intId, $ids)) !== false)
		{
			unset($ids[$key]);
		}

		return array_map('\intval', array_values($ids));
	}

	/**
	 * Change the current database
	 *
	 * @param string $strDatabase The name of the target database
	 */
	public function setDatabase($strDatabase)
	{
		$this->resConnection->executeStatement("USE $strDatabase");
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

		$this->resConnection->executeStatement('LOCK TABLES ' . implode(', ', $arrLocks) . ';');
	}

	/**
	 * Unlock all tables
	 */
	public function unlockTables()
	{
		$this->resConnection->executeStatement('UNLOCK TABLES;');
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
		try
		{
			// MySQL 8 compatibility
			$this->resConnection->executeStatement('SET @@SESSION.information_schema_stats_expiry = 0');
		}
		catch (DriverException $e)
		{
		}

		$status = $this->resConnection->fetchAssociative('SHOW TABLE STATUS LIKE ' . $this->resConnection->quote($strTable));

		return $status['Data_length'] + $status['Index_length'];
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
		try
		{
			// MySQL 8 compatibility
			$this->resConnection->executeStatement('SET @@SESSION.information_schema_stats_expiry = 0');
		}
		catch (DriverException $e)
		{
		}

		$status = $this->resConnection->fetchAssociative('SHOW TABLE STATUS LIKE ' . $this->resConnection->quote($strTable));

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
			$ids = $this->resConnection->fetchFirstColumn(implode(' UNION ALL ', array_fill(0, 10, "SELECT UNHEX(REPLACE(UUID(), '-', '')) AS uuid")));
		}

		return array_pop($ids);
	}

	/**
	 * Quote the column name if it is a reserved word
	 *
	 * @param string $strName
	 *
	 * @return string
	 */
	public static function quoteIdentifier($strName)
	{
		// Quoted already or not an identifier (AbstractPlatform::quoteIdentifier() handles table.column so also allow . here)
		if (!preg_match('/^[A-Za-z0-9_$.]+$/', $strName))
		{
			return $strName;
		}

		return System::getContainer()->get('database_connection')->quoteIdentifier($strName);
	}
}
