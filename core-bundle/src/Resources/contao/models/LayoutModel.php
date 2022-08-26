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
 * Reads and writes page layouts
 *
 * @property string|integer    $id
 * @property string|integer    $pid
 * @property string|integer    $tstamp
 * @property string            $name
 * @property string            $rows
 * @property string|array      $headerHeight
 * @property string|array      $footerHeight
 * @property string            $cols
 * @property string|array      $widthLeft
 * @property string|array      $widthRight
 * @property string|array|null $sections
 * @property string|array      $framework
 * @property string|array|null $stylesheet
 * @property string|array|null $external
 * @property string            $loadingOrder
 * @property string|boolean    $combineScripts
 * @property string|array|null $modules
 * @property string            $template
 * @property string|boolean    $minifyMarkup
 * @property string|integer    $lightboxSize
 * @property string            $defaultImageDensities
 * @property string            $viewport
 * @property string            $titleTag
 * @property string            $cssClass
 * @property string            $onload
 * @property string|null       $head
 * @property string|boolean    $addJQuery
 * @property string|array|null $jquery
 * @property string|boolean    $addMooTools
 * @property string|array|null $mootools
 * @property string|array|null $analytics
 * @property string|array|null $externalJs
 * @property string|array|null $scripts
 * @property string|null       $script
 * @property string|boolean    $static
 * @property string|array      $width
 * @property string|array      $align
 *
 * @method static LayoutModel|null findById($id, array $opt=array())
 * @method static LayoutModel|null findByPk($id, array $opt=array())
 * @method static LayoutModel|null findByIdOrAlias($val, array $opt=array())
 * @method static LayoutModel|null findOneBy($col, $val, array $opt=array())
 * @method static LayoutModel|null findOneByPid($val, array $opt=array())
 * @method static LayoutModel|null findOneByTstamp($val, array $opt=array())
 * @method static LayoutModel|null findOneByName($val, array $opt=array())
 * @method static LayoutModel|null findOneByRows($val, array $opt=array())
 * @method static LayoutModel|null findOneByHeaderHeight($val, array $opt=array())
 * @method static LayoutModel|null findOneByFooterHeight($val, array $opt=array())
 * @method static LayoutModel|null findOneByCols($val, array $opt=array())
 * @method static LayoutModel|null findOneByWidthLeft($val, array $opt=array())
 * @method static LayoutModel|null findOneByWidthRight($val, array $opt=array())
 * @method static LayoutModel|null findOneBySections($val, array $opt=array())
 * @method static LayoutModel|null findOneByFramework($val, array $opt=array())
 * @method static LayoutModel|null findOneByStylesheet($val, array $opt=array())
 * @method static LayoutModel|null findOneByExternal($val, array $opt=array())
 * @method static LayoutModel|null findOneByLoadingOrder($val, array $opt=array())
 * @method static LayoutModel|null findOneByCombineScripts($val, array $opt=array())
 * @method static LayoutModel|null findOneByModules($val, array $opt=array())
 * @method static LayoutModel|null findOneByTemplate($val, array $opt=array())
 * @method static LayoutModel|null findOneByMinifyMarkup($val, array $opt=array())
 * @method static LayoutModel|null findOneByLightboxSize($val, array $opt=array())
 * @method static LayoutModel|null findOneByDefaultImageDensities($val, array $opt=array())
 * @method static LayoutModel|null findOneByViewport($val, array $opt=array())
 * @method static LayoutModel|null findOneByTitleTag($val, array $opt=array())
 * @method static LayoutModel|null findOneByCssClass($val, array $opt=array())
 * @method static LayoutModel|null findOneByOnload($val, array $opt=array())
 * @method static LayoutModel|null findOneByHead($val, array $opt=array())
 * @method static LayoutModel|null findOneByAddJQuery($val, array $opt=array())
 * @method static LayoutModel|null findOneByJquery($val, array $opt=array())
 * @method static LayoutModel|null findOneByAddMooTools($val, array $opt=array())
 * @method static LayoutModel|null findOneByMootools($val, array $opt=array())
 * @method static LayoutModel|null findOneByAnalytics($val, array $opt=array())
 * @method static LayoutModel|null findOneByExternalJs($val, array $opt=array())
 * @method static LayoutModel|null findOneByScripts($val, array $opt=array())
 * @method static LayoutModel|null findOneByScript($val, array $opt=array())
 * @method static LayoutModel|null findOneByStatic($val, array $opt=array())
 * @method static LayoutModel|null findOneByWidth($val, array $opt=array())
 * @method static LayoutModel|null findOneByAlign($val, array $opt=array())
 *
 * @method static Collection|LayoutModel[]|LayoutModel|null findByPid($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByTstamp($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByName($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByRows($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByHeaderHeight($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByFooterHeight($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByCols($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByWidthLeft($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByWidthRight($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findBySections($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByFramework($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByStylesheet($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByExternal($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByLoadingOrder($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByCombineScripts($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByModules($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByTemplate($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByMinifyMarkup($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByLightboxSize($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByDefaultImageDensities($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByViewport($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByTitleTag($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByCssClass($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByOnload($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByHead($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByAddJQuery($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByJquery($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByAddMooTools($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByMootools($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByAnalytics($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByExternalJs($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByScripts($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByScript($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByStatic($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByWidth($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findByAlign($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findMultipleByIds($val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findBy($col, $val, array $opt=array())
 * @method static Collection|LayoutModel[]|LayoutModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByPid($val, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByName($val, array $opt=array())
 * @method static integer countByRows($val, array $opt=array())
 * @method static integer countByHeaderHeight($val, array $opt=array())
 * @method static integer countByFooterHeight($val, array $opt=array())
 * @method static integer countByCols($val, array $opt=array())
 * @method static integer countByWidthLeft($val, array $opt=array())
 * @method static integer countByWidthRight($val, array $opt=array())
 * @method static integer countBySections($val, array $opt=array())
 * @method static integer countByFramework($val, array $opt=array())
 * @method static integer countByStylesheet($val, array $opt=array())
 * @method static integer countByExternal($val, array $opt=array())
 * @method static integer countByLoadingOrder($val, array $opt=array())
 * @method static integer countByCombineScripts($val, array $opt=array())
 * @method static integer countByModules($val, array $opt=array())
 * @method static integer countByTemplate($val, array $opt=array())
 * @method static integer countByMinifyMarkup($val, array $opt=array())
 * @method static integer countByLightboxSize($val, array $opt=array())
 * @method static integer countByDefaultImageDensities($val, array $opt=array())
 * @method static integer countByViewport($val, array $opt=array())
 * @method static integer countByTitleTag($val, array $opt=array())
 * @method static integer countByCssClass($val, array $opt=array())
 * @method static integer countByOnload($val, array $opt=array())
 * @method static integer countByHead($val, array $opt=array())
 * @method static integer countByAddJQuery($val, array $opt=array())
 * @method static integer countByJquery($val, array $opt=array())
 * @method static integer countByAddMooTools($val, array $opt=array())
 * @method static integer countByMootools($val, array $opt=array())
 * @method static integer countByAnalytics($val, array $opt=array())
 * @method static integer countByExternalJs($val, array $opt=array())
 * @method static integer countByScripts($val, array $opt=array())
 * @method static integer countByScript($val, array $opt=array())
 * @method static integer countByStatic($val, array $opt=array())
 * @method static integer countByWidth($val, array $opt=array())
 * @method static integer countByAlign($val, array $opt=array())
 */
class LayoutModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_layout';
}

class_alias(LayoutModel::class, 'LayoutModel');
