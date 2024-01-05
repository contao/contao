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
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\Model\Collection;
use Contao\Model\Registry;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Reads and writes pages
 *
 * @property integer           $id
 * @property integer           $pid
 * @property integer           $sorting
 * @property integer           $tstamp
 * @property string            $title
 * @property string            $alias
 * @property string            $type
 * @property integer           $routePriority
 * @property string            $pageTitle
 * @property string            $language
 * @property string            $robots
 * @property string|null       $description
 * @property string            $redirect
 * @property boolean           $alwaysForward
 * @property integer           $jumpTo
 * @property boolean           $redirectBack
 * @property string            $url
 * @property boolean           $target
 * @property string            $dns
 * @property string            $staticFiles
 * @property string            $staticPlugins
 * @property boolean           $fallback
 * @property boolean           $disableLanguageRedirect
 * @property boolean           $maintenanceMode
 * @property string|null       $favicon
 * @property string|null       $robotsTxt
 * @property string            $mailerTransport
 * @property boolean           $enableCanonical
 * @property string            $canonicalLink
 * @property string            $canonicalKeepParams
 * @property string            $adminEmail
 * @property string            $dateFormat
 * @property string            $timeFormat
 * @property string            $datimFormat
 * @property string            $validAliasCharacters
 * @property boolean           $useFolderUrl
 * @property string            $urlPrefix
 * @property string            $urlSuffix
 * @property boolean           $useSSL
 * @property boolean           $autoforward
 * @property boolean           $protected
 * @property string|array|null $groups
 * @property boolean           $includeLayout
 * @property integer           $layout
 * @property integer           $subpageLayout
 * @property boolean           $includeCache
 * @property integer           $cache
 * @property boolean           $alwaysLoadFromCache
 * @property integer           $clientCache
 * @property boolean           $includeChmod
 * @property integer           $cuser
 * @property integer           $cgroup
 * @property string            $chmod
 * @property boolean           $noSearch
 * @property boolean           $requireItem
 * @property string            $cssClass
 * @property string            $sitemap
 * @property boolean           $hide
 * @property string            $accesskey
 * @property boolean           $published
 * @property string|integer    $start
 * @property string|integer    $stop
 * @property boolean           $enforceTwoFactor
 * @property integer           $twoFactorJumpTo
 *
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
 * @method static PageModel|null findOneByRoutePriority($val, array $opt=array())
 * @method static PageModel|null findOneByPageTitle($val, array $opt=array())
 * @method static PageModel|null findOneByLanguage($val, array $opt=array())
 * @method static PageModel|null findOneByRobots($val, array $opt=array())
 * @method static PageModel|null findOneByDescription($val, array $opt=array())
 * @method static PageModel|null findOneByRedirect($val, array $opt=array())
 * @method static PageModel|null findOneByAlwaysForward($val, array $opt=array())
 * @method static PageModel|null findOneByJumpTo($val, array $opt=array())
 * @method static PageModel|null findOneByRedirectBack($val, array $opt=array())
 * @method static PageModel|null findOneByUrl($val, array $opt=array())
 * @method static PageModel|null findOneByTarget($val, array $opt=array())
 * @method static PageModel|null findOneByDns($val, array $opt=array())
 * @method static PageModel|null findOneByStaticFiles($val, array $opt=array())
 * @method static PageModel|null findOneByStaticPlugins($val, array $opt=array())
 * @method static PageModel|null findOneByFallback($val, array $opt=array())
 * @method static PageModel|null findOneByDisableLanguageRedirect($val, array $opt=array())
 * @method static PageModel|null findOneByFavicon($val, array $opt=array())
 * @method static PageModel|null findOneByRobotsTxt($val, array $opt=array())
 * @method static PageModel|null findOneByMailerTransport($val, array $opt=array())
 * @method static PageModel|null findOneByEnableCanonical($val, array $opt=array())
 * @method static PageModel|null findOneByCanonicalLink($val, array $opt=array())
 * @method static PageModel|null findOneByCanonicalKeepParams($val, array $opt=array())
 * @method static PageModel|null findOneByAdminEmail($val, array $opt=array())
 * @method static PageModel|null findOneByDateFormat($val, array $opt=array())
 * @method static PageModel|null findOneByTimeFormat($val, array $opt=array())
 * @method static PageModel|null findOneByDatimFormat($val, array $opt=array())
 * @method static PageModel|null findOneByValidAliasCharacters($val, array $opt=array())
 * @method static PageModel|null findOneByUseFolderUrl($val, array $opt=array())
 * @method static PageModel|null findOneByUrlPrefix($val, array $opt=array())
 * @method static PageModel|null findOneByUrlSuffix($val, array $opt=array())
 * @method static PageModel|null findOneByUseSSL($val, array $opt=array())
 * @method static PageModel|null findOneByAutoforward($val, array $opt=array())
 * @method static PageModel|null findOneByProtected($val, array $opt=array())
 * @method static PageModel|null findOneByGroups($val, array $opt=array())
 * @method static PageModel|null findOneByIncludeLayout($val, array $opt=array())
 * @method static PageModel|null findOneByLayout($val, array $opt=array())
 * @method static PageModel|null findOneBySubpageLayout($val, array $opt=array())
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
 * @method static PageModel|null findOneByAccesskey($val, array $opt=array())
 * @method static PageModel|null findOneByPublished($val, array $opt=array())
 * @method static PageModel|null findOneByStart($val, array $opt=array())
 * @method static PageModel|null findOneByStop($val, array $opt=array())
 * @method static PageModel|null findOneByEnforceTwoFactor($val, array $opt=array())
 * @method static PageModel|null findOneByTwoFactorJumpTo($val, array $opt=array())
 *
 * @method static Collection<PageModel>|PageModel[]|null findByPid($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findBySorting($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByTstamp($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByTitle($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByAlias($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByType($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByRoutePriority($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByPageTitle($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByLanguage($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByRobots($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByDescription($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByRedirect($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByAlwaysForward($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByJumpTo($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByRedirectBack($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByUrl($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByTarget($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByDns($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByStaticFiles($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByStaticPlugins($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByFallback($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByDisableLanguageRedirect($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByFavicon($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByRobotsTxt($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByMailerTransport($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByEnableCanonical($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByCanonicalLink($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByCanonicalKeepParams($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByAdminEmail($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByDateFormat($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByTimeFormat($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByDatimFormat($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByValidAliasCharacters($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByUseFolderUrl($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByUrlPrefix($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByUrlSuffix($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByUseSSL($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByAutoforward($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByProtected($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByGroups($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByIncludeLayout($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByLayout($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findBySubpageLayout($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByIncludeCache($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByCache($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByIncludeChmod($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByCuser($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByCgroup($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByChmod($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByNoSearch($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByCssClass($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findBySitemap($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByHide($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByAccesskey($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByPublished($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByStart($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByStop($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByEnforceTwoFactor($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findByTwoFactorJumpTo($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findMultipleByIds($val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findBy($col, $val, array $opt=array())
 * @method static Collection<PageModel>|PageModel[]|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByPid($val, array $opt=array())
 * @method static integer countBySorting($val, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByTitle($val, array $opt=array())
 * @method static integer countByAlias($val, array $opt=array())
 * @method static integer countByType($val, array $opt=array())
 * @method static integer countByRoutePriority($val, array $opt=array())
 * @method static integer countByPageTitle($val, array $opt=array())
 * @method static integer countByLanguage($val, array $opt=array())
 * @method static integer countByRobots($val, array $opt=array())
 * @method static integer countByDescription($val, array $opt=array())
 * @method static integer countByRedirect($val, array $opt=array())
 * @method static integer countByAlwaysForward($val, array $opt=array())
 * @method static integer countByJumpTo($val, array $opt=array())
 * @method static integer countByRedirectBack($val, array $opt=array())
 * @method static integer countByUrl($val, array $opt=array())
 * @method static integer countByTarget($val, array $opt=array())
 * @method static integer countByDns($val, array $opt=array())
 * @method static integer countByStaticFiles($val, array $opt=array())
 * @method static integer countByStaticPlugins($val, array $opt=array())
 * @method static integer countByFallback($val, array $opt=array())
 * @method static integer countByDisableLanguageRedirect($val, array $opt=array())
 * @method static integer countByFavicon($val, array $opt=array())
 * @method static integer countByRobotsTxt($val, array $opt=array())
 * @method static integer countByMailerTransport($val, array $opt=array())
 * @method static integer countByEnableCanonical($val, array $opt=array())
 * @method static integer countByCanonicalLink($val, array $opt=array())
 * @method static integer countByCanonicalKeepParams($val, array $opt=array())
 * @method static integer countByAdminEmail($val, array $opt=array())
 * @method static integer countByDateFormat($val, array $opt=array())
 * @method static integer countByTimeFormat($val, array $opt=array())
 * @method static integer countByDatimFormat($val, array $opt=array())
 * @method static integer countByValidAliasCharacters($val, array $opt=array())
 * @method static integer countByUseFolderUrl($val, array $opt=array())
 * @method static integer countByUrlPrefix($val, array $opt=array())
 * @method static integer countByUrlSuffix($val, array $opt=array())
 * @method static integer countByUseSSL($val, array $opt=array())
 * @method static integer countByAutoforward($val, array $opt=array())
 * @method static integer countByProtected($val, array $opt=array())
 * @method static integer countByGroups($val, array $opt=array())
 * @method static integer countByIncludeLayout($val, array $opt=array())
 * @method static integer countByLayout($val, array $opt=array())
 * @method static integer countBySubpageLayout($val, array $opt=array())
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
 * @method static integer countByAccesskey($val, array $opt=array())
 * @method static integer countByPublished($val, array $opt=array())
 * @method static integer countByStart($val, array $opt=array())
 * @method static integer countByStop($val, array $opt=array())
 * @method static integer countByEnforceTwoFactor($val, array $opt=array())
 * @method static integer countByTwoFactorJumpTo($val, array $opt=array())
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

	private static array|null $prefixes = null;

	private static array|null $suffixes = null;

	public static function reset()
	{
		self::$prefixes = null;
		self::$suffixes = null;
	}

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
			$arrColumns[] = "$t.published=1 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		return static::findOneBy($arrColumns, array($intId), $arrOptions);
	}

	/**
	 * Find published pages by their PID
	 *
	 * @param integer $intPid     The parent ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return Collection<PageModel>|PageModel[]|null A collection of models or null if there are no pages
	 */
	public static function findPublishedByPid($intPid, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.pid=?");

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published=1 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		return static::findBy($arrColumns, array($intPid), $arrOptions);
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
		$unroutableTypes = System::getContainer()->get('contao.routing.page_registry')->getUnroutableTypes();
		$arrColumns = array("$t.pid=? AND $t.type!='root' AND $t.type NOT IN ('" . implode("', '", $unroutableTypes) . "')");

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published=1 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.sorting";
		}

		return static::findOneBy($arrColumns, array($intPid), $arrOptions);
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
			$arrColumns[] = "$t.published=1 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.sorting";
		}

		return static::findOneBy($arrColumns, array($intPid), $arrOptions);
	}

	/**
	 * Find the first published page by its type and parent ID
	 *
	 * @param string  $strType    The page type
	 * @param integer $intPid     The parent page's ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return PageModel|null The model or null if there is no published regular page
	 */
	public static function findFirstPublishedByTypeAndPid($strType, $intPid, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.pid=? AND $t.type=?");

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published=1 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.sorting";
		}

		return static::findOneBy($arrColumns, array($intPid, $strType), $arrOptions);
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
			$arrColumns[] = "$t.published=1 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.sorting";
		}

		return static::findOneBy($arrColumns, array($intPid), $arrOptions);
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
			$arrColumns[] = "$t.published=1 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.sorting";
		}

		return static::findOneBy($arrColumns, array($intPid), $arrOptions);
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
			$arrColumns[] = "$t.published=1 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.sorting";
		}

		return static::findOneBy($arrColumns, array($intPid), $arrOptions);
	}

	/**
	 * Find pages matching a list of possible alias names
	 *
	 * @param array $arrAliases An array of possible alias names
	 * @param array $arrOptions An optional options array
	 *
	 * @return Collection<PageModel>|PageModel[]|null A collection of models or null if there are no pages
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
			$arrColumns[] = "$t.published=1 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = Database::getInstance()->findInSet("$t.alias", $arrAliases);
		}

		return static::findBy($arrColumns, null, $arrOptions);
	}

	/**
	 * Find pages that have a similar alias
	 *
	 * @return Collection<PageModel>|PageModel[]|null A collection of models or null if there are no pages
	 */
	public static function findSimilarByAlias(self $pageModel)
	{
		if ('' === $pageModel->alias)
		{
			return null;
		}

		$pageModel->loadDetails();

		$t = static::$strTable;
		$alias = '%' . self::stripPrefixesAndSuffixes($pageModel->alias, $pageModel->urlPrefix, $pageModel->urlSuffix) . '%';

		return static::findBy(array("$t.alias LIKE ?", "$t.id!=?"), array($alias, $pageModel->id));
	}

	/**
	 * Find published pages by their ID or aliases
	 *
	 * @param mixed $varId      The numeric ID or the alias name
	 * @param array $arrOptions An optional options array
	 *
	 * @return Collection<PageModel>|PageModel[]|null A collection of models or null if there are no pages
	 */
	public static function findPublishedByIdOrAlias($varId, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = !preg_match('/^[1-9]\d*$/', $varId) ? array("BINARY $t.alias=?") : array("$t.id=?");

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published=1 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		return static::findBy($arrColumns, array($varId), $arrOptions);
	}

	/**
	 * Find all published regular pages by their IDs
	 *
	 * @param array $arrIds     An array of page IDs
	 * @param array $arrOptions An optional options array
	 *
	 * @return Collection<PageModel>|PageModel[]|null A collection of models or null if there are no pages
	 */
	public static function findPublishedRegularByIds($arrIds, array $arrOptions=array())
	{
		if (empty($arrIds) || !\is_array($arrIds))
		{
			return null;
		}

		$t = static::$strTable;
		$unroutableTypes = System::getContainer()->get('contao.routing.page_registry')->getUnroutableTypes();
		$arrColumns = array("$t.id IN(" . implode(',', array_map('\intval', $arrIds)) . ") AND $t.type NOT IN ('" . implode("', '", $unroutableTypes) . "')");

		if (empty($arrOptions['includeRoot']))
		{
			$arrColumns[] = "$t.type!='root'";
		}

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published=1 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = Database::getInstance()->findInSet("$t.id", $arrIds);
		}

		return static::findBy($arrColumns, null, $arrOptions);
	}

	/**
	 * Find all published regular pages by their parent IDs
	 *
	 * @param integer $intPid     The parent page's ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return Collection<PageModel>|PageModel[]|null A collection of models or null if there are no pages
	 */
	public static function findPublishedRegularByPid($intPid, array $arrOptions=array())
	{
		$t = static::$strTable;
		$unroutableTypes = System::getContainer()->get('contao.routing.page_registry')->getUnroutableTypes();
		$arrColumns = array("$t.pid=? AND $t.type!='root' AND $t.type NOT IN ('" . implode("', '", $unroutableTypes) . "')");

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
	 * Find the language fallback page by hostname
	 *
	 * @param string $strHost    The hostname
	 * @param array  $arrOptions An optional options array
	 *
	 * @return PageModel|null The model or null if there is no fallback page
	 */
	public static function findPublishedFallbackByHostname($strHost, array $arrOptions=array())
	{
		// Try to load from the registry (see #8544)
		if (empty($arrOptions))
		{
			/** @var PageModel|null $objModel */
			$objModel = Registry::getInstance()->fetch(static::$strTable, $strHost, 'contao.dns-fallback');

			if ($objModel !== null)
			{
				return $objModel;
			}
		}

		$t = static::$strTable;
		$arrColumns = array("$t.type='root' AND $t.dns=? AND $t.fallback=1");

		if (isset($arrOptions['fallbackToEmpty']) && $arrOptions['fallbackToEmpty'] === true)
		{
			$arrColumns = array("$t.type='root' AND ($t.dns=? OR $t.dns='') AND $t.fallback=1");

			if (!isset($arrOptions['order']))
			{
				$arrOptions['order'] = "$t.dns DESC";
			}
		}

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published=1 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		return static::findOneBy($arrColumns, array($strHost), $arrOptions);
	}

	/**
	 * Finds the published root pages
	 *
	 * @param array $arrOptions An optional options array
	 *
	 * @return Collection<PageModel>|PageModel[]|null A collection of models or null if there are no parent pages
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
			$arrColumns[] = "$t.published=1 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		return static::findBy($arrColumns, $arrOptions['dns'] ?? null, $arrOptions);
	}

	/**
	 * Find the parent pages of a page
	 *
	 * @param integer $intId The page's ID
	 *
	 * @return Collection<PageModel>|PageModel[]|null A collection of models or null if there are no parent pages
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

		$objResult = $objDatabase->prepare("SELECT p.* FROM tl_member_group g LEFT JOIN tl_page p ON g.jumpTo=p.id WHERE g.id IN(" . implode(',', $arrIds) . ") AND g.jumpTo>0 AND g.redirect=1 AND g.disable=0 AND (g.start='' OR g.start<=$time) AND (g.stop='' OR g.stop>$time) AND p.published=1 AND (p.start='' OR p.start<=$time) AND (p.stop='' OR p.stop>$time) ORDER BY " . $objDatabase->findInSet('g.id', $arrIds))
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
	 * @param integer|string $intId The page's ID
	 *
	 * @return PageModel|null The model or null if there is no matching page
	 */
	public static function findWithDetails($intId)
	{
		return static::findByPk($intId)?->loadDetails();
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
		$this->groups = $this->protected ? StringUtil::deserialize($this->groups, true) : array();
		$this->layout = ($this->includeLayout && $this->layout) ? $this->layout : 0;
		$this->cache = $this->includeCache ? $this->cache : 0;
		$this->alwaysLoadFromCache = $this->includeCache ? $this->alwaysLoadFromCache : false;
		$this->clientCache = $this->includeCache ? $this->clientCache : 0;

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
					if ($objParentPage->includeCache && !$this->includeCache)
					{
						$this->cache = $objParentPage->cache;
						$this->alwaysLoadFromCache = $objParentPage->alwaysLoadFromCache;
						$this->clientCache = $objParentPage->clientCache;
					}

					// Layout
					if ($objParentPage->includeLayout && $this->layout === 0)
					{
						$this->layout = $objParentPage->subpageLayout ?: $objParentPage->layout;
					}

					// Protection
					if ($objParentPage->protected && $this->protected === false)
					{
						$this->protected = true;
						$this->groups = StringUtil::deserialize($objParentPage->groups, true);
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
			$this->urlPrefix = $objParentPage->urlPrefix;
			$this->urlSuffix = $objParentPage->urlSuffix;
			$this->disableLanguageRedirect = $objParentPage->disableLanguageRedirect;
			$this->adminEmail = $objParentPage->adminEmail;
			$this->enforceTwoFactor = $objParentPage->enforceTwoFactor;
			$this->twoFactorJumpTo = $objParentPage->twoFactorJumpTo;
			$this->useFolderUrl = $objParentPage->useFolderUrl;
			$this->mailerTransport = $objParentPage->mailerTransport;
			$this->enableCanonical = $objParentPage->enableCanonical;
			$this->maintenanceMode = $objParentPage->maintenanceMode;

			// Store whether the root page has been published
			$this->rootIsPublic = $objParentPage->published && (!$objParentPage->start || $objParentPage->start <= $time) && (!$objParentPage->stop || $objParentPage->stop > $time);
			$this->rootIsFallback = $objParentPage->fallback;
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
		elseif ($this->type != 'root')
		{
			$request = System::getContainer()->get('request_stack')->getCurrentRequest();
			$isFrontend = $request && System::getContainer()->get('contao.routing.scope_matcher')->isFrontendRequest($request);

			if ($isFrontend)
			{
				System::getContainer()->get('monolog.logger.contao.error')->error('Page ID "' . $this->id . '" does not belong to a root page');

				throw new NoRootPageFoundException('No root page found');
			}
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

		$this->isPublic = $this->published && (!$this->start || $this->start <= $time) && (!$this->stop || $this->stop > $time);

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
	 * @param string|array $strParams An optional array or string of URL parameters
	 *
	 * @throws RouteNotFoundException
	 * @throws ResourceNotFoundException
	 *
	 * @return string A URL that can be used in the front end
	 */
	public function getFrontendUrl($strParams=null)
	{
		$page = $this;
		$page->loadDetails();

		$objRouter = System::getContainer()->get('router');
		$referenceType = $this->domain && $objRouter->getContext()->getHost() !== $this->domain ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH;

		if (\is_array($strParams))
		{
			$parameters = array_merge($strParams, array(RouteObjectInterface::CONTENT_OBJECT => $page));
		}
		else
		{
			$parameters = array(RouteObjectInterface::CONTENT_OBJECT => $page, 'parameters' => $strParams);
		}

		try
		{
			$strUrl = $objRouter->generate(PageRoute::PAGE_BASED_ROUTE_NAME, $parameters, $referenceType);
		}
		catch (RouteNotFoundException $e)
		{
			$pageRegistry = System::getContainer()->get('contao.routing.page_registry');

			if (!$pageRegistry->isRoutable($this))
			{
				throw new ResourceNotFoundException(sprintf('Page ID %s is not routable', $this->id), 0, $e);
			}

			throw $e;
		}

		return $strUrl;
	}

	/**
	 * Generate an absolute URL depending on the current rewriteURL setting
	 *
	 * @param string|array $strParams An optional array or string of URL parameters
	 *
	 * @throws RouteNotFoundException
	 * @throws ResourceNotFoundException
	 *
	 * @return string An absolute URL that can be used in the front end
	 */
	public function getAbsoluteUrl($strParams=null)
	{
		$this->loadDetails();

		$objRouter = System::getContainer()->get('router');

		if (\is_array($strParams))
		{
			$parameters = array_merge($strParams, array(RouteObjectInterface::CONTENT_OBJECT => $this));
		}
		else
		{
			$parameters = array(RouteObjectInterface::CONTENT_OBJECT => $this, 'parameters' => $strParams);
		}

		try
		{
			$strUrl = $objRouter->generate(PageRoute::PAGE_BASED_ROUTE_NAME, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
		}
		catch (RouteNotFoundException $e)
		{
			$pageRegistry = System::getContainer()->get('contao.routing.page_registry');

			if (!$pageRegistry->isRoutable($this))
			{
				throw new ResourceNotFoundException(sprintf('Page ID %s is not routable', $this->id), 0, $e);
			}

			throw $e;
		}

		return $strUrl;
	}

	/**
	 * Generate the front end preview URL
	 *
	 * @param string|array $strParams An optional array or string of URL parameters
	 *
	 * @throws RouteNotFoundException
	 * @throws ResourceNotFoundException
	 *
	 * @return string The front end preview URL
	 */
	public function getPreviewUrl($strParams=null)
	{
		trigger_deprecation('contao/core-bundle', '5.3', 'Using "%s()" has been deprecated and will no longer work in Contao 6. Use "PageModel::getAbsoluteUrl()" and the contao_backend_preview route instead.', __METHOD__);

		$container = System::getContainer();

		if (!$previewScript = $container->getParameter('contao.preview_script'))
		{
			return $this->getAbsoluteUrl($strParams);
		}

		$this->loadDetails();

		$context = $container->get('router')->getContext();
		$baseUrl = $context->getBaseUrl();

		// Add the preview script
		$context->setBaseUrl(preg_replace('(/[^/]*$)', '', $baseUrl) . $previewScript);

		$objRouter = System::getContainer()->get('router');

		if (\is_array($strParams))
		{
			$parameters = array_merge($strParams, array(RouteObjectInterface::CONTENT_OBJECT => $this));
		}
		else
		{
			$parameters = array(RouteObjectInterface::CONTENT_OBJECT => $this, 'parameters' => $strParams);
		}

		try
		{
			$strUrl = $objRouter->generate(PageRoute::PAGE_BASED_ROUTE_NAME, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
		}
		catch (RouteNotFoundException $e)
		{
			$pageRegistry = System::getContainer()->get('contao.routing.page_registry');

			if (!$pageRegistry->isRoutable($this))
			{
				throw new ResourceNotFoundException(sprintf('Page ID %s is not routable', $this->id), 0, $e);
			}

			throw $e;
		}
		finally
		{
			$context->setBaseUrl($baseUrl);
		}

		return $strUrl;
	}

	/**
	 * Return the slug options
	 *
	 * @return array The slug options
	 */
	public function getSlugOptions()
	{
		// Use primary language for slug generation, until fixed in ICU or ausi/slug-generator (see #2413)
		$slugOptions = array('locale'=>LocaleUtil::getPrimaryLanguage($this->language));

		if ($this->validAliasCharacters)
		{
			$slugOptions['validChars'] = $this->validAliasCharacters;
		}

		return $slugOptions;
	}

	private static function stripPrefixesAndSuffixes(string $alias, string $urlPrefix, string $urlSuffix): string
	{
		if (null === self::$prefixes || null === self::$suffixes)
		{
			$rows = Database::getInstance()
				->execute("SELECT urlPrefix, urlSuffix FROM tl_page WHERE type='root'")
				->fetchAllAssoc()
			;

			self::$prefixes = array();
			self::$suffixes = array();

			foreach (array_column($rows, 'urlPrefix') as $prefix)
			{
				$prefix = trim($prefix, '/');

				if ('' !== $prefix)
				{
					self::$prefixes[] = $prefix . '/';
				}
			}

			foreach (array_column($rows, 'urlSuffix') as $suffix)
			{
				self::$suffixes[] = $suffix;
			}
		}

		$prefixes = self::$prefixes;

		if (!empty($urlPrefix))
		{
			$prefixes[] = $urlPrefix . '/';
		}

		if (null !== ($prefixRegex = self::regexArray($prefixes)))
		{
			$alias = preg_replace('/^' . $prefixRegex . '/i', '', $alias);
		}

		if (null !== ($suffixRegex = self::regexArray(array_merge(array($urlSuffix), self::$suffixes))))
		{
			$alias = preg_replace('/' . $suffixRegex . '$/i', '', $alias);
		}

		return $alias;
	}

	private static function regexArray(array $data): string|null
	{
		$data = array_filter(array_unique($data));

		if (0 === \count($data))
		{
			return null;
		}

		usort($data, static fn ($v, $k) => \strlen($v));

		foreach ($data as $k => $v)
		{
			$data[$k] = preg_quote($v, '/');
		}

		return '(' . implode('|', $data) . ')';
	}
}
