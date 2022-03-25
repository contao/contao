<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Model;

use Contao\Database;
use Contao\DcaExtractor;

/**
 * The class reads the relation metadata from the DCA and creates the necessary
 * JOIN queries to retrieve an object from the database.
 */
class QueryBuilder
{
	/**
	 * Build a query based on the given options
	 *
	 * @param array $arrOptions The options array
	 *
	 * @return string The query string
	 */
	public static function find(array $arrOptions)
	{
		$objBase = DcaExtractor::getInstance($arrOptions['table']);

		if (!$objBase->hasRelations())
		{
			$strQuery = "SELECT * FROM " . $arrOptions['table'];
		}
		else
		{
			$arrJoins = array();
			$arrFields = array($arrOptions['table'] . ".*");
			$intCount = 0;

			foreach ($objBase->getRelations() as $strKey=>$arrConfig)
			{
				// Automatically join the single-relation records
				if (($arrConfig['load'] ?? null) == 'eager' || ($arrOptions['eager'] ?? null))
				{
					if ($arrConfig['type'] == 'hasOne' || $arrConfig['type'] == 'belongsTo')
					{
						++$intCount;
						$objRelated = DcaExtractor::getInstance($arrConfig['table']);

						foreach (array_keys($objRelated->getFields()) as $strField)
						{
							$arrFields[] = 'j' . $intCount . '.' . Database::quoteIdentifier($strField) . ' AS ' . $strKey . '__' . $strField;
						}

						$arrJoins[] = " LEFT JOIN " . $arrConfig['table'] . " j$intCount ON " . $arrOptions['table'] . "." . Database::quoteIdentifier($strKey) . "=j$intCount." . $arrConfig['field'];
					}
				}
			}

			// Generate the query
			$strQuery = "SELECT " . implode(', ', $arrFields) . " FROM " . $arrOptions['table'] . implode("", $arrJoins);
		}

		// Where condition
		if (isset($arrOptions['column']))
		{
			$strQuery .= " WHERE " . (\is_array($arrOptions['column']) ? implode(" AND ", $arrOptions['column']) : $arrOptions['table'] . '.' . Database::quoteIdentifier($arrOptions['column']) . "=?");
		}

		// Having (see #6446)
		if (isset($arrOptions['having']))
		{
			$strQuery .= " HAVING " . $arrOptions['having'];
		}

		// Order by
		if (isset($arrOptions['order']))
		{
			$strQuery .= " ORDER BY " . $arrOptions['order'];
		}

		return $strQuery;
	}

	/**
	 * Build a query based on the given options to count the number of records
	 *
	 * @param array $arrOptions The options array
	 *
	 * @return string The query string
	 */
	public static function count(array $arrOptions)
	{
		$strQuery = "SELECT COUNT(*) AS count FROM " . $arrOptions['table'];

		if (isset($arrOptions['column']))
		{
			$strQuery .= " WHERE " . (\is_array($arrOptions['column']) ? implode(" AND ", $arrOptions['column']) : $arrOptions['table'] . '.' . Database::quoteIdentifier($arrOptions['column']) . "=?");
		}

		return $strQuery;
	}
}
