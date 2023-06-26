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
 * Reads and writes articles
 *
 * @property integer        $id
 * @property integer        $pid
 * @property integer        $sorting
 * @property integer        $tstamp
 * @property string         $title
 * @property string         $alias
 * @property integer        $author
 * @property string         $inColumn
 * @property boolean        $showTeaser
 * @property string         $teaserCssID
 * @property string|null    $teaser
 * @property string         $printable
 * @property string         $customTpl
 * @property boolean        $protected
 * @property string|null    $groups
 * @property string|array   $cssID
 * @property boolean        $published
 * @property string|integer $start
 * @property string|integer $stop
 *
 * @method static ArticleModel|null findById($id, array $opt=array())
 * @method static ArticleModel|null findByPk($id, array $opt=array())
 * @method static ArticleModel|null findByIdOrAlias($val, array $opt=array())
 * @method static ArticleModel|null findOneBy($col, $val, array $opt=array())
 * @method static ArticleModel|null findOneByPid($val, array $opt=array())
 * @method static ArticleModel|null findOneBySorting($val, array $opt=array())
 * @method static ArticleModel|null findOneByTstamp($val, array $opt=array())
 * @method static ArticleModel|null findOneByTitle($val, array $opt=array())
 * @method static ArticleModel|null findOneByAlias($val, array $opt=array())
 * @method static ArticleModel|null findOneByAuthor($val, array $opt=array())
 * @method static ArticleModel|null findOneByInColumn($val, array $opt=array())
 * @method static ArticleModel|null findOneByShowTeaser($val, array $opt=array())
 * @method static ArticleModel|null findOneByTeaserCssID($val, array $opt=array())
 * @method static ArticleModel|null findOneByTeaser($val, array $opt=array())
 * @method static ArticleModel|null findOneByPrintable($val, array $opt=array())
 * @method static ArticleModel|null findOneByCustomTpl($val, array $opt=array())
 * @method static ArticleModel|null findOneByProtected($val, array $opt=array())
 * @method static ArticleModel|null findOneByGroups($val, array $opt=array())
 * @method static ArticleModel|null findOneByCssID($val, array $opt=array())
 * @method static ArticleModel|null findOneBySpace($val, array $opt=array())
 * @method static ArticleModel|null findOneByPublished($val, array $opt=array())
 * @method static ArticleModel|null findOneByStart($val, array $opt=array())
 * @method static ArticleModel|null findOneByStop($val, array $opt=array())
 *
 * @method static Collection|ArticleModel[]|ArticleModel|null findByPid($val, array $opt=array())
 * @method static Collection|ArticleModel[]|ArticleModel|null findBySorting($val, array $opt=array())
 * @method static Collection|ArticleModel[]|ArticleModel|null findByTstamp($val, array $opt=array())
 * @method static Collection|ArticleModel[]|ArticleModel|null findByTitle($val, array $opt=array())
 * @method static Collection|ArticleModel[]|ArticleModel|null findByAlias($val, array $opt=array())
 * @method static Collection|ArticleModel[]|ArticleModel|null findByAuthor($val, array $opt=array())
 * @method static Collection|ArticleModel[]|ArticleModel|null findByInColumn($val, array $opt=array())
 * @method static Collection|ArticleModel[]|ArticleModel|null findByShowTeaser($val, array $opt=array())
 * @method static Collection|ArticleModel[]|ArticleModel|null findByTeaserCssID($val, array $opt=array())
 * @method static Collection|ArticleModel[]|ArticleModel|null findByTeaser($val, array $opt=array())
 * @method static Collection|ArticleModel[]|ArticleModel|null findByPrintable($val, array $opt=array())
 * @method static Collection|ArticleModel[]|ArticleModel|null findByCustomTpl($val, array $opt=array())
 * @method static Collection|ArticleModel[]|ArticleModel|null findByProtected($val, array $opt=array())
 * @method static Collection|ArticleModel[]|ArticleModel|null findByGroups($val, array $opt=array())
 * @method static Collection|ArticleModel[]|ArticleModel|null findByCssID($val, array $opt=array())
 * @method static Collection|ArticleModel[]|ArticleModel|null findBySpace($val, array $opt=array())
 * @method static Collection|ArticleModel[]|ArticleModel|null findByPublished($val, array $opt=array())
 * @method static Collection|ArticleModel[]|ArticleModel|null findByStart($val, array $opt=array())
 * @method static Collection|ArticleModel[]|ArticleModel|null findByStop($val, array $opt=array())
 * @method static Collection|ArticleModel[]|ArticleModel|null findMultipleByIds($var, array $opt=array())
 * @method static Collection|ArticleModel[]|ArticleModel|null findBy($col, $val, array $opt=array())
 * @method static Collection|ArticleModel[]|ArticleModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByPid($val, array $opt=array())
 * @method static integer countBySorting($val, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByTitle($val, array $opt=array())
 * @method static integer countByAlias($val, array $opt=array())
 * @method static integer countByAuthor($val, array $opt=array())
 * @method static integer countByInColumn($val, array $opt=array())
 * @method static integer countByShowTeaser($val, array $opt=array())
 * @method static integer countByTeaserCssID($val, array $opt=array())
 * @method static integer countByTeaser($val, array $opt=array())
 * @method static integer countByPrintable($val, array $opt=array())
 * @method static integer countByCustomTpl($val, array $opt=array())
 * @method static integer countByProtected($val, array $opt=array())
 * @method static integer countByGroups($val, array $opt=array())
 * @method static integer countByCssID($val, array $opt=array())
 * @method static integer countBySpace($val, array $opt=array())
 * @method static integer countByPublished($val, array $opt=array())
 * @method static integer countByStart($val, array $opt=array())
 * @method static integer countByStop($val, array $opt=array())
 */
class ArticleModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_article';

	/**
	 * Find an article by its ID or alias and its page
	 *
	 * @param mixed   $varId      The numeric ID or alias name
	 * @param integer $intPid     The page ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return ArticleModel|null The model or null if there is no article
	 */
	public static function findByIdOrAliasAndPid($varId, $intPid, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = !preg_match('/^[1-9]\d*$/', $varId) ? array("BINARY $t.alias=?") : array("$t.id=?");
		$arrValues = array($varId);

		if ($intPid)
		{
			$arrColumns[] = "$t.pid=?";
			$arrValues[] = $intPid;
		}

		return static::findOneBy($arrColumns, $arrValues, $arrOptions);
	}

	/**
	 * Find a published article by its ID or alias and its page
	 *
	 * @param mixed   $varId      The numeric ID or alias name
	 * @param integer $intPid     The page ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return ArticleModel|null The model or null if there is no article
	 */
	public static function findPublishedByIdOrAliasAndPid($varId, $intPid, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = !preg_match('/^[1-9]\d*$/', $varId) ? array("BINARY $t.alias=?") : array("$t.id=?");
		$arrValues = array($varId);

		if ($intPid)
		{
			$arrColumns[] = "$t.pid=?";
			$arrValues[] = $intPid;
		}

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published=1 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		return static::findOneBy($arrColumns, $arrValues, $arrOptions);
	}

	/**
	 * Find a published article by its ID
	 *
	 * @param integer $intId      The article ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return ArticleModel|null The model or null if there is no published article
	 */
	public static function findPublishedById($intId, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.id=?");

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published=1 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		return static::findOneBy($arrColumns, array($intId), $arrOptions);
	}

	/**
	 * Find all published articles by their parent ID and column
	 *
	 * @param integer $intPid     The page ID
	 * @param string  $strColumn  The column name
	 * @param array   $arrOptions An optional options array
	 *
	 * @return Collection|ArticleModel[]|ArticleModel|null A collection of models or null if there are no articles in the given column
	 */
	public static function findPublishedByPidAndColumn($intPid, $strColumn, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.pid=? AND $t.inColumn=?");
		$arrValues = array($intPid, $strColumn);

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published=1 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.sorting";
		}

		return static::findBy($arrColumns, $arrValues, $arrOptions);
	}

	/**
	 * Find all published articles with teaser by their parent ID
	 *
	 * @param integer $intPid     The page ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return Collection|ArticleModel[]|ArticleModel|null A collection of models or null if there are no articles in the given column
	 */
	public static function findPublishedWithTeaserByPid($intPid, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.pid=? AND $t.showTeaser=1");

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published=1 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.sorting";
		}

		return static::findBy($arrColumns, array($intPid), $arrOptions);
	}

	/**
	 * Find all published articles with teaser by their parent ID and column
	 *
	 * @param integer $intPid     The page ID
	 * @param string  $strColumn  The column name
	 * @param array   $arrOptions An optional options array
	 *
	 * @return Collection|ArticleModel[]|ArticleModel|null A collection of models or null if there are no articles in the given column
	 */
	public static function findPublishedWithTeaserByPidAndColumn($intPid, $strColumn, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.pid=? AND $t.inColumn=? AND $t.showTeaser=1");
		$arrValues = array($intPid, $strColumn);

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published=1 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.sorting";
		}

		return static::findBy($arrColumns, $arrValues, $arrOptions);
	}
}
