<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Exception\NoRootPageFoundException;
use Contao\Model\Collection;
use Contao\Model\Registry;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Reads and writes pages
 *
 * @property integer $id
 * @property integer $pid
 * @property integer $sorting
 * @property integer $tstamp
 * @property string  $title
 * @property string  $alias
 * @property string  $type
 * @property string  $pageTitle
 * @property string  $language
 * @property string  $robots
 * @property string  $description
 * @property string  $redirect
 * @property integer $jumpTo
 * @property string  $redirectBack
 * @property string  $url
 * @property string  $target
 * @property string  $dns
 * @property string  $staticFiles
 * @property string  $staticPlugins
 * @property string  $fallback
 * @property string  $favicon
 * @property string  $robotsTxt
 * @property string  $adminEmail
 * @property string  $dateFormat
 * @property string  $timeFormat
 * @property string  $datimFormat
 * @property string  $validAliasCharacters
 * @property string  $createSitemap
 * @property string  $sitemapName
 * @property string  $useSSL
 * @property string  $autoforward
 * @property string  $protected
 * @property string  $groups
 * @property string  $includeLayout
 * @property integer $layout
 * @property string  $includeCache
 * @property integer $cache
 * @property string  $alwaysLoadFromCache
 * @property integer $clientCache
 * @property string  $includeChmod
 * @property integer $cuser
 * @property integer $cgroup
 * @property string  $chmod
 * @property string  $noSearch
 * @property string  $requireItem
 * @property string  $cssClass
 * @property string  $sitemap
 * @property string  $hide
 * @property string  $guests
 * @property integer $tabindex
 * @property string  $accesskey
 * @property string  $published
 * @property string  $start
 * @property string  $stop
 * @property array   $trail
 * @property string  $mainAlias
 * @property string  $mainTitle
 * @property string  $mainPageTitle
 * @property string  $parentAlias
 * @property string  $parentTitle
 * @property string  $parentPageTitle
 * @property string  $folderUrl
 * @property boolean $isPublic
 * @property integer $rootId
 * @property string  $rootAlias
 * @property string  $rootTitle
 * @property string  $rootPageTitle
 * @property integer $rootSorting
 * @property string  $domain
 * @property string  $rootLanguage
 * @property boolean $rootIsPublic
 * @property boolean $rootIsFallback
 * @property boolean $rootUseSSL
 * @property string  $rootFallbackLanguage
 * @property boolean $minifyMarkup
 * @property integer $layoutId
 * @property boolean $hasJQuery
 * @property boolean $hasMooTools
 * @property string  $template
 * @property string  $templateGroup
 * @property string  $enforceTwoFactor
 * @property integer $twoFactorJumpTo
 *
 * @method static PageModel|null findById($id, array $opt=array())
 * @method static PageModel|null findByPk($id, array $opt=array())
 * @method static PageModel|null findByIdOrAlias($val, array $opt=array())
 * @method static PageModel|null findOneBy($col, $val, array $opt=array())
 * @method static PageModel|null findOneByPid($val, array $opt=array())
 * @method static PageModel|null findOneBySorting($val, array $opt=array())
 * @method static PageModel|null findOneByTstamp($val, array $opt=array())
 * @method static PageModel|null findOneByTitle($val, array $opt=array())
 * @method static PageModel|null findOneByAlias($val, array $opt=array())
 * @method static PageModel|null findOneByType($val, array $opt=array())
 * @method static PageModel|null findOneByPageTitle($val, array $opt=array())
 * @method static PageModel|null findOneByLanguage($val, array $opt=array())
 * @method static PageModel|null findOneByRobots($val, array $opt=array())
 * @method static PageModel|null findOneByDescription($val, array $opt=array())
 * @method static PageModel|null findOneByRedirect($val, array $opt=array())
 * @method static PageModel|null findOneByJumpTo($val, array $opt=array())
 * @method static PageModel|null findOneByRedirectBack($val, array $opt=array())
 * @method static PageModel|null findOneByUrl($val, array $opt=array())
 * @method static PageModel|null findOneByTarget($val, array $opt=array())
 * @method static PageModel|null findOneByDns($val, array $opt=array())
 * @method static PageModel|null findOneByStaticFiles($val, array $opt=array())
 * @method static PageModel|null findOneByStaticPlugins($val, array $opt=array())
 * @method static PageModel|null findOneByFallback($val, array $opt=array())
 * @method static PageModel|null findOneByFavicon($val, array $opt=array())
 * @method static PageModel|null findOneByRobotsTxt($val, array $opt=array())
 * @method static PageModel|null findOneByAdminEmail($val, array $opt=array())
 * @method static PageModel|null findOneByDateFormat($val, array $opt=array())
 * @method static PageModel|null findOneByTimeFormat($val, array $opt=array())
 * @method static PageModel|null findOneByDatimFormat($val, array $opt=array())
 * @method static PageModel|null findOneByCreateSitemap($val, array $opt=array())
 * @method static PageModel|null findOneBySitemapName($val, array $opt=array())
 * @method static PageModel|null findOneByUseSSL($val, array $opt=array())
 * @method static PageModel|null findOneByAutoforward($val, array $opt=array())
 * @method static PageModel|null findOneByProtected($val, array $opt=array())
 * @method static PageModel|null findOneByGroups($val, array $opt=array())
 * @method static PageModel|null findOneByIncludeLayout($val, array $opt=array())
 * @method static PageModel|null findOneByLayout($val, array $opt=array())
 * @method static PageModel|null findOneByIncludeCache($val, array $opt=array())
 * @method static PageModel|null findOneByCache($val, array $opt=array())
 * @method static PageModel|null findOneByIncludeChmod($val, array $opt=array())
 * @method static PageModel|null findOneByCuser($val, array $opt=array())
 * @method static PageModel|null findOneByCgroup($val, array $opt=array())
 * @method static PageModel|null findOneByChmod($val, array $opt=array())
 * @method static PageModel|null findOneByNoSearch($val, array $opt=array())
 * @method static PageModel|null findOneByCssClass($val, array $opt=array())
 * @method static PageModel|null findOneBySitemap($val, array $opt=array())
 * @method static PageModel|null findOneByHide($val, array $opt=array())
 * @method static PageModel|null findOneByGuests($val, array $opt=array())
 * @method static PageModel|null findOneByTabindex($val, array $opt=array())
 * @method static PageModel|null findOneByAccesskey($val, array $opt=array())
 * @method static PageModel|null findOneByPublished($val, array $opt=array())
 * @method static PageModel|null findOneByStart($val, array $opt=array())
 * @method static PageModel|null findOneByStop($val, array $opt=array())
 * @method static PageModel|null findOneByEnforceTwoFactor($val, array $opt=array())
 * @method static PageModel|null findOneByTwoFactorJumpTo($val, array $opt=array())
 *
 * @method static Collection|PageModel[]|PageModel|null findByPid($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findBySorting($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByTstamp($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByTitle($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByAlias($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByType($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByPageTitle($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByLanguage($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByRobots($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByDescription($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByRedirect($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByJumpTo($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByRedirectBack($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByUrl($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByTarget($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByDns($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByStaticFiles($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByStaticPlugins($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByFallback($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByFavicon($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByRobotsTxt($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByAdminEmail($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByDateFormat($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByTimeFormat($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByDatimFormat($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByCreateSitemap($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findBySitemapName($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByUseSSL($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByAutoforward($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByProtected($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByGroups($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByIncludeLayout($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByLayout($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByIncludeCache($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByCache($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByIncludeChmod($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByCuser($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByCgroup($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByChmod($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByNoSearch($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByCssClass($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findBySitemap($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByHide($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByGuests($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByTabindex($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByAccesskey($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByPublished($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByStart($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByStop($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByEnforceTwoFactor($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findByTwoFactorJumpTo($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findMultipleByIds($val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findBy($col, $val, array $opt=array())
 * @method static Collection|PageModel[]|PageModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByPid($val, array $opt=array())
 * @method static integer countBySorting($val, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByTitle($val, array $opt=array())
 * @method static integer countByAlias($val, array $opt=array())
 * @method static integer countByType($val, array $opt=array())
 * @method static integer countByPageTitle($val, array $opt=array())
 * @method static integer countByLanguage($val, array $opt=array())
 * @method static integer countByRobots($val, array $opt=array())
 * @method static integer countByDescription($val, array $opt=array())
 * @method static integer countByRedirect($val, array $opt=array())
 * @method static integer countByJumpTo($val, array $opt=array())
 * @method static integer countByRedirectBack($val, array $opt=array())
 * @method static integer countByUrl($val, array $opt=array())
 * @method static integer countByTarget($val, array $opt=array())
 * @method static integer countByDns($val, array $opt=array())
 * @method static integer countByStaticFiles($val, array $opt=array())
 * @method static integer countByStaticPlugins($val, array $opt=array())
 * @method static integer countByFallback($val, array $opt=array())
 * @method static integer countByFavicon($val, array $opt=array())
 * @method static integer countByRobotsTxt($val, array $opt=array())
 * @method static integer countByAdminEmail($val, array $opt=array())
 * @method static integer countByDateFormat($val, array $opt=array())
 * @method static integer countByTimeFormat($val, array $opt=array())
 * @method static integer countByDatimFormat($val, array $opt=array())
 * @method static integer countByCreateSitemap($val, array $opt=array())
 * @method static integer countBySitemapName($val, array $opt=array())
 * @method static integer countByUseSSL($val, array $opt=array())
 * @method static integer countByAutoforward($val, array $opt=array())
 * @method static integer countByProtected($val, array $opt=array())
 * @method static integer countByGroups($val, array $opt=array())
 * @method static integer countByIncludeLayout($val, array $opt=array())
 * @method static integer countByLayout($val, array $opt=array())
 * @method static integer countByIncludeCache($val, array $opt=array())
 * @method static integer countByCache($val, array $opt=array())
 * @method static integer countByIncludeChmod($val, array $opt=array())
 * @method static integer countByCuser($val, array $opt=array())
 * @method static integer countByCgroup($val, array $opt=array())
 * @method static integer countByChmod($val, array $opt=array())
 * @method static integer countByNoSearch($val, array $opt=array())
 * @method static integer countByCssClass($val, array $opt=array())
 * @method static integer countBySitemap($val, array $opt=array())
 * @method static integer countByHide($val, array $opt=array())
 * @method static integer countByGuests($val, array $opt=array())
 * @method static integer countByTabindex($val, array $opt=array())
 * @method static integer countByAccesskey($val, array $opt=array())
 * @method static integer countByPublished($val, array $opt=array())
 * @method static integer countByStart($val, array $opt=array())
 * @method static integer countByStop($val, array $opt=array())
 * @method static integer countByEnforceTwoFactor($val, array $opt=array())
 * @method static integer countByTwoFactorJumpTo($val, array $opt=array())
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PageModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_page';

	/**
	 * Details loaded
	 * @var boolean
	 */
	protected $blnDetailsLoaded = false;

	/**
	 * Find a published page by its ID
	 *
	 * @param integer $intId      The page ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return PageModel|null The model or null if there is no published page
	 */
	public static function findPublishedById($intId, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.id=?");

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')";
		}

		return static::findOneBy($arrColumns, $intId, $arrOptions);
	}

	/**
	 * Find published pages by their PID
	 *
	 * @param integer $intPid     The parent ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return Collection|PageModel[]|PageModel|null A collection of models or null if there are no pages
	 */
	public static function findPublishedByPid($intPid, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.pid=?");

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')";
		}

		return static::findBy($arrColumns, $intPid, $arrOptions);
	}

	/**
	 * Find the first published root page by its host name and language
	 *
	 * @param string $strHost     The host name
	 * @param mixed  $varLanguage An ISO language code or an array of ISO language codes
	 * @param array  $arrOptions  An optional options array
	 *
	 * @return PageModel|null The model or null if there is no matching root page
	 *
	 * @deprecated Deprecated since Contao 4.7, to be removed in Contao 5.0.
	 */
	public static function findFirstPublishedRootByHostAndLanguage($strHost, $varLanguage, array $arrOptions=array())
	{
		@trigger_error('Using PageModel::findFirstPublishedRootByHostAndLanguage() has been deprecated and will no longer work Contao 5.0.', E_USER_DEPRECATED);

		$t = static::$strTable;
		$objDatabase = Database::getInstance();

		if (\is_array($varLanguage))
		{
			$arrColumns = array("$t.type='root' AND ($t.dns=? OR $t.dns='')");

			if (!empty($varLanguage))
			{
				$arrColumns[] = "($t.language IN('" . implode("','", $varLanguage) . "') OR $t.fallback='1')";
			}
			else
			{
				$arrColumns[] = "$t.fallback='1'";
			}

			if (!isset($arrOptions['order']))
			{
				$arrOptions['order'] = "$t.dns DESC" . (!empty($varLanguage) ? ", " . $objDatabase->findInSet("$t.language", array_reverse($varLanguage)) . " DESC" : "") . ", $t.sorting";
			}

			if (!static::isPreviewMode($arrOptions))
			{
				$time = Date::floorToMinute();
				$arrColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')";
			}

			return static::findOneBy($arrColumns, $strHost, $arrOptions);
		}

		$arrColumns = array("$t.type='root' AND ($t.dns=? OR $t.dns='') AND ($t.language=? OR $t.fallback='1')");
		$arrValues = array($strHost, $varLanguage);

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.dns DESC, $t.fallback";
		}

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')";
		}

		return static::findOneBy($arrColumns, $arrValues, $arrOptions);
	}

	/**
	 * Find the first published page by its parent ID
	 *
	 * @param integer $intPid     The parent page's ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return PageModel|null The model or null if there is no published page
	 */
	public static function findFirstPublishedByPid($intPid, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.pid=? AND $t.type!='root' AND $t.type!='error_401' AND $t.type!='error_403' AND $t.type!='error_404'");

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.sorting";
		}

		return static::findOneBy($arrColumns, $intPid, $arrOptions);
	}

	/**
	 * Find the first published regular page by its parent ID
	 *
	 * @param integer $intPid     The parent page's ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return PageModel|null The model or null if there is no published regular page
	 */
	public static function findFirstPublishedRegularByPid($intPid, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.pid=? AND $t.type='regular'");

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.sorting";
		}

		return static::findOneBy($arrColumns, $intPid, $arrOptions);
	}

	/**
	 * Find an error 401 page by its parent ID
	 *
	 * @param integer $intPid     The parent page's ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return PageModel|null The model or null if there is no 401 page
	 */
	public static function find401ByPid($intPid, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.pid=? AND $t.type='error_401'");

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.sorting";
		}

		return static::findOneBy($arrColumns, $intPid, $arrOptions);
	}

	/**
	 * Find an error 403 page by its parent ID
	 *
	 * @param integer $intPid     The parent page's ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return PageModel|null The model or null if there is no 403 page
	 */
	public static function find403ByPid($intPid, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.pid=? AND $t.type='error_403'");

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.sorting";
		}

		return static::findOneBy($arrColumns, $intPid, $arrOptions);
	}

	/**
	 * Find an error 404 page by its parent ID
	 *
	 * @param integer $intPid     The parent page's ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return PageModel|null The model or null if there is no 404 page
	 */
	public static function find404ByPid($intPid, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.pid=? AND $t.type='error_404'");

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.sorting";
		}

		return static::findOneBy($arrColumns, $intPid, $arrOptions);
	}

	/**
	 * Find pages matching a list of possible alias names
	 *
	 * @param array $arrAliases An array of possible alias names
	 * @param array $arrOptions An optional options array
	 *
	 * @return Collection|PageModel[]|PageModel|null A collection of models or null if there are no pages
	 */
	public static function findByAliases($arrAliases, array $arrOptions=array())
	{
		if (empty($arrAliases) || !\is_array($arrAliases))
		{
			return null;
		}

		// Remove everything that is not an alias
		$arrAliases = array_filter(array_map(static function ($v) { return preg_match('/^[\w\/.-]+$/u', $v) ? $v : null; }, $arrAliases));

		// Return if nothing is left
		if (empty($arrAliases))
		{
			return null;
		}

		$t = static::$strTable;
		$arrColumns = array("$t.alias IN('" . implode("','", array_filter($arrAliases)) . "')");

		// Check the publication status (see #4652)
		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = Database::getInstance()->findInSet("$t.alias", $arrAliases);
		}

		return static::findBy($arrColumns, null, $arrOptions);
	}

	/**
	 * Find published pages by their ID or aliases
	 *
	 * @param mixed $varId      The numeric ID or the alias name
	 * @param array $arrOptions An optional options array
	 *
	 * @return Collection|PageModel[]|PageModel|null A collection of models or null if there are no pages
	 */
	public static function findPublishedByIdOrAlias($varId, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = !preg_match('/^[1-9]\d*$/', $varId) ? array("BINARY $t.alias=?") : array("$t.id=?");

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')";
		}

		return static::findBy($arrColumns, $varId, $arrOptions);
	}

	/**
	 * Find all published subpages by their parent ID and exclude pages only visible for guests
	 *
	 * @param integer $intPid        The parent page's ID
	 * @param boolean $blnShowHidden If true, hidden pages will be included
	 * @param boolean $blnIsSitemap  If true, the sitemap settings apply
	 *
	 * @return Collection|PageModel[]|PageModel|null A collection of models or null if there are no pages
	 *
	 * @deprecated Deprecated since Contao 4.9, to be removed in Contao 5.0. Use Module::getPublishedSubpagesWithoutGuestsByPid() instead.
	 */
	public static function findPublishedSubpagesWithoutGuestsByPid($intPid, $blnShowHidden=false, $blnIsSitemap=false)
	{
		@trigger_error('Using PageModel::findPublishedSubpagesWithoutGuestsByPid() has been deprecated and will no longer work Contao 5.0. Use Module::getPublishedSubpagesWithoutGuestsByPid() instead.', E_USER_DEPRECATED);

		$time = Date::floorToMinute();
		$tokenChecker = System::getContainer()->get('contao.security.token_checker');
		$blnFeUserLoggedIn = $tokenChecker->hasFrontendUser();
		$blnBeUserLoggedIn = $tokenChecker->hasBackendUser() && $tokenChecker->isPreviewMode();

		$objSubpages = Database::getInstance()->prepare("SELECT p1.*, (SELECT COUNT(*) FROM tl_page p2 WHERE p2.pid=p1.id AND p2.type!='root' AND p2.type!='error_401' AND p2.type!='error_403' AND p2.type!='error_404'" . (!$blnShowHidden ? ($blnIsSitemap ? " AND (p2.hide='' OR sitemap='map_always')" : " AND p2.hide=''") : "") . ($blnFeUserLoggedIn ? " AND p2.guests=''" : "") . (!$blnBeUserLoggedIn ? " AND p2.published='1' AND (p2.start='' OR p2.start<='$time') AND (p2.stop='' OR p2.stop>'$time')" : "") . ") AS subpages FROM tl_page p1 WHERE p1.pid=? AND p1.type!='root' AND p1.type!='error_401' AND p1.type!='error_403' AND p1.type!='error_404'" . (!$blnShowHidden ? ($blnIsSitemap ? " AND (p1.hide='' OR sitemap='map_always')" : " AND p1.hide=''") : "") . ($blnFeUserLoggedIn ? " AND p1.guests=''" : "") . (!$blnBeUserLoggedIn ? " AND p1.published='1' AND (p1.start='' OR p1.start<='$time') AND (p1.stop='' OR p1.stop>'$time')" : "") . " ORDER BY p1.sorting")
											  ->execute($intPid);

		if ($objSubpages->numRows < 1)
		{
			return null;
		}

		return static::createCollectionFromDbResult($objSubpages, 'tl_page');
	}

	/**
	 * Find all published regular pages by their IDs and exclude pages only visible for guests
	 *
	 * @param array $arrIds     An array of page IDs
	 * @param array $arrOptions An optional options array
	 *
	 * @return Collection|PageModel[]|PageModel|null A collection of models or null if there are no pages
	 */
	public static function findPublishedRegularWithoutGuestsByIds($arrIds, array $arrOptions=array())
	{
		if (empty($arrIds) || !\is_array($arrIds))
		{
			return null;
		}

		$t = static::$strTable;
		$arrColumns = array("$t.id IN(" . implode(',', array_map('\intval', $arrIds)) . ") AND $t.type!='error_401' AND $t.type!='error_403' AND $t.type!='error_404'");

		if (empty($arrOptions['includeRoot']))
		{
			$arrColumns[] = "$t.type!='root'";
		}

		if (System::getContainer()->get('contao.security.token_checker')->hasFrontendUser())
		{
			$arrColumns[] = "$t.guests=''";
		}

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = Database::getInstance()->findInSet("$t.id", $arrIds);
		}

		return static::findBy($arrColumns, null, $arrOptions);
	}

	/**
	 * Find all published regular pages by their parent IDs and exclude pages only visible for guests
	 *
	 * @param integer $intPid     The parent page's ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return Collection|PageModel[]|PageModel|null A collection of models or null if there are no pages
	 */
	public static function findPublishedRegularWithoutGuestsByPid($intPid, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.pid=? AND $t.type!='root' AND $t.type!='error_401' AND $t.type!='error_403' AND $t.type!='error_404'");

		if (System::getContainer()->get('contao.security.token_checker')->hasFrontendUser())
		{
			$arrColumns[] = "$t.guests=''";
		}

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.sorting";
		}

		return static::findBy($arrColumns, $intPid, $arrOptions);
	}

	/**
	 * Find the language fallback page by hostname
	 *
	 * @param string $strHost    The hostname
	 * @param array  $arrOptions An optional options array
	 *
	 * @return PageModel|Model|null The model or null if there is not fallback page
	 */
	public static function findPublishedFallbackByHostname($strHost, array $arrOptions=array())
	{
		// Try to load from the registry (see #8544)
		if (empty($arrOptions))
		{
			$objModel = Registry::getInstance()->fetch(static::$strTable, $strHost, 'contao.dns-fallback');

			if ($objModel !== null)
			{
				return $objModel;
			}
		}

		$t = static::$strTable;
		$arrColumns = array("$t.dns=? AND $t.fallback='1'");

		if (isset($arrOptions['fallbackToEmpty']) && $arrOptions['fallbackToEmpty'] === true)
		{
			$arrColumns = array("($t.dns=? OR $t.dns='') AND $t.fallback='1'");

			if (!isset($arrOptions['order']))
			{
				$arrOptions['order'] = "$t.dns DESC";
			}
		}

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')";
		}

		return static::findOneBy($arrColumns, $strHost, $arrOptions);
	}

	/**
	 * Finds the published root pages
	 *
	 * @param array $arrOptions An optional options array
	 *
	 * @return Collection|PageModel[]|PageModel|null A collection of models or null if there are no parent pages
	 */
	public static function findPublishedRootPages(array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.type='root'");

		if (isset($arrOptions['dns']))
		{
			$arrColumns = array("$t.type='root' AND $t.dns=?");
		}

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')";
		}

		return static::findBy($arrColumns, $arrOptions['dns'] ?? null, $arrOptions);
	}

	/**
	 * Find the parent pages of a page
	 *
	 * @param integer $intId The page's ID
	 *
	 * @return Collection|PageModel[]|PageModel|null A collection of models or null if there are no parent pages
	 */
	public static function findParentsById($intId)
	{
		$arrModels = array();

		while ($intId > 0 && ($objPage = static::findByPk($intId)) !== null)
		{
			$intId = $objPage->pid;
			$arrModels[] = $objPage;
		}

		if (empty($arrModels))
		{
			return null;
		}

		return static::createCollection($arrModels, 'tl_page');
	}

	/**
	 * Find the first active page by its member groups
	 *
	 * @param array $arrIds An array of member group IDs
	 *
	 * @return PageModel|null The model or null if there is no matching member group
	 */
	public static function findFirstActiveByMemberGroups($arrIds)
	{
		if (empty($arrIds) || !\is_array($arrIds))
		{
			return null;
		}

		$time = Date::floorToMinute();
		$objDatabase = Database::getInstance();
		$arrIds = array_map('\intval', $arrIds);

		$objResult = $objDatabase->prepare("SELECT p.* FROM tl_member_group g LEFT JOIN tl_page p ON g.jumpTo=p.id WHERE g.id IN(" . implode(',', $arrIds) . ") AND g.jumpTo>0 AND g.redirect='1' AND g.disable!='1' AND (g.start='' OR g.start<='$time') AND (g.stop='' OR g.stop>'$time') AND p.published='1' AND (p.start='' OR p.start<='$time') AND (p.stop='' OR p.stop>'$time') ORDER BY " . $objDatabase->findInSet('g.id', $arrIds))
								 ->limit(1)
								 ->execute();

		if ($objResult->numRows < 1)
		{
			return null;
		}

		$objRegistry = Registry::getInstance();

		/** @var PageModel|Model $objPage */
		if ($objPage = $objRegistry->fetch('tl_page', $objResult->id))
		{
			return $objPage;
		}

		return new static($objResult);
	}

	/**
	 * Find a page by its ID and return it with the inherited details
	 *
	 * @param integer $intId The page's ID
	 *
	 * @return PageModel|null The model or null if there is no matching page
	 */
	public static function findWithDetails($intId)
	{
		$objPage = static::findByPk($intId);

		if ($objPage === null)
		{
			return null;
		}

		return $objPage->loadDetails();
	}

	/**
	 * Register the contao.dns-fallback alias when the model is attached to the registry
	 *
	 * @param Registry $registry The model registry
	 */
	public function onRegister(Registry $registry)
	{
		parent::onRegister($registry);

		// Register this model as being the fallback page for a given dns
		if ($this->fallback && $this->type == 'root' && !$registry->isRegisteredAlias($this, 'contao.dns-fallback', $this->dns))
		{
			$registry->registerAlias($this, 'contao.dns-fallback', $this->dns);
		}
	}

	/**
	 * Unregister the contao.dns-fallback alias when the model is detached from the registry
	 *
	 * @param Registry $registry The model registry
	 */
	public function onUnregister(Registry $registry)
	{
		parent::onUnregister($registry);

		// Unregister the fallback page
		if ($this->fallback && $this->type == 'root' && $registry->isRegisteredAlias($this, 'contao.dns-fallback', $this->dns))
		{
			$registry->unregisterAlias($this, 'contao.dns-fallback', $this->dns);
		}
	}

	/**
	 * Get the details of a page including inherited parameters
	 *
	 * @return PageModel The page model
	 *
	 * @throws NoRootPageFoundException If no root page is found
	 */
	public function loadDetails()
	{
		// Loaded already
		if ($this->blnDetailsLoaded)
		{
			return $this;
		}

		// Set some default values
		$this->protected = (bool) $this->protected;
		$this->groups = $this->protected ? StringUtil::deserialize($this->groups) : false;
		$this->layout = ($this->includeLayout && $this->layout) ? $this->layout : false;
		$this->cache = $this->includeCache ? $this->cache : false;
		$this->alwaysLoadFromCache = $this->includeCache ? $this->alwaysLoadFromCache : false;
		$this->clientCache = $this->includeCache ? $this->clientCache : false;

		$pid = $this->pid;
		$type = $this->type;
		$alias = $this->alias;
		$name = $this->title;
		$title = $this->pageTitle ?: $this->title;
		$folderUrl = '';
		$palias = '';
		$pname = '';
		$ptitle = '';
		$trail = array($this->id, $pid);
		$time = time();

		// Inherit the settings
		if ($this->type == 'root')
		{
			$objParentPage = $this; // see #4610
		}
		else
		{
			// Load all parent pages
			$objParentPage = self::findParentsById($pid);

			if ($objParentPage !== null)
			{
				while ($pid > 0 && $type != 'root' && $objParentPage->next())
				{
					$pid = $objParentPage->pid;
					$type = $objParentPage->type;

					// Parent title
					if (!$ptitle)
					{
						$palias = $objParentPage->alias;
						$pname = $objParentPage->title;
						$ptitle = $objParentPage->pageTitle ?: $objParentPage->title;
					}

					// Page title
					if ($type != 'root')
					{
						// If $folderUrl is not yet set, use the alias of the first
						// parent page if it is not a root page (see #2129)
						if (!$folderUrl && $objParentPage->alias && $objParentPage->alias !== 'index' && $objParentPage->alias !== '/')
						{
							$folderUrl = $objParentPage->alias . '/';
						}

						$alias = $objParentPage->alias;
						$name = $objParentPage->title;
						$title = $objParentPage->pageTitle ?: $objParentPage->title;
						$trail[] = $objParentPage->pid;
					}

					// Cache
					if ($objParentPage->includeCache)
					{
						$this->cache = $this->cache !== false ? $this->cache : $objParentPage->cache;
						$this->alwaysLoadFromCache = $this->alwaysLoadFromCache !== false ? $this->alwaysLoadFromCache : $objParentPage->alwaysLoadFromCache;
						$this->clientCache = $this->clientCache !== false ? $this->clientCache : $objParentPage->clientCache;
					}

					// Layout
					if ($objParentPage->includeLayout && $this->layout === false)
					{
						$this->layout = $objParentPage->layout;
					}

					// Protection
					if ($objParentPage->protected && $this->protected === false)
					{
						$this->protected = true;
						$this->groups = StringUtil::deserialize($objParentPage->groups);
					}
				}
			}

			// Set the titles
			$this->mainAlias = $alias;
			$this->mainTitle = $name;
			$this->mainPageTitle = $title;
			$this->parentAlias = $palias;
			$this->parentTitle = $pname;
			$this->parentPageTitle = $ptitle;
			$this->folderUrl = $folderUrl;
		}

		// Set the root ID and title
		if ($objParentPage !== null && $objParentPage->type == 'root')
		{
			$this->rootId = $objParentPage->id;
			$this->rootAlias = $objParentPage->alias;
			$this->rootTitle = $objParentPage->title;
			$this->rootPageTitle = $objParentPage->pageTitle ?: $objParentPage->title;
			$this->rootSorting = $objParentPage->sorting;
			$this->domain = $objParentPage->dns;
			$this->rootLanguage = $objParentPage->language;
			$this->language = $objParentPage->language;
			$this->staticFiles = $objParentPage->staticFiles;
			$this->staticPlugins = $objParentPage->staticPlugins;
			$this->dateFormat = $objParentPage->dateFormat;
			$this->timeFormat = $objParentPage->timeFormat;
			$this->datimFormat = $objParentPage->datimFormat;
			$this->validAliasCharacters = $objParentPage->validAliasCharacters;
			$this->adminEmail = $objParentPage->adminEmail;
			$this->enforceTwoFactor = $objParentPage->enforceTwoFactor;
			$this->twoFactorJumpTo = $objParentPage->twoFactorJumpTo;

			// Store whether the root page has been published
			$this->rootIsPublic = ($objParentPage->published && (!$objParentPage->start || $objParentPage->start <= $time) && (!$objParentPage->stop || $objParentPage->stop > $time));
			$this->rootIsFallback = (bool) $objParentPage->fallback;
			$this->rootUseSSL = $objParentPage->useSSL;
			$this->rootFallbackLanguage = $objParentPage->language;

			// Store the fallback language (see #6874)
			if (!$objParentPage->fallback)
			{
				$this->rootFallbackLanguage = null;

				$objFallback = static::findPublishedFallbackByHostname($objParentPage->dns);

				if ($objFallback !== null)
				{
					$this->rootFallbackLanguage = $objFallback->language;
				}
			}
		}

		// No root page found
		elseif (TL_MODE == 'FE' && $this->type != 'root')
		{
			System::log('Page ID "' . $this->id . '" does not belong to a root page', __METHOD__, TL_ERROR);

			throw new NoRootPageFoundException('No root page found');
		}

		$this->trail = array_reverse($trail);

		// Use the global date format if none is set (see #6104)
		if (!$this->dateFormat)
		{
			$this->dateFormat = Config::get('dateFormat');
		}

		if (!$this->timeFormat)
		{
			$this->timeFormat = Config::get('timeFormat');
		}

		if (!$this->datimFormat)
		{
			$this->datimFormat = Config::get('datimFormat');
		}

		$this->isPublic = ($this->published && (!$this->start || $this->start <= $time) && (!$this->stop || $this->stop > $time));

		// HOOK: add custom logic
		if (!empty($GLOBALS['TL_HOOKS']['loadPageDetails']) && \is_array($GLOBALS['TL_HOOKS']['loadPageDetails']))
		{
			$parentModels = array();

			if ($objParentPage instanceof Collection)
			{
				$parentModels = $objParentPage->getModels();
			}

			foreach ($GLOBALS['TL_HOOKS']['loadPageDetails'] as $callback)
			{
				System::importStatic($callback[0])->{$callback[1]}($parentModels, $this);
			}
		}

		// Prevent saving (see #6506 and #7199)
		$this->preventSaving();
		$this->blnDetailsLoaded = true;

		return $this;
	}

	/**
	 * Generate a front end URL
	 *
	 * @param string $strParams    An optional string of URL parameters
	 * @param string $strForceLang Force a certain language
	 *
	 * @return string An URL that can be used in the front end
	 */
	public function getFrontendUrl($strParams=null, $strForceLang=null)
	{
		if ($strForceLang !== null)
		{
			@trigger_error('Using PageModel::getFrontendUrl() with $strForceLang has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);
		}

		$this->loadDetails();

		$objUrlGenerator = System::getContainer()->get('contao.routing.url_generator');

		$strUrl = $objUrlGenerator->generate
		(
			($this->alias ?: $this->id) . $strParams,
			array
			(
				'_locale' => ($strForceLang ?: $this->rootLanguage),
				'_domain' => $this->domain,
				'_ssl' => (bool) $this->rootUseSSL,
			)
		);

		// Make the URL relative to the base path
		if (0 === strncmp($strUrl, '/', 1))
		{
			$strUrl = substr($strUrl, \strlen(Environment::get('path')) + 1);
		}

		return $this->applyLegacyLogic($strUrl, $strParams);
	}

	/**
	 * Generate an absolute URL depending on the current rewriteURL setting
	 *
	 * @param string $strParams An optional string of URL parameters
	 *
	 * @return string An absolute URL that can be used in the front end
	 */
	public function getAbsoluteUrl($strParams=null)
	{
		$this->loadDetails();

		$objUrlGenerator = System::getContainer()->get('contao.routing.url_generator');

		$strUrl = $objUrlGenerator->generate
		(
			($this->alias ?: $this->id) . $strParams,
			array
			(
				'_locale' => $this->rootLanguage,
				'_domain' => $this->domain,
				'_ssl' => (bool) $this->rootUseSSL,
			),
			UrlGeneratorInterface::ABSOLUTE_URL
		);

		return $this->applyLegacyLogic($strUrl, $strParams);
	}

	/**
	 * Generate the front end preview URL
	 *
	 * @param string $strParams An optional string of URL parameters
	 *
	 * @return string The front end preview URL
	 */
	public function getPreviewUrl($strParams=null)
	{
		$container = System::getContainer();

		if (!$previewScript = $container->getParameter('contao.preview_script'))
		{
			return $this->getAbsoluteUrl($strParams);
		}

		$context = $container->get('router')->getContext();
		$baseUrl = $context->getBaseUrl();

		// Add the preview script
		$context->setBaseUrl($previewScript);

		$objUrlGenerator = $container->get('contao.routing.url_generator');

		$strUrl = $objUrlGenerator->generate
		(
			($this->alias ?: $this->id) . $strParams,
			array
			(
				'_locale' => $this->rootLanguage,
				'_domain' => $this->domain,
				'_ssl' => (bool) $this->rootUseSSL,
			),
			UrlGeneratorInterface::ABSOLUTE_URL
		);

		$context->setBaseUrl($baseUrl);

		return $this->applyLegacyLogic($strUrl, $strParams);
	}

	/**
	 * Return the slug options
	 *
	 * @return array The slug options
	 */
	public function getSlugOptions()
	{
		$slugOptions = array('locale'=>$this->language);

		if ($this->validAliasCharacters)
		{
			$slugOptions['validChars'] = $this->validAliasCharacters;
		}

		return $slugOptions;
	}

	/**
	 * Modifies a URL from the URL generator.
	 *
	 * @param string      $strUrl
	 * @param string|null $strParams
	 *
	 * @return string
	 */
	private function applyLegacyLogic($strUrl, $strParams)
	{
		// Decode sprintf placeholders
		if ($strParams !== null && strpos($strParams, '%') !== false)
		{
			@trigger_error('Using sprintf placeholders in URLs has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);

			$arrMatches = array();
			preg_match_all('/%([sducoxXbgGeEfF])/', $strParams, $arrMatches);

			foreach (array_unique($arrMatches[1]) as $v)
			{
				$strUrl = str_replace('%25' . $v, '%' . $v, $strUrl);
			}
		}

		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['generateFrontendUrl']) && \is_array($GLOBALS['TL_HOOKS']['generateFrontendUrl']))
		{
			@trigger_error('Using the "generateFrontendUrl" hook has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);

			foreach ($GLOBALS['TL_HOOKS']['generateFrontendUrl'] as $callback)
			{
				$strUrl = System::importStatic($callback[0])->{$callback[1]}($this->row(), $strParams ?? '', $strUrl);
			}

			return $strUrl;
		}

		return $strUrl;
	}
}

class_alias(PageModel::class, 'PageModel');
