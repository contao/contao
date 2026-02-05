<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\Model\Collection;

trigger_deprecation('contao/core-bundle', '5.6', 'Using the "%s" class is deprecated and will no longer work in Contao 6.', CalendarFeedModel::class);

/**
 * Reads and writes calendar feeds
 *
 * @property integer           $id
 * @property integer           $tstamp
 * @property string            $title
 * @property string            $alias
 * @property string            $language
 * @property string|array|null $calendars
 * @property string            $format
 * @property string            $source
 * @property integer           $maxItems
 * @property string            $feedBase
 * @property string|null       $description
 * @property string|integer    $imgSize
 *
 * @property string $feedName
 *
 * @method static CalendarFeedModel|null findById($id, array $opt=array())
 * @method static CalendarFeedModel|null findByPk($id, array $opt=array())
 * @method static CalendarFeedModel|null findByIdOrAlias($val, array $opt=array())
 * @method static CalendarFeedModel|null findByAlias($val, array $opt=array())
 * @method static CalendarFeedModel|null findOneBy($col, $val, array $opt=array())
 * @method static CalendarFeedModel|null findOneByTstamp($val, array $opt=array())
 * @method static CalendarFeedModel|null findOneByTitle($val, array $opt=array())
 * @method static CalendarFeedModel|null findOneByAlias($val, array $opt=array())
 * @method static CalendarFeedModel|null findOneByLanguage($val, array $opt=array())
 * @method static CalendarFeedModel|null findOneByCalendars($val, array $opt=array())
 * @method static CalendarFeedModel|null findOneByFormat($val, array $opt=array())
 * @method static CalendarFeedModel|null findOneBySource($val, array $opt=array())
 * @method static CalendarFeedModel|null findOneByMaxItems($val, array $opt=array())
 * @method static CalendarFeedModel|null findOneByFeedBase($val, array $opt=array())
 * @method static CalendarFeedModel|null findOneByDescription($val, array $opt=array())
 *
 * @method static Collection<CalendarFeedModel>|null findByTstamp($val, array $opt=array())
 * @method static Collection<CalendarFeedModel>|null findByTitle($val, array $opt=array())
 * @method static Collection<CalendarFeedModel>|null findByLanguage($val, array $opt=array())
 * @method static Collection<CalendarFeedModel>|null findByCalendars($val, array $opt=array())
 * @method static Collection<CalendarFeedModel>|null findByFormat($val, array $opt=array())
 * @method static Collection<CalendarFeedModel>|null findBySource($val, array $opt=array())
 * @method static Collection<CalendarFeedModel>|null findByMaxItems($val, array $opt=array())
 * @method static Collection<CalendarFeedModel>|null findByFeedBase($val, array $opt=array())
 * @method static Collection<CalendarFeedModel>|null findByDescription($val, array $opt=array())
 * @method static Collection<CalendarFeedModel>|null findMultipleByIds($val, array $opt=array())
 * @method static Collection<CalendarFeedModel>|null findBy($col, $val, array $opt=array())
 * @method static Collection<CalendarFeedModel>|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByTitle($val, array $opt=array())
 * @method static integer countByAlias($val, array $opt=array())
 * @method static integer countByLanguage($val, array $opt=array())
 * @method static integer countByCalendars($val, array $opt=array())
 * @method static integer countByFormat($val, array $opt=array())
 * @method static integer countBySource($val, array $opt=array())
 * @method static integer countByMaxItems($val, array $opt=array())
 * @method static integer countByFeedBase($val, array $opt=array())
 * @method static integer countByDescription($val, array $opt=array())
 *
 * @deprecated Deprecated since Contao 5.6, to be removed in Contao 6.
 */
class CalendarFeedModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_calendar_feed';

	/**
	 * Find all feeds which include a certain calendar
	 *
	 * @param integer $intId      The calendar ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return Collection<CalendarFeedModel>|null A collection of models or null if the calendar is not part of a feed
	 */
	public static function findByCalendar($intId, array $arrOptions=array())
	{
		$t = static::$strTable;

		return static::findBy(array("$t.calendars LIKE '%\"" . (int) $intId . "\"%'"), null, $arrOptions);
	}

	/**
	 * Find calendar feeds by their IDs
	 *
	 * @param array $arrIds     An array of calendar feed IDs
	 * @param array $arrOptions An optional options array
	 *
	 * @return Collection<CalendarFeedModel>|null A collection of models or null if there are no feeds
	 */
	public static function findByIds($arrIds, array $arrOptions=array())
	{
		if (empty($arrIds) || !\is_array($arrIds))
		{
			return null;
		}

		$t = static::$strTable;

		return static::findBy(array("$t.id IN(" . implode(',', array_map('\intval', $arrIds)) . ")"), null, $arrOptions);
	}
}
