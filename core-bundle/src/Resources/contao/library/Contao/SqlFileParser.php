<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

/**
 * Parses a database.sql file.
 */
class SqlFileParser
{
	/**
	 * Parse a database.sql file
	 *
	 * @param string $file The file path
	 *
	 * @return array An array of DCA table settings
	 *
	 * @throws \InvalidArgumentException If the file does not exist
	 */
	public static function parse($file)
	{
		if (!file_exists($file))
		{
			throw new \InvalidArgumentException('Invalid file ' . $file);
		}

		$table = '';
		$return = array();

		$data = file($file);

		foreach ($data as $k=>$v)
		{
			$key_name = array();
			$subpatterns = array();

			// Unset comments and empty lines
			if (preg_match('/^[#-]+/', $v) || trim($v) === '')
			{
				unset($data[$k]);
				continue;
			}

			// Store the table names
			if (preg_match('/^CREATE TABLE `([^`]+)`/i', $v, $subpatterns))
			{
				$table = $subpatterns[1];
			}
			// Get the table options
			elseif ($table && preg_match('/^\)([^;]+);/', $v, $subpatterns))
			{
				$return[$table]['TABLE_OPTIONS'] = $subpatterns[1];
				$table = '';
			}
			// Add the fields
			elseif ($table)
			{
				preg_match('/^[^`]*`([^`]+)`/', trim($v), $key_name);
				$first = preg_replace('/\s[^\n\r]+/', '', $key_name[0]);
				$key = $key_name[1];

				// Create definitions
				if (\in_array($first, array('KEY', 'PRIMARY', 'PRIMARY KEY', 'FOREIGN', 'FOREIGN KEY', 'INDEX', 'UNIQUE', 'FULLTEXT', 'CHECK')))
				{
					if (strncmp($first, 'PRIMARY', 7) === 0)
					{
						$key = 'PRIMARY';
					}

					$return[$table]['TABLE_CREATE_DEFINITIONS'][$key] = preg_replace('/,$/', '', trim($v));
				}
				else
				{
					$return[$table]['TABLE_FIELDS'][$key] = preg_replace('/,$/', '', trim($v));
				}
			}
		}

		// Ignore the table options if there is no primary key
		foreach (array_keys($return) as $table)
		{
			if (!isset($return[$table]['TABLE_CREATE_DEFINITIONS']['PRIMARY']))
			{
				unset($return[$table]['TABLE_OPTIONS']);
			}
		}

		return $return;
	}
}

class_alias(SqlFileParser::class, 'SqlFileParser');
