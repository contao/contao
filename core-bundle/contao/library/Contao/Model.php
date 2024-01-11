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
use Contao\Model\Collection;
use Contao\Model\QueryBuilder;
use Contao\Model\Registry;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Filesystem\Path;

/**
 * Reads objects from and writes them to the database
 *
 * The class allows you to find and automatically join database records and to
 * convert the result into objects. It also supports creating new objects and
 * persisting them in the database.
 *
 * Usage:
 *
 *     // Write
 *     $user = new UserModel();
 *     $user->name = 'Leo Feyer';
 *     $user->city = 'Wuppertal';
 *     $user->save();
 *
 *     // Read
 *     $user = UserModel::findByCity('Wuppertal');
 *
 *     while ($user->next())
 *     {
 *         echo $user->name;
 *     }
 *
 * @property integer $id        The ID
 * @property string  $customTpl A custom template
 */
abstract class Model
{
	/**
	 * Insert flag
	 */
	const INSERT = 1;

	/**
	 * Update flag
	 */
	const UPDATE = 2;

	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable;

	/**
	 * Primary key
	 * @var string
	 */
	protected static $strPk = 'id';

	/**
	 * Data
	 * @var array
	 */
	protected $arrData = array();

	/**
	 * Modified keys
	 * @var array
	 */
	protected $arrModified = array();

	/**
	 * Relations
	 * @var array
	 */
	protected $arrRelations = array();

	/**
	 * Related
	 * @var array
	 */
	protected $arrRelated = array();

	/**
	 * Enums
	 * @var array
	 */
	protected $arrEnums = array();

	/**
	 * Prevent saving
	 * @var boolean
	 */
	protected $blnPreventSaving = false;

	/**
	 * @var array<string, array<string, string>>
	 */
	private static $arrColumnCastTypes = array();

	/**
	 * Load the relations and optionally process a result set
	 *
	 * @param Result|array $objResult An optional database result or array
	 */
	public function __construct($objResult=null)
	{
		$this->arrModified = array();

		$objDca = DcaExtractor::getInstance(static::$strTable);
		$this->arrRelations = $objDca->getRelations();
		$this->arrEnums = $objDca->getEnums();

		if ($objResult !== null)
		{
			$arrRelated = array();

			if ($objResult instanceof Result)
			{
				$arrData = $objResult->row();
			}
			else
			{
				$arrData = (array) $objResult;
			}

			// Look for joined fields
			foreach ($arrData as $k=>$v)
			{
				if (str_contains($k, '__'))
				{
					list($key, $field) = explode('__', $k, 2);

					if (!isset($arrRelated[$key]))
					{
						$arrRelated[$key] = array();
					}

					$arrRelated[$key][$field] = $v;
					unset($arrData[$k]);
				}
			}

			$objRegistry = Registry::getInstance();

			$this->setRow($arrData); // see #5439
			$objRegistry->register($this);

			// Create the related models
			foreach ($arrRelated as $key=>$row)
			{
				if (!isset($this->arrRelations[$key]['table']))
				{
					throw new \Exception('Incomplete relation defined for ' . static::$strTable . '.' . $key);
				}

				$table = $this->arrRelations[$key]['table'];

				/** @var static $strClass */
				$strClass = static::getClassFromTable($table);
				$intPk = $strClass::getPk();

				// If the primary key is empty, set null (see #5356)
				if (!isset($row[$intPk]))
				{
					$this->arrRelated[$key] = null;
				}
				else
				{
					$objRelated = $objRegistry->fetch($table, $row[$intPk]);

					if ($objRelated !== null)
					{
						$objRelated->mergeRow($row);
					}
					else
					{
						$objRelated = new $strClass();
						$objRelated->setRow($row);

						$objRegistry->register($objRelated);
					}

					$this->arrRelated[$key] = $objRelated;
				}
			}
		}
	}

	/**
	 * Unset the primary key when cloning an object
	 */
	public function __clone()
	{
		$this->arrModified = array();
		$this->blnPreventSaving = false;

		unset($this->arrData[static::$strPk]);
	}

	/**
	 * Clone a model with its original values
	 *
	 * @return static The model
	 */
	public function cloneOriginal()
	{
		$clone = clone $this;
		$clone->setRow($this->originalRow());

		return $clone;
	}

	/**
	 * Set an object property
	 *
	 * @param string $strKey   The property name
	 * @param mixed  $varValue The property value
	 */
	public function __set($strKey, $varValue)
	{
		if (isset($this->arrData[$strKey]) && $this->arrData[$strKey] === $varValue)
		{
			return;
		}

		$this->markModified($strKey);
		$this->arrData[$strKey] = $varValue;

		unset($this->arrRelated[$strKey]);

		if ($varValue !== ($varNewValue = static::convertToPhpValue($strKey, $varValue)))
		{
			trigger_deprecation('contao/core-bundle', '5.0', 'Setting "%s::$%s" to type %s has been deprecated and will no longer work in Contao 6. Use type "%s" instead.', static::class, $strKey, get_debug_type($varValue), get_debug_type($varNewValue));
		}
	}

	/**
	 * Return an object property
	 *
	 * @param string $strKey The property key
	 *
	 * @return mixed|null The property value or null
	 */
	public function __get($strKey)
	{
		return $this->arrData[$strKey] ?? null;
	}

	/**
	 * Check whether a property is set
	 *
	 * @param string $strKey The property key
	 *
	 * @return boolean True if the property is set
	 */
	public function __isset($strKey)
	{
		return isset($this->arrData[$strKey]);
	}

	/**
	 * Return the name of the primary key
	 *
	 * @return string The primary key
	 */
	public static function getPk()
	{
		return static::$strPk;
	}

	/**
	 * Return an array of unique field/column names (without the PK)
	 *
	 * @return array
	 */
	public static function getUniqueFields()
	{
		$objDca = DcaExtractor::getInstance(static::getTable());

		return $objDca->getUniqueFields();
	}

	/**
	 * Return the name of the related table
	 *
	 * @return string The table name
	 */
	public static function getTable()
	{
		return static::$strTable;
	}

	/**
	 * Return the current record as associative array
	 *
	 * @return array The data record
	 */
	public function row()
	{
		return $this->arrData;
	}

	/**
	 * Return the original values as associative array
	 *
	 * @return array The original data
	 */
	public function originalRow()
	{
		$row = $this->row();

		if (!$this->isModified())
		{
			return $row;
		}

		$originalRow = array();

		foreach ($row as $k=>$v)
		{
			$originalRow[$k] = $this->arrModified[$k] ?? $v;
		}

		return $originalRow;
	}

	/**
	 * Return true if the model has been modified
	 *
	 * @return boolean True if the model has been modified
	 */
	public function isModified()
	{
		return !empty($this->arrModified);
	}

	/**
	 * Set the current record from an array
	 *
	 * @param array $arrData The data record
	 *
	 * @return static The model object
	 */
	public function setRow(array $arrData)
	{
		foreach ($arrData as $k=>$v)
		{
			if (str_contains($k, '__'))
			{
				unset($arrData[$k]);
			}
		}

		foreach ($arrData as $strKey => $varValue)
		{
			$arrData[$strKey] = static::convertToPhpValue($strKey, $varValue);
		}

		$this->arrData = $arrData;

		return $this;
	}

	/**
	 * Set the current record from an array preserving modified but unsaved fields
	 *
	 * @param array $arrData The data record
	 *
	 * @return static The model object
	 */
	public function mergeRow(array $arrData)
	{
		foreach ($arrData as $k=>$v)
		{
			if (str_contains($k, '__'))
			{
				continue;
			}

			if (!isset($this->arrModified[$k]))
			{
				$this->arrData[$k] = static::convertToPhpValue($k, $v);
			}
		}

		return $this;
	}

	/**
	 * @internal
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function getColumnCastTypesFromDatabase(): array
	{
		$schemaManager = System::getContainer()->get('database_connection')->createSchemaManager();
		$schema = $schemaManager->introspectSchema();

		return static::getColumnCastTypesFromSchema($schema);
	}

	/**
	 * @internal
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function getColumnCastTypesFromDca(): array
	{
		return static::getColumnCastTypesFromSchema(System::getContainer()->get('contao.doctrine.schema_provider')->createSchema());
	}

	private static function getColumnCastTypesFromSchema(Schema $schema): array
	{
		$types = array();

		foreach ($schema->getTables() as $table)
		{
			foreach ($table->getColumns() as $column)
			{
				$type = strtolower($column->getType()->getName());

				if (\in_array($type, array(Types::INTEGER, Types::SMALLINT, Types::FLOAT, Types::BOOLEAN), true))
				{
					$types[$table->getName()][$column->getName()] = $type;
				}
			}
		}

		return $types;
	}

	/**
	 * Convert a value from the database to the correct PHP type as defined in
	 * the schema for the given column.
	 *
	 * @internal
	 *
	 * @param string $strKey   The column name
	 * @param mixed  $varValue The value as it was retrieved from the database
	 *
	 * @return mixed The value cast to the corresponding PHP type
	 */
	public static function convertToPhpValue(string $strKey, mixed $varValue): mixed
	{
		if (null === $varValue)
		{
			return null;
		}

		if (!self::$arrColumnCastTypes)
		{
			$path = Path::join(System::getContainer()->getParameter('kernel.cache_dir'), 'contao/config/column-types.php');

			if (!System::getContainer()->getParameter('kernel.debug') && file_exists($path))
			{
				self::$arrColumnCastTypes = include $path;
			}
			else
			{
				self::$arrColumnCastTypes = self::getColumnCastTypesFromDatabase();
			}
		}

		return match (self::$arrColumnCastTypes[static::$strTable][$strKey] ?? null)
		{
			Types::INTEGER, Types::SMALLINT => (int) $varValue,
			Types::FLOAT => (float) $varValue,
			Types::BOOLEAN => (bool) $varValue,
			default => $varValue,
		};
	}

	/**
	 * Mark a field as modified
	 *
	 * @param string $strKey The field key
	 */
	public function markModified($strKey)
	{
		if (!isset($this->arrModified[$strKey]))
		{
			$this->arrModified[$strKey] = $this->arrData[$strKey] ?? null;
		}
	}

	/**
	 * Return the object instance
	 *
	 * @return static The model object
	 */
	public function current()
	{
		return $this;
	}

	/**
	 * Save the current record
	 *
	 * @return static The model object
	 *
	 * @throws \InvalidArgumentException If an argument is passed
	 * @throws \RuntimeException         If the model cannot be saved
	 */
	public function save()
	{
		// The instance cannot be saved
		if ($this->blnPreventSaving)
		{
			throw new \RuntimeException('The model instance has been detached and cannot be saved');
		}

		$objDatabase = Database::getInstance();
		$arrFields = $objDatabase->getFieldNames(static::$strTable);

		// The model is in the registry
		if (Registry::getInstance()->isRegistered($this))
		{
			$arrSet = array();
			$arrRow = $this->row();

			// Only update modified fields
			foreach ($this->arrModified as $k=>$v)
			{
				// Only set fields that exist in the DB
				if (\in_array($k, $arrFields))
				{
					$arrSet[$k] = $arrRow[$k];
				}
			}

			$arrSet = $this->preSave($arrSet);

			// No modified fields
			if (empty($arrSet))
			{
				return $this;
			}

			// Track primary key changes
			$intPk = $this->arrModified[static::$strPk] ?? $this->{static::$strPk};

			if ($intPk === null)
			{
				throw new \RuntimeException('The primary key has not been set');
			}

			// Update the row
			$objDatabase->prepare("UPDATE " . static::$strTable . " %s WHERE " . Database::quoteIdentifier(static::$strPk) . "=?")
						->set($arrSet)
						->execute($intPk);

			$this->postSave(self::UPDATE);
			$this->arrModified = array(); // reset after postSave()
		}

		// The model is not yet in the registry
		else
		{
			$arrSet = $this->row();

			// Remove fields that do not exist in the DB
			foreach ($arrSet as $k=>$v)
			{
				if (!\in_array($k, $arrFields))
				{
					unset($arrSet[$k]);
				}
			}

			$arrSet = $this->preSave($arrSet);

			// No modified fields
			if (empty($arrSet))
			{
				return $this;
			}

			// Insert a new row
			$stmt = $objDatabase->prepare("INSERT INTO " . static::$strTable . " %s")
								->set($arrSet)
								->execute();

			if (static::$strPk == 'id')
			{
				$this->id = (int) $stmt->insertId;
			}

			$this->postSave(self::INSERT);
			$this->arrModified = array(); // reset after postSave()

			Registry::getInstance()->register($this);
		}

		return $this;
	}

	/**
	 * Modify the current row before it is stored in the database
	 *
	 * @param array $arrSet The data array
	 *
	 * @return array The modified data array
	 */
	protected function preSave(array $arrSet)
	{
		return $arrSet;
	}

	/**
	 * Modify the current row after it has been stored in the database
	 *
	 * @param integer $intType The query type (Model::INSERT or Model::UPDATE)
	 */
	protected function postSave($intType)
	{
		if ($intType == self::INSERT)
		{
			$this->refresh(); // might have been modified by default values or triggers
		}
	}

	/**
	 * Delete the current record and return the number of affected rows
	 *
	 * @return integer The number of affected rows
	 */
	public function delete()
	{
		// Track primary key changes
		$intPk = $this->arrModified[static::$strPk] ?? $this->{static::$strPk};

		// Delete the row
		$intAffected = Database::getInstance()->prepare("DELETE FROM " . static::$strTable . " WHERE " . Database::quoteIdentifier(static::$strPk) . "=?")
											   ->execute($intPk)
											   ->affectedRows;

		if ($intAffected)
		{
			// Unregister the model
			Registry::getInstance()->unregister($this);

			// Remove the primary key (see #6162)
			$this->arrData[static::$strPk] = null;
		}

		return $intAffected;
	}

	/**
	 * Lazy load related records
	 *
	 * @param string $strKey     The property name
	 * @param array  $arrOptions An optional options array
	 *
	 * @return Collection<static>|static|null The model or a model collection if there are multiple rows
	 *
	 * @throws \Exception If $strKey is not a related field
	 */
	public function getRelated($strKey, array $arrOptions=array())
	{
		// The related model has been loaded before
		if (\array_key_exists($strKey, $this->arrRelated))
		{
			return $this->arrRelated[$strKey];
		}

		// The relation does not exist
		if (!isset($this->arrRelations[$strKey]))
		{
			$table = static::getTable();

			throw new \Exception("Field $table.$strKey does not seem to be related");
		}

		// The relation exists but there is no reference yet (see #6161 and #458)
		if (empty($this->$strKey))
		{
			return null;
		}

		$arrRelation = $this->arrRelations[$strKey];

		/** @var static $strClass */
		$strClass = static::getClassFromTable($arrRelation['table']);

		// Load the related record(s)
		if ($arrRelation['type'] == 'hasOne' || $arrRelation['type'] == 'belongsTo')
		{
			$this->arrRelated[$strKey] = $strClass::findOneBy($arrRelation['field'], $this->$strKey, $arrOptions);
		}
		elseif ($arrRelation['type'] == 'hasMany' || $arrRelation['type'] == 'belongsToMany')
		{
			if (isset($arrRelation['delimiter']))
			{
				$arrValues = StringUtil::trimsplit($arrRelation['delimiter'], $this->$strKey);
			}
			else
			{
				$arrValues = StringUtil::deserialize($this->$strKey, true);
			}

			$objModel = null;

			if (\is_array($arrValues))
			{
				// Handle UUIDs (see #6525 and #8850)
				if ($arrRelation['table'] == 'tl_files' && $arrRelation['field'] == 'uuid')
				{
					/** @var FilesModel $strClass */
					$objModel = $strClass::findMultipleByUuids($arrValues, $arrOptions);
				}
				else
				{
					$strField = $arrRelation['table'] . '.' . Database::quoteIdentifier($arrRelation['field']);

					$arrOptions = array_merge
					(
						array
						(
							'order' => Database::getInstance()->findInSet($strField, $arrValues)
						),
						$arrOptions
					);

					$objModel = $strClass::findBy(array($strField . " IN('" . implode("','", $arrValues) . "')"), null, $arrOptions);
				}
			}

			$this->arrRelated[$strKey] = $objModel;
		}

		return $this->arrRelated[$strKey];
	}

	public function getEnum($strKey): \BackedEnum|null
	{
		$enum = $this->arrEnums[$strKey] ?? null;

		// The enum does not exist
		if (null === $enum)
		{
			throw new \Exception(sprintf('Field %s.%s has no enum configured', static::getTable(), $strKey));
		}

		$varValue = $this->{$strKey};

		// The value is invalid
		if (!\is_string($varValue) && !\is_int($varValue))
		{
			throw new \Exception(sprintf('Value of %s.%s must be a string or an integer to resolve a backed enumeration', static::getTable(), $strKey));
		}

		return $this->arrEnums[$strKey]::tryFrom($varValue);
	}

	/**
	 * Reload the data from the database discarding all modifications
	 */
	public function refresh()
	{
		// Track primary key changes
		$intPk = $this->arrModified[static::$strPk] ?? $this->{static::$strPk};

		// Reload the database record
		$res = Database::getInstance()->prepare("SELECT * FROM " . static::$strTable . " WHERE " . Database::quoteIdentifier(static::$strPk) . "=?")
									   ->execute($intPk);

		$this->setRow($res->row());
	}

	/**
	 * Detach the model from the registry
	 *
	 * @param boolean $blnKeepClone Keeps a clone of the model in the registry
	 */
	public function detach($blnKeepClone=true)
	{
		$registry = Registry::getInstance();

		if (!$registry->isRegistered($this))
		{
			return;
		}

		$registry->unregister($this);

		if ($blnKeepClone)
		{
			$this->cloneOriginal()->attach();
		}
	}

	/**
	 * Attach the model to the registry
	 */
	public function attach()
	{
		Registry::getInstance()->register($this);
	}

	/**
	 * Called when the model is attached to the model registry
	 *
	 * @param Registry $registry The model registry
	 */
	public function onRegister(Registry $registry)
	{
		// Register aliases to unique fields
		foreach (static::getUniqueFields() as $strColumn)
		{
			$varAliasValue = $this->{$strColumn};

			if (!$registry->isRegisteredAlias($this, $strColumn, $varAliasValue))
			{
				$registry->registerAlias($this, $strColumn, $varAliasValue);
			}
		}
	}

	/**
	 * Called when the model is detached from the model registry
	 *
	 * @param Registry $registry The model registry
	 */
	public function onUnregister(Registry $registry)
	{
		// Unregister aliases to unique fields
		foreach (static::getUniqueFields() as $strColumn)
		{
			$varAliasValue = $this->{$strColumn};

			if ($registry->isRegisteredAlias($this, $strColumn, $varAliasValue))
			{
				$registry->unregisterAlias($this, $strColumn, $varAliasValue);
			}
		}
	}

	/**
	 * Prevent saving the model
	 *
	 * @param boolean $blnKeepClone Keeps a clone of the model in the registry
	 */
	public function preventSaving($blnKeepClone=true)
	{
		$this->detach($blnKeepClone);
		$this->blnPreventSaving = true;
	}

	/**
	 * Find a single record by its primary key
	 *
	 * @param int|string|null $varValue   The property value
	 * @param array           $arrOptions An optional options array
	 *
	 * @return static|null The model or null if the result is empty
	 */
	public static function findByPk($varValue, array $arrOptions=array())
	{
		// Try to load from the registry
		if (empty($arrOptions))
		{
			$objModel = Registry::getInstance()->fetch(static::$strTable, $varValue);

			if ($objModel !== null)
			{
				return $objModel;
			}
		}

		$arrOptions = array_merge
		(
			array
			(
				'limit'  => 1,
				'column' => static::$strPk,
				'value'  => $varValue,
				'return' => 'Model'
			),
			$arrOptions
		);

		return static::find($arrOptions);
	}

	/**
	 * Find a single record by its ID or alias
	 *
	 * @param mixed $varId      The ID or alias
	 * @param array $arrOptions An optional options array
	 *
	 * @return static|null The model or null if the result is empty
	 */
	public static function findByIdOrAlias($varId, array $arrOptions=array())
	{
		$isAlias = !preg_match('/^[1-9]\d*$/', $varId);

		// Try to load from the registry
		if (!$isAlias && empty($arrOptions))
		{
			$objModel = Registry::getInstance()->fetch(static::$strTable, $varId);

			if ($objModel !== null)
			{
				return $objModel;
			}
		}

		$t = static::$strTable;

		$arrOptions = array_merge
		(
			array
			(
				'limit'  => 1,
				'column' => $isAlias ? array("BINARY $t.alias=?") : array("$t.id=?"),
				'value'  => $varId,
				'return' => 'Model'
			),
			$arrOptions
		);

		return static::find($arrOptions);
	}

	/**
	 * Find multiple records by their IDs
	 *
	 * @param array $arrIds     An array of IDs
	 * @param array $arrOptions An optional options array
	 *
	 * @return Collection<static>|null The model collection or null if there are no records
	 */
	public static function findMultipleByIds($arrIds, array $arrOptions=array())
	{
		if (empty($arrIds) || !\is_array($arrIds))
		{
			return null;
		}

		$arrRegistered = array();
		$arrUnregistered = array();

		// Search for registered models
		foreach ($arrIds as $intId)
		{
			if (empty($arrOptions))
			{
				$arrRegistered[$intId] = Registry::getInstance()->fetch(static::$strTable, $intId);
			}

			if (!isset($arrRegistered[$intId]))
			{
				$arrUnregistered[] = $intId;
			}
		}

		// Fetch only the missing models from the database
		if (!empty($arrUnregistered))
		{
			$t = static::$strTable;

			$arrOptions = array_merge
			(
				array
				(
					'column' => array("$t.id IN(" . implode(',', array_map('\intval', $arrUnregistered)) . ")"),
					'order'  => Database::getInstance()->findInSet("$t.id", $arrIds),
					'return' => 'Collection'
				),
				$arrOptions
			);

			$objMissing = static::find($arrOptions);

			if ($objMissing !== null)
			{
				foreach ($objMissing as $objCurrent)
				{
					$intId = $objCurrent->{static::$strPk};
					$arrRegistered[$intId] = $objCurrent;
				}
			}
		}

		$arrRegistered = array_filter(array_values($arrRegistered));

		if (empty($arrRegistered))
		{
			return null;
		}

		return static::createCollection($arrRegistered, static::$strTable);
	}

	/**
	 * Find a single record by various criteria
	 *
	 * @param mixed $strColumn  The property name
	 * @param mixed $varValue   The property value, NULL is interpreted as "no value", use array(null) to query for NULL values
	 * @param array $arrOptions An optional options array
	 *
	 * @return static|null The model or null if the result is empty
	 */
	public static function findOneBy($strColumn, $varValue, array $arrOptions=array())
	{
		$arrOptions = array_merge
		(
			array
			(
				'limit'  => 1,
				'column' => $strColumn,
				'value'  => $varValue,
				'return' => 'Model'
			),
			$arrOptions
		);

		return static::find($arrOptions);
	}

	/**
	 * Find records by various criteria
	 *
	 * @param mixed $strColumn  The property name
	 * @param mixed $varValue   The property value, NULL is interpreted as "no value", use array(null) to query for NULL values
	 * @param array $arrOptions An optional options array
	 *
	 * @return Collection<static>|static|null A model, model collection or null if the result is empty
	 */
	public static function findBy($strColumn, $varValue, array $arrOptions=array())
	{
		$blnModel = false;
		$arrColumn = (array) $strColumn;

		if (\count($arrColumn) == 1 && ($arrColumn[0] === static::getPk() || \in_array($arrColumn[0], static::getUniqueFields())))
		{
			$blnModel = true;
		}

		$arrOptions = array_merge
		(
			array
			(
				'column' => $strColumn,
				'value'  => $varValue,
				'return' => $blnModel ? 'Model' : 'Collection'
			),
			$arrOptions
		);

		return static::find($arrOptions);
	}

	/**
	 * Find all records
	 *
	 * @param array $arrOptions An optional options array
	 *
	 * @return Collection<static>|null The model collection or null if the result is empty
	 */
	public static function findAll(array $arrOptions=array())
	{
		$arrOptions = array_merge
		(
			array
			(
				'return' => 'Collection'
			),
			$arrOptions
		);

		return static::find($arrOptions);
	}

	/**
	 * Magic method to map Model::findByName() to Model::findBy('name')
	 *
	 * @param string $name The method name
	 * @param array  $args The passed arguments
	 *
	 * @return Collection<static>|integer|static|null A model, model collection or the number of matching rows
	 *
	 * @throws \Exception If the method name is invalid
	 */
	public static function __callStatic($name, $args)
	{
		if (str_starts_with($name, 'findBy'))
		{
			array_unshift($args, lcfirst(substr($name, 6)));

			return static::findBy(...$args);
		}

		if (str_starts_with($name, 'findOneBy'))
		{
			array_unshift($args, lcfirst(substr($name, 9)));

			return static::findOneBy(...$args);
		}

		if (str_starts_with($name, 'countBy'))
		{
			array_unshift($args, lcfirst(substr($name, 7)));

			return static::countBy(...$args);
		}

		throw new \Exception("Unknown method $name");
	}

	/**
	 * Find records and return the model or model collection
	 *
	 * Supported options:
	 *
	 * * column: the field name
	 * * value:  the field value
	 * * limit:  the maximum number of rows
	 * * offset: the number of rows to skip
	 * * order:  the sorting order
	 * * eager:  load all related records eagerly
	 *
	 * @param array $arrOptions The options array
	 *
	 * @return Collection<static>|static[]|static|null A model, model collection or null if the result is empty
	 */
	protected static function find(array $arrOptions)
	{
		if (!static::$strTable)
		{
			return null;
		}

		// Try to load from the registry
		if (($arrOptions['return'] ?? null) == 'Model')
		{
			$arrColumn = (array) $arrOptions['column'];

			if (\count($arrColumn) == 1)
			{
				// Support table prefixes
				$arrColumn[0] = preg_replace('/^' . preg_quote(static::getTable(), '/') . '\./', '', $arrColumn[0]);

				if ($arrColumn[0] == static::$strPk || \in_array($arrColumn[0], static::getUniqueFields()))
				{
					$varKey = \is_array($arrOptions['value'] ?? null) ? $arrOptions['value'][0] : ($arrOptions['value'] ?? null);

					// Return early if column is unique and field value is null (#5033)
					if ($varKey === null)
					{
						return null;
					}

					$objModel = Registry::getInstance()->fetch(static::$strTable, $varKey, $arrColumn[0]);

					if ($objModel !== null)
					{
						return $objModel;
					}
				}
			}
		}

		$arrOptions['table'] = static::$strTable;
		$strQuery = static::buildFindQuery($arrOptions);

		$objStatement = Database::getInstance()->prepare($strQuery);

		// Defaults for limit and offset
		if (!isset($arrOptions['limit']))
		{
			$arrOptions['limit'] = 0;
		}

		if (!isset($arrOptions['offset']))
		{
			$arrOptions['offset'] = 0;
		}

		// Limit
		if ($arrOptions['limit'] > 0 || $arrOptions['offset'] > 0)
		{
			$objStatement->limit($arrOptions['limit'], $arrOptions['offset']);
		}

		if (!isset($arrOptions['value']))
		{
			$arrOptions['value'] = \is_string($arrOptions['column'] ?? null) ? array(null) : array();
		}

		$objStatement = static::preFind($objStatement);
		$objResult = $objStatement->execute(...(array) $arrOptions['value']);

		if ($objResult->numRows < 1)
		{
			if (($arrOptions['return'] ?? null) == 'Array')
			{
				trigger_deprecation('contao/core-bundle', '5.2', 'Using "Array" as return type for model queries has been deprecated and will no longer work in Contao 6. Use the "getModels()" method instead.');

				return array();
			}

			return null;
		}

		$objResult = static::postFind($objResult);

		// Try to load from the registry
		if (($arrOptions['return'] ?? null) == 'Model')
		{
			$objModel = Registry::getInstance()->fetch(static::$strTable, $objResult->{static::$strPk});

			if ($objModel !== null)
			{
				return $objModel->mergeRow($objResult->row());
			}

			return static::createModelFromDbResult($objResult);
		}

		if (($arrOptions['return'] ?? null) == 'Array')
		{
			trigger_deprecation('contao/core-bundle', '5.2', 'Using "Array" as return type for model queries has been deprecated and will no longer work in Contao 6. Use the "getModels()" method instead.');

			return static::createCollectionFromDbResult($objResult, static::$strTable)->getModels();
		}

		return static::createCollectionFromDbResult($objResult, static::$strTable);
	}

	/**
	 * Modify the database statement before it is executed
	 *
	 * @param Statement $objStatement The database statement object
	 *
	 * @return Statement The database statement object
	 */
	protected static function preFind(Statement $objStatement)
	{
		return $objStatement;
	}

	/**
	 * Modify the database result before the model is created
	 *
	 * @param Result $objResult The database result object
	 *
	 * @return Result The database result object
	 */
	protected static function postFind(Result $objResult)
	{
		return $objResult;
	}

	/**
	 * Return the number of records matching certain criteria
	 *
	 * @param mixed $strColumn  An optional property name
	 * @param mixed $varValue   An optional property value
	 * @param array $arrOptions An optional options array
	 *
	 * @return integer The number of matching rows
	 */
	public static function countBy($strColumn=null, $varValue=null, array $arrOptions=array())
	{
		if (!static::$strTable)
		{
			return 0;
		}

		$arrOptions = array_merge
		(
			array
			(
				'table'  => static::$strTable,
				'column' => $strColumn,
				'value'  => $varValue
			),
			$arrOptions
		);

		$strQuery = static::buildCountQuery($arrOptions);

		return (int) Database::getInstance()->prepare($strQuery)->execute(...(array) ($arrOptions['value'] ?? array()))->count;
	}

	/**
	 * Return the total number of rows
	 *
	 * @return integer The total number of rows
	 */
	public static function countAll()
	{
		return static::countBy();
	}

	/**
	 * Compile a Model class name from a table name (e.g. tl_form_field becomes FormFieldModel)
	 *
	 * @param string $strTable The table name
	 *
	 * @return class-string<Model> The model class name
	 */
	public static function getClassFromTable($strTable)
	{
		if (!isset($GLOBALS['TL_MODELS'][$strTable]))
		{
			throw new \RuntimeException(sprintf('There is no class for table "%s" registered in $GLOBALS[\'TL_MODELS\'].', $strTable));
		}

		return $GLOBALS['TL_MODELS'][$strTable];
	}

	/**
	 * Build a query based on the given options
	 *
	 * @param array $arrOptions The options array
	 *
	 * @return string The query string
	 */
	protected static function buildFindQuery(array $arrOptions)
	{
		return QueryBuilder::find($arrOptions);
	}

	/**
	 * Build a query based on the given options to count the number of records
	 *
	 * @param array $arrOptions The options array
	 *
	 * @return string The query string
	 */
	protected static function buildCountQuery(array $arrOptions)
	{
		return QueryBuilder::count($arrOptions);
	}

	/**
	 * Create a model from a database result
	 *
	 * @param Result $objResult The database result object
	 *
	 * @return static The model
	 */
	protected static function createModelFromDbResult(Result $objResult)
	{
		/**
		 * @var static               $strClass
		 * @var class-string<static> $strClass
		 */
		$strClass = static::getClassFromTable(static::$strTable);

		return new $strClass($objResult);
	}

	/**
	 * Create a Collection object
	 *
	 * @param array  $arrModels An array of models
	 * @param string $strTable  The table name
	 *
	 * @return Collection<static> The Collection object
	 */
	protected static function createCollection(array $arrModels, $strTable)
	{
		return new Collection($arrModels, $strTable);
	}

	/**
	 * Create a new collection from a database result
	 *
	 * @param Result $objResult The database result object
	 * @param string $strTable  The table name
	 *
	 * @return Collection<static> The model collection
	 */
	protected static function createCollectionFromDbResult(Result $objResult, $strTable)
	{
		return Collection::createFromDbResult($objResult, $strTable);
	}

	/**
	 * Check if the preview mode is enabled
	 *
	 * @param array $arrOptions The options array
	 *
	 * @return boolean
	 */
	protected static function isPreviewMode(array $arrOptions)
	{
		if (isset($arrOptions['ignoreFePreview']))
		{
			return false;
		}

		return System::getContainer()->get('contao.security.token_checker')->isPreviewMode();
	}
}
