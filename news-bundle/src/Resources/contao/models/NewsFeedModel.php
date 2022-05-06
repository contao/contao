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

/**
 * Reads and writes news feeds
 *
 * @property string|integer    $id
 * @property string|integer    $tstamp
 * @property string            $title
 * @property string            $alias
 * @property string            $language
 * @property string|array|null $archives
 * @property string            $format
 * @property string            $source
 * @property string|integer    $maxItems
 * @property string            $feedBase
 * @property string|null       $description
 * @property string|integer    $imgSize
 *
 * @property string $feedName
 *
 * @method static NewsFeedModel|null findById($id, array $opt=array())
 * @method static NewsFeedModel|null findByPk($id, array $opt=array())
 * @method static NewsFeedModel|null findByIdOrAlias($val, array $opt=array())
 * @method static NewsFeedModel|null findOneBy($col, $val, array $opt=array())
 * @method static NewsFeedModel|null findOneByTstamp($val, array $opt=array())
 * @method static NewsFeedModel|null findOneByTitle($val, array $opt=array())
 * @method static NewsFeedModel|null findOneByAlias($val, array $opt=array())
 * @method static NewsFeedModel|null findOneByLanguage($val, array $opt=array())
 * @method static NewsFeedModel|null findOneByArchives($val, array $opt=array())
 * @method static NewsFeedModel|null findOneByFormat($val, array $opt=array())
 * @method static NewsFeedModel|null findOneBySource($val, array $opt=array())
 * @method static NewsFeedModel|null findOneByMaxItems($val, array $opt=array())
 * @method static NewsFeedModel|null findOneByFeedBase($val, array $opt=array())
 * @method static NewsFeedModel|null findOneByDescription($val, array $opt=array())
 *
 * @method static Collection|NewsFeedModel[]|NewsFeedModel|null findByTstamp($val, array $opt=array())
 * @method static Collection|NewsFeedModel[]|NewsFeedModel|null findByTitle($val, array $opt=array())
 * @method static Collection|NewsFeedModel[]|NewsFeedModel|null findByAlias($val, array $opt=array())
 * @method static Collection|NewsFeedModel[]|NewsFeedModel|null findByLanguage($val, array $opt=array())
 * @method static Collection|NewsFeedModel[]|NewsFeedModel|null findByArchives($val, array $opt=array())
 * @method static Collection|NewsFeedModel[]|NewsFeedModel|null findByFormat($val, array $opt=array())
 * @method static Collection|NewsFeedModel[]|NewsFeedModel|null findBySource($val, array $opt=array())
 * @method static Collection|NewsFeedModel[]|NewsFeedModel|null findByMaxItems($val, array $opt=array())
 * @method static Collection|NewsFeedModel[]|NewsFeedModel|null findByFeedBase($val, array $opt=array())
 * @method static Collection|NewsFeedModel[]|NewsFeedModel|null findByDescription($val, array $opt=array())
 * @method static Collection|NewsFeedModel[]|NewsFeedModel|null findMultipleByIds($val, array $opt=array())
 * @method static Collection|NewsFeedModel[]|NewsFeedModel|null findBy($col, $val, array $opt=array())
 * @method static Collection|NewsFeedModel[]|NewsFeedModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByTitle($val, array $opt=array())
 * @method static integer countByAlias($val, array $opt=array())
 * @method static integer countByLanguage($val, array $opt=array())
 * @method static integer countByArchives($val, array $opt=array())
 * @method static integer countByFormat($val, array $opt=array())
 * @method static integer countBySource($val, array $opt=array())
 * @method static integer countByMaxItems($val, array $opt=array())
 * @method static integer countByFeedBase($val, array $opt=array())
 * @method static integer countByDescription($val, array $opt=array())
 */
class NewsFeedModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_news_feed';

	/**
	 * Find all feeds which include a certain news-archive
	 *
	 * @param integer $intId      The news archive ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return Collection|NewsFeedModel[]|NewsFeedModel|null A collection of models or null if the news archive is not part of a feed
	 */
	public static function findByArchive($intId, array $arrOptions=array())
	{
		$t = static::$strTable;

		return static::findBy(array("$t.archives LIKE '%\"" . (int) $intId . "\"%'"), null, $arrOptions);
	}

	/**
	 * Find news feeds by their IDs
	 *
	 * @param array $arrIds     An array of news feed IDs
	 * @param array $arrOptions An optional options array
	 *
	 * @return Collection|NewsFeedModel[]|NewsFeedModel|null A collection of models or null if there are no feeds
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

class_alias(NewsFeedModel::class, 'NewsFeedModel');
