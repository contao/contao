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
 * Reads and writes front end modules
 *
 * @property integer           $id
 * @property integer           $pid
 * @property integer           $tstamp
 * @property string            $name
 * @property string            $headline
 * @property string            $type
 * @property integer           $levelOffset
 * @property integer           $showLevel
 * @property boolean           $hardLimit
 * @property boolean           $showProtected
 * @property boolean           $defineRoot
 * @property integer           $rootPage
 * @property string            $navigationTpl
 * @property string            $customTpl
 * @property string|array|null $pages
 * @property boolean           $showHidden
 * @property string            $customLabel
 * @property boolean           $autologin
 * @property integer           $jumpTo
 * @property integer           $overviewPage
 * @property boolean           $redirectBack
 * @property string|array|null $editable
 * @property string            $memberTpl
 * @property integer           $form
 * @property string            $queryType
 * @property boolean           $fuzzy
 * @property string|array      $contextLength
 * @property integer           $minKeywordLength
 * @property integer           $perPage
 * @property string            $searchType
 * @property string            $searchTpl
 * @property string            $inColumn
 * @property integer           $skipFirst
 * @property boolean           $loadFirst
 * @property string|null       $singleSRC
 * @property string            $url
 * @property string|integer    $imgSize
 * @property boolean           $useCaption
 * @property boolean           $fullsize
 * @property string|array|null $multiSRC
 * @property string|null       $html
 * @property string|null       $unfilteredHtml
 * @property integer           $feedCache
 * @property string|null       $feedUrls
 * @property integer           $numberOfItems
 * @property boolean           $disableCaptcha
 * @property string|array|null $reg_groups
 * @property boolean           $reg_allowLogin
 * @property boolean           $reg_skipName
 * @property string            $reg_close
 * @property boolean           $reg_deleteDir
 * @property boolean           $reg_assignDir
 * @property string|null       $reg_homeDir
 * @property boolean           $reg_activate
 * @property integer           $reg_jumpTo
 * @property string|null       $reg_text
 * @property string|null       $reg_password
 * @property boolean           $protected
 * @property string|array|null $groups
 * @property string|array      $cssID
 * @property string|array|null $rootPageDependentModules
 *
 * @property string $typePrefix
 * @property array  $classes
 *
 * @method static ModuleModel|null findById($id, array $opt=array())
 * @method static ModuleModel|null findByPk($id, array $opt=array())
 * @method static ModuleModel|null findByIdOrAlias($val, array $opt=array())
 * @method static ModuleModel|null findOneBy($col, $val, array $opt=array())
 * @method static ModuleModel|null findOneByPid($val, array $opt=array())
 * @method static ModuleModel|null findOneByTstamp($val, array $opt=array())
 * @method static ModuleModel|null findOneByName($val, array $opt=array())
 * @method static ModuleModel|null findOneByHeadline($val, array $opt=array())
 * @method static ModuleModel|null findOneByType($val, array $opt=array())
 * @method static ModuleModel|null findOneByLevelOffset($val, array $opt=array())
 * @method static ModuleModel|null findOneByShowLevel($val, array $opt=array())
 * @method static ModuleModel|null findOneByHardLimit($val, array $opt=array())
 * @method static ModuleModel|null findOneByShowProtected($val, array $opt=array())
 * @method static ModuleModel|null findOneByDefineRoot($val, array $opt=array())
 * @method static ModuleModel|null findOneByRootPage($val, array $opt=array())
 * @method static ModuleModel|null findOneByNavigationTpl($val, array $opt=array())
 * @method static ModuleModel|null findOneByCustomTpl($val, array $opt=array())
 * @method static ModuleModel|null findOneByPages($val, array $opt=array())
 * @method static ModuleModel|null findOneByShowHidden($val, array $opt=array())
 * @method static ModuleModel|null findOneByCustomLabel($val, array $opt=array())
 * @method static ModuleModel|null findOneByAutologin($val, array $opt=array())
 * @method static ModuleModel|null findOneByJumpTo($val, array $opt=array())
 * @method static ModuleModel|null findOneByOverviewPage($val, array $opt=array())
 * @method static ModuleModel|null findOneByRedirectBack($val, array $opt=array())
 * @method static ModuleModel|null findOneByEditable($val, array $opt=array())
 * @method static ModuleModel|null findOneByMemberTpl($val, array $opt=array())
 * @method static ModuleModel|null findOneByTableless($val, array $opt=array())
 * @method static ModuleModel|null findOneByForm($val, array $opt=array())
 * @method static ModuleModel|null findOneByQueryType($val, array $opt=array())
 * @method static ModuleModel|null findOneByFuzzy($val, array $opt=array())
 * @method static ModuleModel|null findOneByContextLength($val, array $opt=array())
 * @method static ModuleModel|null findOneByMinKeywordLength($val, array $opt=array())
 * @method static ModuleModel|null findOneByPerPage($val, array $opt=array())
 * @method static ModuleModel|null findOneBySearchType($val, array $opt=array())
 * @method static ModuleModel|null findOneBySearchTpl($val, array $opt=array())
 * @method static ModuleModel|null findOneByInColumn($val, array $opt=array())
 * @method static ModuleModel|null findOneBySkipFirst($val, array $opt=array())
 * @method static ModuleModel|null findOneByLoadFirst($val, array $opt=array())
 * @method static ModuleModel|null findOneBySingleSRC($val, array $opt=array())
 * @method static ModuleModel|null findOneByUrl($val, array $opt=array())
 * @method static ModuleModel|null findOneByImgSize($val, array $opt=array())
 * @method static ModuleModel|null findOneByUseCaption($val, array $opt=array())
 * @method static ModuleModel|null findOneByFullsize($val, array $opt=array())
 * @method static ModuleModel|null findOneByMultiSRC($val, array $opt=array())
 * @method static ModuleModel|null findOneByHtml($val, array $opt=array())
 * @method static ModuleModel|null findOneByUnfilteredHtml($val, array $opt=array())
 * @method static ModuleModel|null findOneByFeedCache($val, array $opt=array())
 * @method static ModuleModel|null findOneByFeedUrlsfeedUrlsfeedUrls($val, array $opt=array())
 * @method static ModuleModel|null findOneByNumberOfItems($val, array $opt=array())
 * @method static ModuleModel|null findOneByDisableCaptcha($val, array $opt=array())
 * @method static ModuleModel|null findOneByReg_groups($val, array $opt=array())
 * @method static ModuleModel|null findOneByReg_allowLogin($val, array $opt=array())
 * @method static ModuleModel|null findOneByReg_skipName($val, array $opt=array())
 * @method static ModuleModel|null findOneByReg_close($val, array $opt=array())
 * @method static ModuleModel|null findOneByReg_deleteDir($val, array $opt=array())
 * @method static ModuleModel|null findOneByReg_assignDir($val, array $opt=array())
 * @method static ModuleModel|null findOneByReg_homeDir($val, array $opt=array())
 * @method static ModuleModel|null findOneByReg_activate($val, array $opt=array())
 * @method static ModuleModel|null findOneByReg_jumpTo($val, array $opt=array())
 * @method static ModuleModel|null findOneByReg_text($val, array $opt=array())
 * @method static ModuleModel|null findOneByReg_password($val, array $opt=array())
 * @method static ModuleModel|null findOneByProtected($val, array $opt=array())
 * @method static ModuleModel|null findOneByGroups($val, array $opt=array())
 * @method static ModuleModel|null findOneByCssID($val, array $opt=array())
 * @method static ModuleModel|null findOneBySpace($val, array $opt=array())
 *
 * @method static Collection|ModuleModel[]|ModuleModel|null findByPid($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByTstamp($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByName($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByHeadline($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByType($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByLevelOffset($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByShowLevel($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByHardLimit($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByShowProtected($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByDefineRoot($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByRootPage($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByNavigationTpl($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByCustomTpl($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByPages($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByShowHidden($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByCustomLabel($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByAutologin($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByJumpTo($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByOverviewPage($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByRedirectBack($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByEditable($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByMemberTpl($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByTableless($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByForm($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByQueryType($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByFuzzy($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByContextLength($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByMinKeywordLength($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByPerPage($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findBySearchType($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findBySearchTpl($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByInColumn($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findBySkipFirst($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByLoadFirst($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findBySingleSRC($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByUrl($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByImgSize($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByUseCaption($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByFullsize($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByMultiSRC($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByHtml($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByUnfilteredHtml($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByFeedCache($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByFeedUrls($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByNumberOfItems($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByDisableCaptcha($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByReg_groups($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByReg_allowLogin($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByReg_skipName($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByReg_close($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByReg_deleteDir($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByReg_assignDir($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByReg_homeDir($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByReg_activate($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByReg_jumpTo($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByReg_text($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByReg_password($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByProtected($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByGroups($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findByCssID($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findBySpace($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findMultipleByIds($val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findBy($col, $val, array $opt=array())
 * @method static Collection|ModuleModel[]|ModuleModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByPid($val, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByName($val, array $opt=array())
 * @method static integer countByHeadline($val, array $opt=array())
 * @method static integer countByType($val, array $opt=array())
 * @method static integer countByLevelOffset($val, array $opt=array())
 * @method static integer countByShowLevel($val, array $opt=array())
 * @method static integer countByHardLimit($val, array $opt=array())
 * @method static integer countByShowProtected($val, array $opt=array())
 * @method static integer countByDefineRoot($val, array $opt=array())
 * @method static integer countByRootPage($val, array $opt=array())
 * @method static integer countByNavigationTpl($val, array $opt=array())
 * @method static integer countByCustomTpl($val, array $opt=array())
 * @method static integer countByPages($val, array $opt=array())
 * @method static integer countByShowHidden($val, array $opt=array())
 * @method static integer countByCustomLabel($val, array $opt=array())
 * @method static integer countByAutologin($val, array $opt=array())
 * @method static integer countByJumpTo($val, array $opt=array())
 * @method static integer countByOverviewPage($val, array $opt=array())
 * @method static integer countByRedirectBack($val, array $opt=array())
 * @method static integer countByEditable($val, array $opt=array())
 * @method static integer countByMemberTpl($val, array $opt=array())
 * @method static integer countByTableless($val, array $opt=array())
 * @method static integer countByForm($val, array $opt=array())
 * @method static integer countByQueryType($val, array $opt=array())
 * @method static integer countByFuzzy($val, array $opt=array())
 * @method static integer countByContextLength($val, array $opt=array())
 * @method static integer countByMinKeywordLength($val, array $opt=array())
 * @method static integer countByPerPage($val, array $opt=array())
 * @method static integer countBySearchType($val, array $opt=array())
 * @method static integer countBySearchTpl($val, array $opt=array())
 * @method static integer countByInColumn($val, array $opt=array())
 * @method static integer countBySkipFirst($val, array $opt=array())
 * @method static integer countByLoadFirst($val, array $opt=array())
 * @method static integer countBySingleSRC($val, array $opt=array())
 * @method static integer countByUrl($val, array $opt=array())
 * @method static integer countByImgSize($val, array $opt=array())
 * @method static integer countByUseCaption($val, array $opt=array())
 * @method static integer countByFullsize($val, array $opt=array())
 * @method static integer countByMultiSRC($val, array $opt=array())
 * @method static integer countByHtml($val, array $opt=array())
 * @method static integer countByUnfilteredHtml($val, array $opt=array())
 * @method static integer countByFeedCache($val, array $opt=array())
 * @method static integer countByFeedUrls($val, array $opt=array())
 * @method static integer countByNumberOfItems($val, array $opt=array())
 * @method static integer countByDisableCaptcha($val, array $opt=array())
 * @method static integer countByReg_groups($val, array $opt=array())
 * @method static integer countByReg_allowLogin($val, array $opt=array())
 * @method static integer countByReg_skipName($val, array $opt=array())
 * @method static integer countByReg_close($val, array $opt=array())
 * @method static integer countByReg_deleteDir($val, array $opt=array())
 * @method static integer countByReg_assignDir($val, array $opt=array())
 * @method static integer countByReg_homeDir($val, array $opt=array())
 * @method static integer countByReg_activate($val, array $opt=array())
 * @method static integer countByReg_jumpTo($val, array $opt=array())
 * @method static integer countByReg_text($val, array $opt=array())
 * @method static integer countByReg_password($val, array $opt=array())
 * @method static integer countByProtected($val, array $opt=array())
 * @method static integer countByGroups($val, array $opt=array())
 * @method static integer countByCssID($val, array $opt=array())
 * @method static integer countBySpace($val, array $opt=array())
 */
class ModuleModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_module';
}
