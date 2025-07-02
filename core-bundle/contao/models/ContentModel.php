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
use Contao\Model\MetadataTrait;

/**
 * Reads and writes content elements
 *
 * @property integer           $id
 * @property integer           $pid
 * @property string            $ptable
 * @property integer           $sorting
 * @property integer           $tstamp
 * @property string            $type
 * @property string            $headline
 * @property string            $sectionHeadline
 * @property string|null       $text
 * @property boolean           $addImage
 * @property boolean           $inline
 * @property boolean           $overwriteMeta
 * @property string|null       $singleSRC
 * @property string            $alt
 * @property string            $imageTitle
 * @property string|integer    $size
 * @property string            $imageUrl
 * @property boolean           $fullsize
 * @property string            $caption
 * @property string            $floating
 * @property string|null       $html
 * @property string|null       $unfilteredHtml
 * @property string            $listtype
 * @property string|array|null $listitems
 * @property string|array|null $tableitems
 * @property string            $summary
 * @property boolean           $thead
 * @property boolean           $tfoot
 * @property boolean           $tleft
 * @property boolean           $sortable
 * @property integer           $sortIndex
 * @property string            $sortOrder
 * @property boolean           $closeSections
 * @property string            $mooHeadline
 * @property string            $mooStyle
 * @property string            $mooClasses
 * @property string            $highlight
 * @property string            $markdownSource
 * @property string|null       $code
 * @property string|null       $url
 * @property boolean           $target
 * @property boolean           $overwriteLink
 * @property string            $titleText
 * @property string            $linkTitle
 * @property string            $embed
 * @property string            $rel
 * @property boolean           $useImage
 * @property string|array|null $multiSRC
 * @property boolean           $useHomeDir
 * @property integer           $perRow
 * @property integer           $perPage
 * @property integer           $numberOfItems
 * @property string            $sortBy
 * @property boolean           $metaIgnore
 * @property string            $galleryTpl
 * @property string            $customTpl
 * @property string|null       $playerSRC
 * @property string            $youtube
 * @property string            $vimeo
 * @property string|null       $posterSRC
 * @property string|array      $playerSize
 * @property string|array|null $playerOptions
 * @property integer           $playerStart
 * @property integer           $playerStop
 * @property string            $playerCaption
 * @property string            $playerAspect
 * @property boolean           $splashImage
 * @property string            $playerTitle
 * @property string            $playerPreload
 * @property string            $playerColor
 * @property string|array|null $youtubeOptions
 * @property string|array|null $vimeoOptions
 * @property string|null       $textTrackSRC
 * @property integer           $sliderDelay
 * @property integer           $sliderSpeed
 * @property integer           $sliderStartSlide
 * @property boolean           $sliderContinuous
 * @property integer           $cteAlias
 * @property integer           $articleAlias
 * @property integer           $article
 * @property integer           $form
 * @property integer           $module
 * @property boolean           $protected
 * @property string|array|null $groups
 * @property string|array      $cssID
 * @property boolean           $invisible
 * @property string|integer    $start
 * @property string|integer    $stop
 * @property boolean           $showPreview
 *
 * @property string  $typePrefix
 * @property array   $classes
 * @property integer $origId
 *
 * @method static ContentModel|null findById($id, array $opt=array())
 * @method static ContentModel|null findByPk($id, array $opt=array())
 * @method static ContentModel|null findByIdOrAlias($val, array $opt=array())
 * @method static ContentModel|null findOneBy($col, $val, array $opt=array())
 * @method static ContentModel|null findOneByPid($val, array $opt=array())
 * @method static ContentModel|null findOneByPtable($val, array $opt=array())
 * @method static ContentModel|null findOneBySorting($val, array $opt=array())
 * @method static ContentModel|null findOneByTstamp($val, array $opt=array())
 * @method static ContentModel|null findOneByType($val, array $opt=array())
 * @method static ContentModel|null findOneByHeadline($val, array $opt=array())
 * @method static ContentModel|null findOneBySectionHeadline($val, array $opt=array())
 * @method static ContentModel|null findOneByText($val, array $opt=array())
 * @method static ContentModel|null findOneByAddImage($val, array $opt=array())
 * @method static ContentModel|null findOneByInline($val, array $opt=array())
 * @method static ContentModel|null findOneByOverwriteMeta($val, array $opt=array())
 * @method static ContentModel|null findOneBySingleSRC($val, array $opt=array())
 * @method static ContentModel|null findOneByAlt($val, array $opt=array())
 * @method static ContentModel|null findOneByImageTitle($val, array $opt=array())
 * @method static ContentModel|null findOneBySize($val, array $opt=array())
 * @method static ContentModel|null findOneByImageUrl($val, array $opt=array())
 * @method static ContentModel|null findOneByFullsize($val, array $opt=array())
 * @method static ContentModel|null findOneByCaption($val, array $opt=array())
 * @method static ContentModel|null findOneByFloating($val, array $opt=array())
 * @method static ContentModel|null findOneByHtml($val, array $opt=array())
 * @method static ContentModel|null findOneByUnfilteredHtml($val, array $opt=array())
 * @method static ContentModel|null findOneByListtype($val, array $opt=array())
 * @method static ContentModel|null findOneByListitems($val, array $opt=array())
 * @method static ContentModel|null findOneByTableitems($val, array $opt=array())
 * @method static ContentModel|null findOneBySummary($val, array $opt=array())
 * @method static ContentModel|null findOneByThead($val, array $opt=array())
 * @method static ContentModel|null findOneByTfoot($val, array $opt=array())
 * @method static ContentModel|null findOneByTleft($val, array $opt=array())
 * @method static ContentModel|null findOneBySortable($val, array $opt=array())
 * @method static ContentModel|null findOneBySortIndex($val, array $opt=array())
 * @method static ContentModel|null findOneBySortOrder($val, array $opt=array())
 * @method static ContentModel|null findOneByCloseSections($val, array $opt=array())
 * @method static ContentModel|null findOneByMooHeadline($val, array $opt=array())
 * @method static ContentModel|null findOneByMooStyle($val, array $opt=array())
 * @method static ContentModel|null findOneByMooClasses($val, array $opt=array())
 * @method static ContentModel|null findOneByHighlight($val, array $opt=array())
 * @method static ContentModel|null findOneByMarkdownSource($val, array $opt=array())
 * @method static ContentModel|null findOneByCode($val, array $opt=array())
 * @method static ContentModel|null findOneByUrl($val, array $opt=array())
 * @method static ContentModel|null findOneByTarget($val, array $opt=array())
 * @method static ContentModel|null findOneByOverwriteLink($val, array $opt=array())
 * @method static ContentModel|null findOneByTitleText($val, array $opt=array())
 * @method static ContentModel|null findOneByLinkTitle($val, array $opt=array())
 * @method static ContentModel|null findOneByEmbed($val, array $opt=array())
 * @method static ContentModel|null findOneByRel($val, array $opt=array())
 * @method static ContentModel|null findOneByUseImage($val, array $opt=array())
 * @method static ContentModel|null findOneByMultiSRC($val, array $opt=array())
 * @method static ContentModel|null findOneByUseHomeDir($val, array $opt=array())
 * @method static ContentModel|null findOneByPerRow($val, array $opt=array())
 * @method static ContentModel|null findOneByPerPage($val, array $opt=array())
 * @method static ContentModel|null findOneByNumberOfItems($val, array $opt=array())
 * @method static ContentModel|null findOneBySortBy($val, array $opt=array())
 * @method static ContentModel|null findOneByMetaIgnore($val, array $opt=array())
 * @method static ContentModel|null findOneByGalleryTpl($val, array $opt=array())
 * @method static ContentModel|null findOneByCustomTpl($val, array $opt=array())
 * @method static ContentModel|null findOneByPlayerSRC($val, array $opt=array())
 * @method static ContentModel|null findOneByYoutube($val, array $opt=array())
 * @method static ContentModel|null findOneByVimeo($val, array $opt=array())
 * @method static ContentModel|null findOneByPosterSRC($val, array $opt=array())
 * @method static ContentModel|null findOneByPlayerSize($val, array $opt=array())
 * @method static ContentModel|null findOneByPlayerOptions($val, array $opt=array())
 * @method static ContentModel|null findOneByPlayerStart($val, array $opt=array())
 * @method static ContentModel|null findOneByPlayerStop($val, array $opt=array())
 * @method static ContentModel|null findOneByPlayerCaption($val, array $opt=array())
 * @method static ContentModel|null findOneByPlayerAspect($val, array $opt=array())
 * @method static ContentModel|null findOneBySplashImage($val, array $opt=array())
 * @method static ContentModel|null findOneByPlayerTitle($val, array $opt=array())
 * @method static ContentModel|null findOneByPlayerPreload($val, array $opt=array())
 * @method static ContentModel|null findOneByPlayerColor($val, array $opt=array())
 * @method static ContentModel|null findOneByYoutubeOptions($val, array $opt=array())
 * @method static ContentModel|null findOneByVimeoOptions($val, array $opt=array())
 * @method static ContentModel|null findOneByTextTrackSRC($val, array $opt = array())
 * @method static ContentModel|null findOneBySliderDelay($val, array $opt=array())
 * @method static ContentModel|null findOneBySliderSpeed($val, array $opt=array())
 * @method static ContentModel|null findOneBySliderStartSlide($val, array $opt=array())
 * @method static ContentModel|null findOneBySliderContinuous($val, array $opt=array())
 * @method static ContentModel|null findOneByCteAlias($val, array $opt=array())
 * @method static ContentModel|null findOneByArticleAlias($val, array $opt=array())
 * @method static ContentModel|null findOneByArticle($val, array $opt=array())
 * @method static ContentModel|null findOneByForm($val, array $opt=array())
 * @method static ContentModel|null findOneByModule($val, array $opt=array())
 * @method static ContentModel|null findOneByProtected($val, array $opt=array())
 * @method static ContentModel|null findOneByGroups($val, array $opt=array())
 * @method static ContentModel|null findOneByCssID($val, array $opt=array())
 * @method static ContentModel|null findOneBySpace($val, array $opt=array())
 * @method static ContentModel|null findOneByInvisible($val, array $opt=array())
 * @method static ContentModel|null findOneByStart($val, array $opt=array())
 * @method static ContentModel|null findOneByStop($val, array $opt=array())
 * @method static ContentModel|null findOneByShowPreview($val, array $opt=array())
 *
 * @method static Collection<ContentModel>|null findByPid($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByPtable($val, array $opt=array())
 * @method static Collection<ContentModel>|null findBySorting($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByTstamp($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByType($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByHeadline($val, array $opt=array())
 * @method static Collection<ContentModel>|null findBySectionHeadline($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByText($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByAddImage($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByInline($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByOverwriteMeta($val, array $opt=array())
 * @method static Collection<ContentModel>|null findBySingleSRC($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByAlt($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByImageTitle($val, array $opt=array())
 * @method static Collection<ContentModel>|null findBySize($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByImageUrl($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByFullsize($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByCaption($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByFloating($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByHtml($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByUnfilteredHtml($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByListtype($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByListitems($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByTableitems($val, array $opt=array())
 * @method static Collection<ContentModel>|null findBySummary($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByThead($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByTfoot($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByTleft($val, array $opt=array())
 * @method static Collection<ContentModel>|null findBySortable($val, array $opt=array())
 * @method static Collection<ContentModel>|null findBySortIndex($val, array $opt=array())
 * @method static Collection<ContentModel>|null findBySortOrder($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByCloseSections($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByMooHeadline($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByMooStyle($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByMooClasses($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByHighlight($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByMarkdownSource($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByCode($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByUrl($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByTarget($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByOverwriteLink($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByTitleText($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByLinkTitle($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByEmbed($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByRel($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByUseImage($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByMultiSRC($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByUseHomeDir($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByPerRow($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByPerPage($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByNumberOfItems($val, array $opt=array())
 * @method static Collection<ContentModel>|null findBySortBy($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByMetaIgnore($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByGalleryTpl($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByCustomTpl($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByPlayerSRC($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByYoutube($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByVimeo($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByPosterSRC($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByPlayerSize($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByPlayerOptions($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByPlayerStart($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByPlayerStop($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByPlayerCaption($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByPlayerAspect($val, array $opt=array())
 * @method static Collection<ContentModel>|null findBySplashImage($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByPlayerTitle($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByPlayerPreload($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByPlayerColor($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByYoutubeOptions($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByVimeoOptions($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByTextTrackSRC($val, array $opt = array())
 * @method static Collection<ContentModel>|null findBySliderDelay($val, array $opt=array())
 * @method static Collection<ContentModel>|null findBySliderSpeed($val, array $opt=array())
 * @method static Collection<ContentModel>|null findBySliderStartSlide($val, array $opt=array())
 * @method static Collection<ContentModel>|null findBySliderContinuous($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByCteAlias($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByArticleAlias($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByArticle($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByForm($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByModule($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByProtected($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByGroups($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByCssID($val, array $opt=array())
 * @method static Collection<ContentModel>|null findBySpace($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByInvisible($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByStart($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByStop($val, array $opt=array())
 * @method static Collection<ContentModel>|null findByShowPreview($val, array $opt=array())
 * @method static Collection<ContentModel>|null findMultipleByIds($val, array $opt=array())
 * @method static Collection<ContentModel>|null findBy($col, $val, array $opt=array())
 * @method static Collection<ContentModel>|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByPid($val, array $opt=array())
 * @method static integer countByPtable($val, array $opt=array())
 * @method static integer countBySorting($val, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByType($val, array $opt=array())
 * @method static integer countByHeadline($val, array $opt=array())
 * @method static integer countBySectionHeadline($val, array $opt=array())
 * @method static integer countByText($val, array $opt=array())
 * @method static integer countByAddImage($val, array $opt=array())
 * @method static integer countByInline($val, array $opt=array())
 * @method static integer countByOverwriteMeta($val, array $opt=array())
 * @method static integer countBySingleSRC($val, array $opt=array())
 * @method static integer countByAlt($val, array $opt=array())
 * @method static integer countByImageTitle($val, array $opt=array())
 * @method static integer countBySize($val, array $opt=array())
 * @method static integer countByImageUrl($val, array $opt=array())
 * @method static integer countByFullsize($val, array $opt=array())
 * @method static integer countByCaption($val, array $opt=array())
 * @method static integer countByFloating($val, array $opt=array())
 * @method static integer countByHtml($val, array $opt=array())
 * @method static integer countByUnfilteredHtml($val, array $opt=array())
 * @method static integer countByListtype($val, array $opt=array())
 * @method static integer countByListitems($val, array $opt=array())
 * @method static integer countByTableitems($val, array $opt=array())
 * @method static integer countBySummary($val, array $opt=array())
 * @method static integer countByThead($val, array $opt=array())
 * @method static integer countByTfoot($val, array $opt=array())
 * @method static integer countByTleft($val, array $opt=array())
 * @method static integer countBySortable($val, array $opt=array())
 * @method static integer countBySortIndex($val, array $opt=array())
 * @method static integer countBySortOrder($val, array $opt=array())
 * @method static integer countByCloseSections($val, array $opt=array())
 * @method static integer countByMooHeadline($val, array $opt=array())
 * @method static integer countByMooStyle($val, array $opt=array())
 * @method static integer countByMooClasses($val, array $opt=array())
 * @method static integer countByHighlight($val, array $opt=array())
 * @method static integer countByMarkdownSource($val, array $opt=array())
 * @method static integer countByCode($val, array $opt=array())
 * @method static integer countByUrl($val, array $opt=array())
 * @method static integer countByTarget($val, array $opt=array())
 * @method static integer countByOverwriteLink($val, array $opt=array())
 * @method static integer countByTitleText($val, array $opt=array())
 * @method static integer countByLinkTitle($val, array $opt=array())
 * @method static integer countByEmbed($val, array $opt=array())
 * @method static integer countByRel($val, array $opt=array())
 * @method static integer countByUseImage($val, array $opt=array())
 * @method static integer countByMultiSRC($val, array $opt=array())
 * @method static integer countByUseHomeDir($val, array $opt=array())
 * @method static integer countByPerRow($val, array $opt=array())
 * @method static integer countByPerPage($val, array $opt=array())
 * @method static integer countByNumberOfItems($val, array $opt=array())
 * @method static integer countBySortBy($val, array $opt=array())
 * @method static integer countByMetaIgnore($val, array $opt=array())
 * @method static integer countByGalleryTpl($val, array $opt=array())
 * @method static integer countByCustomTpl($val, array $opt=array())
 * @method static integer countByPlayerSRC($val, array $opt=array())
 * @method static integer countByYoutube($val, array $opt=array())
 * @method static integer countByVimeo($val, array $opt=array())
 * @method static integer countByPosterSRC($val, array $opt=array())
 * @method static integer countByPlayerSize($val, array $opt=array())
 * @method static integer countByPlayerOptions($val, array $opt=array())
 * @method static integer countByPlayerStart($val, array $opt=array())
 * @method static integer countByPlayerStop($val, array $opt=array())
 * @method static integer countByPlayerCaption($val, array $opt=array())
 * @method static integer countByPlayerAspect($val, array $opt=array())
 * @method static integer countBySplashImage($val, array $opt=array())
 * @method static integer countByPlayerTitle($val, array $opt=array())
 * @method static integer countByPlayerPreload($val, array $opt=array())
 * @method static integer countByPlayerColor($val, array $opt=array())
 * @method static integer countByYoutubeOptions($val, array $opt=array())
 * @method static integer countByVimeoOptions($val, array $opt=array())
 * @method static integer countByTextTrackSRC($val, array $opt = array())
 * @method static integer countBySliderDelay($val, array $opt=array())
 * @method static integer countBySliderSpeed($val, array $opt=array())
 * @method static integer countBySliderStartSlide($val, array $opt=array())
 * @method static integer countBySliderContinuous($val, array $opt=array())
 * @method static integer countByCteAlias($val, array $opt=array())
 * @method static integer countByArticleAlias($val, array $opt=array())
 * @method static integer countByArticle($val, array $opt=array())
 * @method static integer countByForm($val, array $opt=array())
 * @method static integer countByModule($val, array $opt=array())
 * @method static integer countByProtected($val, array $opt=array())
 * @method static integer countByGroups($val, array $opt=array())
 * @method static integer countByCssID($val, array $opt=array())
 * @method static integer countBySpace($val, array $opt=array())
 * @method static integer countByInvisible($val, array $opt=array())
 * @method static integer countByStart($val, array $opt=array())
 * @method static integer countByStop($val, array $opt=array())
 * @method static integer countByShowPreview($val, array $opt=array())
 */
class ContentModel extends Model
{
	use MetadataTrait;

	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_content';

	/**
	 * Find all published content elements by their parent ID and parent table
	 *
	 * @param integer $intPid         The article ID
	 * @param string  $strParentTable The parent table name
	 * @param array   $arrOptions     An optional options array
	 *
	 * @return Collection<ContentModel>|null A collection of models or null if there are no content elements
	 */
	public static function findPublishedByPidAndTable($intPid, $strParentTable, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.pid=? AND $t.ptable=?");

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.invisible=0 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		// Skip unsaved elements (see #2708)
		$arrColumns[] = "$t.tstamp!=0";

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.sorting";
		}

		return static::findBy($arrColumns, array($intPid, $strParentTable), $arrOptions);
	}

	/**
	 * Count all published content elements by their parent ID and parent table
	 *
	 * @param integer $intPid         The article ID
	 * @param string  $strParentTable The parent table name
	 * @param array   $arrOptions     An optional options array
	 *
	 * @return integer The number of matching rows
	 */
	public static function countPublishedByPidAndTable($intPid, $strParentTable, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.pid=? AND $t.ptable=?");

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.invisible=0 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		// Skip unsaved elements (see #2708)
		$arrColumns[] = "$t.tstamp!=0";

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.sorting";
		}

		return static::countBy($arrColumns, array($intPid, $strParentTable), $arrOptions);
	}
}
