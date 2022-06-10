<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Database;

use Contao\Controller;
use Contao\Database;
use Contao\DcaExtractor;
use Contao\System;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Compares the existing database structure with the DCA table settings and
 * calculates the queries needed to update the database.
 */
class Installer extends Controller
{
	/**
	 * Make the constructor public
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Compile a command array for each database modification
	 *
	 * @return array An array of commands
	 */
	protected function compileCommands()
	{
		$drop = array();
		$create = array();
		$return = array();

		$sql_current = $this->getFromDb();
		$sql_target = $this->getFromDca();

		// Create tables
		foreach (array_diff(array_keys($sql_target), array_keys($sql_current)) as $table)
		{
			$return['CREATE'][] = "CREATE TABLE `" . $table . "` (\n  " . implode(",\n  ", $sql_target[$table]['TABLE_FIELDS']) . (!empty($sql_target[$table]['TABLE_CREATE_DEFINITIONS']) ? ',' . "\n  " . implode(",\n  ", $sql_target[$table]['TABLE_CREATE_DEFINITIONS']) : '') . "\n)" . $sql_target[$table]['TABLE_OPTIONS'] . ';';
			$create[] = $table;
		}

		// Add or change fields
		foreach ($sql_target as $k=>$v)
		{
			if (\in_array($k, $create))
			{
				continue;
			}

			// Fields
			if (\is_array($v['TABLE_FIELDS']))
			{
				foreach ($v['TABLE_FIELDS'] as $kk=>$vv)
				{
					if (!isset($sql_current[$k]['TABLE_FIELDS'][$kk]))
					{
						$return['ALTER_ADD'][] = 'ALTER TABLE `' . $k . '` ADD ' . $vv . ';';
					}
					elseif ($sql_current[$k]['TABLE_FIELDS'][$kk] != $vv)
					{
						$return['ALTER_CHANGE'][] = 'ALTER TABLE `' . $k . '` CHANGE `' . $kk . '` ' . $vv . ';';
					}
				}
			}

			// Create definitions
			if (\is_array($v['TABLE_CREATE_DEFINITIONS']))
			{
				foreach ($v['TABLE_CREATE_DEFINITIONS'] as $kk=>$vv)
				{
					if (!isset($sql_current[$k]['TABLE_CREATE_DEFINITIONS'][$kk]))
					{
						$return['ALTER_ADD'][] = 'ALTER TABLE `' . $k . '` ADD ' . $vv . ';';
					}
					elseif ($sql_current[$k]['TABLE_CREATE_DEFINITIONS'][$kk] != str_replace('FULLTEXT ', '', $vv))
					{
						$return['ALTER_CHANGE'][] = 'ALTER TABLE `' . $k . '` DROP INDEX `' . $kk . '`, ADD ' . $vv . ';';
					}
				}
			}

			// Move auto_increment fields to the end of the array
			if (\is_array($return['ALTER_ADD']))
			{
				foreach (preg_grep('/auto_increment/i', $return['ALTER_ADD']) as $kk=>$vv)
				{
					unset($return['ALTER_ADD'][$kk]);
					$return['ALTER_ADD'][$kk] = $vv;
				}
			}

			if (\is_array($return['ALTER_CHANGE']))
			{
				foreach (preg_grep('/auto_increment/i', $return['ALTER_CHANGE']) as $kk=>$vv)
				{
					unset($return['ALTER_CHANGE'][$kk]);
					$return['ALTER_CHANGE'][$kk] = $vv;
				}
			}
		}

		// Drop tables
		foreach (array_diff(array_keys($sql_current), array_keys($sql_target)) as $table)
		{
			$return['DROP'][] = 'DROP TABLE `' . $table . '`;';
			$drop[] = $table;
		}

		// Drop fields
		foreach ($sql_current as $k=>$v)
		{
			if (!\in_array($k, $drop))
			{
				// Create definitions
				if (\is_array($v['TABLE_CREATE_DEFINITIONS']))
				{
					foreach ($v['TABLE_CREATE_DEFINITIONS'] as $kk=>$vv)
					{
						if (!isset($sql_target[$k]['TABLE_CREATE_DEFINITIONS'][$kk]))
						{
							$return['ALTER_DROP'][] = 'ALTER TABLE `' . $k . '` DROP INDEX `' . $kk . '`;';
						}
					}
				}

				// Fields
				if (\is_array($v['TABLE_FIELDS']))
				{
					foreach ($v['TABLE_FIELDS'] as $kk=>$vv)
					{
						if (!isset($sql_target[$k]['TABLE_FIELDS'][$kk]))
						{
							$return['ALTER_DROP'][] = 'ALTER TABLE `' . $k . '` DROP `' . $kk . '`;';
						}
					}
				}
			}
		}

		// HOOK: allow third-party developers to modify the array (see #3281)
		if (isset($GLOBALS['TL_HOOKS']['sqlCompileCommands']) && \is_array($GLOBALS['TL_HOOKS']['sqlCompileCommands']))
		{
			foreach ($GLOBALS['TL_HOOKS']['sqlCompileCommands'] as $callback)
			{
				$this->import($callback[0]);
				$return = $this->{$callback[0]}->{$callback[1]}($return);
			}
		}

		return $return;
	}

	/**
	 * Get the DCA table settings from the DCA cache
	 *
	 * @return array An array of DCA table settings
	 */
	public function getFromDca()
	{
		$return = array();
		$processed = array();

		/** @var SplFileInfo[] $files */
		$files = System::getContainer()->get('contao.resource_finder')->findIn('dca')->depth(0)->files()->name('*.php');

		foreach ($files as $file)
		{
			if (\in_array($file->getBasename(), $processed))
			{
				continue;
			}

			$processed[] = $file->getBasename();

			$strTable = $file->getBasename('.php');
			$objExtract = DcaExtractor::getInstance($strTable);

			if ($objExtract->isDbTable())
			{
				$return[$strTable] = $objExtract->getDbInstallerArray();
			}
		}

		ksort($return);

		// HOOK: allow third-party developers to modify the array (see #6425)
		if (isset($GLOBALS['TL_HOOKS']['sqlGetFromDca']) && \is_array($GLOBALS['TL_HOOKS']['sqlGetFromDca']))
		{
			foreach ($GLOBALS['TL_HOOKS']['sqlGetFromDca'] as $callback)
			{
				$this->import($callback[0]);
				$return = $this->{$callback[0]}->{$callback[1]}($return);
			}
		}

		return $return;
	}

	/**
	 * Get the current database structure
	 *
	 * @return array An array of tables and fields
	 */
	public function getFromDb()
	{
		$this->import(Database::class, 'Database');
		$tables = preg_grep('/^tl_/', $this->Database->listTables(null, true));

		if (empty($tables))
		{
			return array();
		}

		$return = array();
		$quote = static function ($item) { return '`' . $item . '`'; };

		foreach ($tables as $table)
		{
			$fields = $this->Database->listFields($table, true);

			foreach ($fields as $field)
			{
				$name = $field['name'];
				$field['name'] = $quote($field['name']);

				if ($field['type'] != 'index')
				{
					unset($field['index'], $field['origtype']);

					// Field type
					if ($field['length'])
					{
						$field['type'] .= '(' . $field['length'] . ($field['precision'] ? ',' . $field['precision'] : '') . ')';

						unset($field['length'], $field['precision']);
					}

					// Variant collation
					if ($field['collation'])
					{
						$field['collation'] = 'COLLATE ' . $field['collation'];
					}
					else
					{
						unset($field['collation']);
					}

					// Default values
					if ($field['default'] === null || stripos($field['extra'], 'auto_increment') !== false || strtolower($field['null']) == 'null' || \in_array(strtolower($field['type']), array('text', 'tinytext', 'mediumtext', 'longtext', 'blob', 'tinyblob', 'mediumblob', 'longblob')))
					{
						unset($field['default']);
					}
					// Date/time constants (see #5089)
					elseif (\in_array(strtolower($field['default']), array('current_date', 'current_time', 'current_timestamp')))
					{
						$field['default'] = "default " . $field['default'];
					}
					// Everything else
					else
					{
						$field['default'] = "default '" . $field['default'] . "'";
					}

					$return[$table]['TABLE_FIELDS'][$name] = trim(implode(' ', $field));
				}

				// Indexes
				if (isset($field['index']) && $field['index_fields'])
				{
					// Quote the field names
					$index_fields = implode(
						', ',
						array_map(
							static function ($item) use ($quote)
							{
								if (strpos($item, '(') === false)
								{
									return $quote($item);
								}

								list($name, $length) = explode('(', rtrim($item, ')'));

								return $quote($name) . '(' . $length . ')';
							},
							$field['index_fields']
						)
					);

					switch ($field['index'])
					{
						case 'UNIQUE':
							if ($name == 'PRIMARY')
							{
								$return[$table]['TABLE_CREATE_DEFINITIONS'][$name] = 'PRIMARY KEY  (' . $index_fields . ')';
							}
							else
							{
								$return[$table]['TABLE_CREATE_DEFINITIONS'][$name] = 'UNIQUE KEY `' . $name . '` (' . $index_fields . ')';
							}
							break;

						case 'FULLTEXT':
							$return[$table]['TABLE_CREATE_DEFINITIONS'][$name] = 'FULLTEXT KEY `' . $name . '` (' . $index_fields . ')';
							break;

						default:
							$return[$table]['TABLE_CREATE_DEFINITIONS'][$name] = 'KEY `' . $name . '` (' . $index_fields . ')';
							break;
					}

					unset($field['index_fields'], $field['index']);
				}
			}
		}

		// HOOK: allow third-party developers to modify the array (see #3281)
		if (isset($GLOBALS['TL_HOOKS']['sqlGetFromDB']) && \is_array($GLOBALS['TL_HOOKS']['sqlGetFromDB']))
		{
			foreach ($GLOBALS['TL_HOOKS']['sqlGetFromDB'] as $callback)
			{
				$this->import($callback[0]);
				$return = $this->{$callback[0]}->{$callback[1]}($return);
			}
		}

		return $return;
	}
}
