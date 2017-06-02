<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;


/**
 * Reads and writes front end modules
 *
 * @property integer $id
 * @property integer $pid
 * @property integer $tstamp
 * @property string  $name
 * @property string  $headline
 * @property string  $type
 * @property integer $levelOffset
 * @property integer $showLevel
 * @property boolean $hardLimit
 * @property boolean $showProtected
 * @property boolean $defineRoot
 * @property integer $rootPage
 * @property string  $navigationTpl
 * @property string  $customTpl
 * @property string  $pages
 * @property string  $orderPages
 * @property boolean $showHidden
 * @property string  $customLabel
 * @property boolean $autologin
 * @property integer $jumpTo
 * @property boolean $redirectBack
 * @property string  $cols
 * @property string  $editable
 * @property string  $memberTpl
 * @property integer $form
 * @property string  $queryType
 * @property boolean $fuzzy
 * @property integer $contextLength
 * @property integer $totalLength
 * @property integer $perPage
 * @property string  $searchType
 * @property string  $searchTpl
 * @property string  $inColumn
 * @property integer $skipFirst
 * @property boolean $loadFirst
 * @property string  $size
 * @property boolean $transparent
 * @property string  $flashvars
 * @property string  $altContent
 * @property string  $source
 * @property string  $singleSRC
 * @property string  $url
 * @property boolean $interactive
 * @property string  $flashID
 * @property string  $flashJS
 * @property string  $imgSize
 * @property boolean $useCaption
 * @property boolean $fullsize
 * @property string  $multiSRC
 * @property string  $orderSRC
 * @property string  $html
 * @property integer $rss_cache
 * @property string  $rss_feed
 * @property string  $rss_template
 * @property integer $numberOfItems
 * @property boolean $disableCaptcha
 * @property string  $reg_groups
 * @property boolean $reg_allowLogin
 * @property boolean $reg_skipName
 * @property string  $reg_close
 * @property boolean $reg_assignDir
 * @property string  $reg_homeDir
 * @property boolean $reg_activate
 * @property integer $reg_jumpTo
 * @property string  $reg_text
 * @property string  $reg_password
 * @property boolean $protected
 * @property string  $groups
 * @property boolean $guests
 * @property string  $cssID
 * @property string  $typePrefix
 * @property string  $classes
 *
 * @method static ModuleModel|null findById($id, $opt=array())
 * @method static ModuleModel|null findByPk($id, $opt=array())
 * @method static ModuleModel|null findByIdOrAlias($val, $opt=array())
 * @method static ModuleModel|null findOneBy($col, $val, $opt=array())
 * @method static ModuleModel|null findOneByPid($val, $opt=array())
 * @method static ModuleModel|null findOneByTstamp($val, $opt=array())
 * @method static ModuleModel|null findOneByName($val, $opt=array())
 * @method static ModuleModel|null findOneByHeadline($val, $opt=array())
 * @method static ModuleModel|null findOneByType($val, $opt=array())
 * @method static ModuleModel|null findOneByLevelOffset($val, $opt=array())
 * @method static ModuleModel|null findOneByShowLevel($val, $opt=array())
 * @method static ModuleModel|null findOneByHardLimit($val, $opt=array())
 * @method static ModuleModel|null findOneByShowProtected($val, $opt=array())
 * @method static ModuleModel|null findOneByDefineRoot($val, $opt=array())
 * @method static ModuleModel|null findOneByRootPage($val, $opt=array())
 * @method static ModuleModel|null findOneByNavigationTpl($val, $opt=array())
 * @method static ModuleModel|null findOneByCustomTpl($val, $opt=array())
 * @method static ModuleModel|null findOneByPages($val, $opt=array())
 * @method static ModuleModel|null findOneByOrderPages($val, $opt=array())
 * @method static ModuleModel|null findOneByShowHidden($val, $opt=array())
 * @method static ModuleModel|null findOneByCustomLabel($val, $opt=array())
 * @method static ModuleModel|null findOneByAutologin($val, $opt=array())
 * @method static ModuleModel|null findOneByJumpTo($val, $opt=array())
 * @method static ModuleModel|null findOneByRedirectBack($val, $opt=array())
 * @method static ModuleModel|null findOneByCols($val, $opt=array())
 * @method static ModuleModel|null findOneByEditable($val, $opt=array())
 * @method static ModuleModel|null findOneByMemberTpl($val, $opt=array())
 * @method static ModuleModel|null findOneByTableless($val, $opt=array())
 * @method static ModuleModel|null findOneByForm($val, $opt=array())
 * @method static ModuleModel|null findOneByQueryType($val, $opt=array())
 * @method static ModuleModel|null findOneByFuzzy($val, $opt=array())
 * @method static ModuleModel|null findOneByContextLength($val, $opt=array())
 * @method static ModuleModel|null findOneByTotalLength($val, $opt=array())
 * @method static ModuleModel|null findOneByPerPage($val, $opt=array())
 * @method static ModuleModel|null findOneBySearchType($val, $opt=array())
 * @method static ModuleModel|null findOneBySearchTpl($val, $opt=array())
 * @method static ModuleModel|null findOneByInColumn($val, $opt=array())
 * @method static ModuleModel|null findOneBySkipFirst($val, $opt=array())
 * @method static ModuleModel|null findOneByLoadFirst($val, $opt=array())
 * @method static ModuleModel|null findOneBySize($val, $opt=array())
 * @method static ModuleModel|null findOneByTransparent($val, $opt=array())
 * @method static ModuleModel|null findOneByFlashvars($val, $opt=array())
 * @method static ModuleModel|null findOneByAltContent($val, $opt=array())
 * @method static ModuleModel|null findOneBySource($val, $opt=array())
 * @method static ModuleModel|null findOneBySingleSRC($val, $opt=array())
 * @method static ModuleModel|null findOneByUrl($val, $opt=array())
 * @method static ModuleModel|null findOneByInteractive($val, $opt=array())
 * @method static ModuleModel|null findOneByFlashID($val, $opt=array())
 * @method static ModuleModel|null findOneByFlashJS($val, $opt=array())
 * @method static ModuleModel|null findOneByImgSize($val, $opt=array())
 * @method static ModuleModel|null findOneByUseCaption($val, $opt=array())
 * @method static ModuleModel|null findOneByFullsize($val, $opt=array())
 * @method static ModuleModel|null findOneByMultiSRC($val, $opt=array())
 * @method static ModuleModel|null findOneByOrderSRC($val, $opt=array())
 * @method static ModuleModel|null findOneByHtml($val, $opt=array())
 * @method static ModuleModel|null findOneByRss_cache($val, $opt=array())
 * @method static ModuleModel|null findOneByRss_feed($val, $opt=array())
 * @method static ModuleModel|null findOneByRss_template($val, $opt=array())
 * @method static ModuleModel|null findOneByNumberOfItems($val, $opt=array())
 * @method static ModuleModel|null findOneByDisableCaptcha($val, $opt=array())
 * @method static ModuleModel|null findOneByReg_groups($val, $opt=array())
 * @method static ModuleModel|null findOneByReg_allowLogin($val, $opt=array())
 * @method static ModuleModel|null findOneByReg_skipName($val, $opt=array())
 * @method static ModuleModel|null findOneByReg_close($val, $opt=array())
 * @method static ModuleModel|null findOneByReg_assignDir($val, $opt=array())
 * @method static ModuleModel|null findOneByReg_homeDir($val, $opt=array())
 * @method static ModuleModel|null findOneByReg_activate($val, $opt=array())
 * @method static ModuleModel|null findOneByReg_jumpTo($val, $opt=array())
 * @method static ModuleModel|null findOneByReg_text($val, $opt=array())
 * @method static ModuleModel|null findOneByReg_password($val, $opt=array())
 * @method static ModuleModel|null findOneByProtected($val, $opt=array())
 * @method static ModuleModel|null findOneByGroups($val, $opt=array())
 * @method static ModuleModel|null findOneByGuests($val, $opt=array())
 * @method static ModuleModel|null findOneByCssID($val, $opt=array())
 * @method static ModuleModel|null findOneBySpace($val, $opt=array())
 *
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByPid($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByTstamp($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByName($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByHeadline($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByType($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByLevelOffset($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByShowLevel($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByHardLimit($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByShowProtected($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByDefineRoot($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByRootPage($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByNavigationTpl($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByCustomTpl($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByPages($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByOrderPages($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByShowHidden($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByCustomLabel($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByAutologin($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByJumpTo($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByRedirectBack($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByCols($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByEditable($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByMemberTpl($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByTableless($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByForm($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByQueryType($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByFuzzy($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByContextLength($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByTotalLength($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByPerPage($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findBySearchType($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findBySearchTpl($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByInColumn($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findBySkipFirst($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByLoadFirst($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findBySize($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByTransparent($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByFlashvars($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByAltContent($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findBySource($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findBySingleSRC($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByUrl($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByInteractive($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByFlashID($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByFlashJS($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByImgSize($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByUseCaption($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByFullsize($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByMultiSRC($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByOrderSRC($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByHtml($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByRss_cache($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByRss_feed($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByRss_template($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByNumberOfItems($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByDisableCaptcha($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByReg_groups($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByReg_allowLogin($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByReg_skipName($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByReg_close($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByReg_assignDir($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByReg_homeDir($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByReg_activate($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByReg_jumpTo($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByReg_text($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByReg_password($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByProtected($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByGroups($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByGuests($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findByCssID($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findBySpace($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findMultipleByIds($val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findBy($col, $val, $opt=array())
 * @method static Model\Collection|ModuleModel[]|ModuleModel|null findAll($opt=array())
 *
 * @method static integer countById($id, $opt=array())
 * @method static integer countByPid($val, $opt=array())
 * @method static integer countByTstamp($val, $opt=array())
 * @method static integer countByName($val, $opt=array())
 * @method static integer countByHeadline($val, $opt=array())
 * @method static integer countByType($val, $opt=array())
 * @method static integer countByLevelOffset($val, $opt=array())
 * @method static integer countByShowLevel($val, $opt=array())
 * @method static integer countByHardLimit($val, $opt=array())
 * @method static integer countByShowProtected($val, $opt=array())
 * @method static integer countByDefineRoot($val, $opt=array())
 * @method static integer countByRootPage($val, $opt=array())
 * @method static integer countByNavigationTpl($val, $opt=array())
 * @method static integer countByCustomTpl($val, $opt=array())
 * @method static integer countByPages($val, $opt=array())
 * @method static integer countByOrderPages($val, $opt=array())
 * @method static integer countByShowHidden($val, $opt=array())
 * @method static integer countByCustomLabel($val, $opt=array())
 * @method static integer countByAutologin($val, $opt=array())
 * @method static integer countByJumpTo($val, $opt=array())
 * @method static integer countByRedirectBack($val, $opt=array())
 * @method static integer countByCols($val, $opt=array())
 * @method static integer countByEditable($val, $opt=array())
 * @method static integer countByMemberTpl($val, $opt=array())
 * @method static integer countByTableless($val, $opt=array())
 * @method static integer countByForm($val, $opt=array())
 * @method static integer countByQueryType($val, $opt=array())
 * @method static integer countByFuzzy($val, $opt=array())
 * @method static integer countByContextLength($val, $opt=array())
 * @method static integer countByTotalLength($val, $opt=array())
 * @method static integer countByPerPage($val, $opt=array())
 * @method static integer countBySearchType($val, $opt=array())
 * @method static integer countBySearchTpl($val, $opt=array())
 * @method static integer countByInColumn($val, $opt=array())
 * @method static integer countBySkipFirst($val, $opt=array())
 * @method static integer countByLoadFirst($val, $opt=array())
 * @method static integer countBySize($val, $opt=array())
 * @method static integer countByTransparent($val, $opt=array())
 * @method static integer countByFlashvars($val, $opt=array())
 * @method static integer countByAltContent($val, $opt=array())
 * @method static integer countBySource($val, $opt=array())
 * @method static integer countBySingleSRC($val, $opt=array())
 * @method static integer countByUrl($val, $opt=array())
 * @method static integer countByInteractive($val, $opt=array())
 * @method static integer countByFlashID($val, $opt=array())
 * @method static integer countByFlashJS($val, $opt=array())
 * @method static integer countByImgSize($val, $opt=array())
 * @method static integer countByUseCaption($val, $opt=array())
 * @method static integer countByFullsize($val, $opt=array())
 * @method static integer countByMultiSRC($val, $opt=array())
 * @method static integer countByOrderSRC($val, $opt=array())
 * @method static integer countByHtml($val, $opt=array())
 * @method static integer countByRss_cache($val, $opt=array())
 * @method static integer countByRss_feed($val, $opt=array())
 * @method static integer countByRss_template($val, $opt=array())
 * @method static integer countByNumberOfItems($val, $opt=array())
 * @method static integer countByDisableCaptcha($val, $opt=array())
 * @method static integer countByReg_groups($val, $opt=array())
 * @method static integer countByReg_allowLogin($val, $opt=array())
 * @method static integer countByReg_skipName($val, $opt=array())
 * @method static integer countByReg_close($val, $opt=array())
 * @method static integer countByReg_assignDir($val, $opt=array())
 * @method static integer countByReg_homeDir($val, $opt=array())
 * @method static integer countByReg_activate($val, $opt=array())
 * @method static integer countByReg_jumpTo($val, $opt=array())
 * @method static integer countByReg_text($val, $opt=array())
 * @method static integer countByReg_password($val, $opt=array())
 * @method static integer countByProtected($val, $opt=array())
 * @method static integer countByGroups($val, $opt=array())
 * @method static integer countByGuests($val, $opt=array())
 * @method static integer countByCssID($val, $opt=array())
 * @method static integer countBySpace($val, $opt=array())
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ModuleModel extends \Model
{

	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_module';

}
