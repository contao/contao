<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\File\ModelMetadataTrait;
use Contao\Model\Collection;

/**
 * Reads and writes content elements
 *
 * @property string|integer    $id
 * @property string|integer    $pid
 * @property string            $ptable
 * @property string|integer    $sorting
 * @property string|integer    $tstamp
 * @property string            $type
 * @property string            $headline
 * @property string|null       $text
 * @property string|boolean    $addImage
 * @property string|boolean    $inline
 * @property string|boolean    $overwriteMeta
 * @property string|null       $singleSRC
 * @property string            $alt
 * @property string            $imageTitle
 * @property string|integer    $size
 * @property string|array      $imagemargin
 * @property string            $imageUrl
 * @property string|boolean    $fullsize
 * @property string            $caption
 * @property string            $floating
 * @property string|null       $html
 * @property string            $listtype
 * @property string|array|null $listitems
 * @property string|array|null $tableitems
 * @property string            $summary
 * @property string|boolean    $thead
 * @property string|boolean    $tfoot
 * @property string|boolean    $tleft
 * @property string|boolean    $sortable
 * @property string|integer    $sortIndex
 * @property string            $sortOrder
 * @property string            $mooHeadline
 * @property string            $mooStyle
 * @property string            $mooClasses
 * @property string            $highlight
 * @property string            $markdownSource
 * @property string|null       $code
 * @property string            $url
 * @property string|boolean    $target
 * @property string|boolean    $overwriteLink
 * @property string            $titleText
 * @property string            $linkTitle
 * @property string            $embed
 * @property string            $rel
 * @property string|boolean    $useImage
 * @property string|array|null $multiSRC
 * @property string|array|null $orderSRC
 * @property string|boolean    $useHomeDir
 * @property string|integer    $perRow
 * @property string|integer    $perPage
 * @property string|integer    $numberOfItems
 * @property string            $sortBy
 * @property string|boolean    $metaIgnore
 * @property string            $galleryTpl
 * @property string            $customTpl
 * @property string|null       $playerSRC
 * @property string            $youtube
 * @property string            $vimeo
 * @property string|null       $posterSRC
 * @property string|array      $playerSize
 * @property string|array|null $playerOptions
 * @property string|integer    $playerStart
 * @property string|integer    $playerStop
 * @property string            $playerCaption
 * @property string            $playerAspect
 * @property string|boolean    $splashImage
 * @property string            $playerPreload
 * @property string            $playerColor
 * @property string|array|null $youtubeOptions
 * @property string|array|null $vimeoOptions
 * @property string|integer    $sliderDelay
 * @property string|integer    $sliderSpeed
 * @property string|integer    $sliderStartSlide
 * @property string|boolean    $sliderContinuous
 * @property string|integer    $cteAlias
 * @property string|integer    $articleAlias
 * @property string|integer    $article
 * @property string|integer    $form
 * @property string|integer    $module
 * @property string|boolean    $protected
 * @property string|array|null $groups
 * @property string|boolean    $guests
 * @property string|array      $cssID
 * @property string|boolean    $invisible
 * @property string|integer    $start
 * @property string|integer    $stop
 * @property string|boolean    $showPreview
 *
 * @property string         $typePrefix
 * @property array          $classes
 * @property string|integer $origId
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
 * @method static ContentModel|null findOneByText($val, array $opt=array())
 * @method static ContentModel|null findOneByAddImage($val, array $opt=array())
 * @method static ContentModel|null findOneByInline($val, array $opt=array())
 * @method static ContentModel|null findOneByOverwriteMeta($val, array $opt=array())
 * @method static ContentModel|null findOneBySingleSRC($val, array $opt=array())
 * @method static ContentModel|null findOneByAlt($val, array $opt=array())
 * @method static ContentModel|null findOneByImageTitle($val, array $opt=array())
 * @method static ContentModel|null findOneBySize($val, array $opt=array())
 * @method static ContentModel|null findOneByImagemargin($val, array $opt=array())
 * @method static ContentModel|null findOneByImageUrl($val, array $opt=array())
 * @method static ContentModel|null findOneByFullsize($val, array $opt=array())
 * @method static ContentModel|null findOneByCaption($val, array $opt=array())
 * @method static ContentModel|null findOneByFloating($val, array $opt=array())
 * @method static ContentModel|null findOneByHtml($val, array $opt=array())
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
 * @method static ContentModel|null findOneByOrderSRC($val, array $opt=array())
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
 * @method static ContentModel|null findOneByPlayerPreload($val, array $opt=array())
 * @method static ContentModel|null findOneByPlayerColor($val, array $opt=array())
 * @method static ContentModel|null findOneByYoutubeOptions($val, array $opt=array())
 * @method static ContentModel|null findOneByVimeoOptions($val, array $opt=array())
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
 * @method static ContentModel|null findOneByGuests($val, array $opt=array())
 * @method static ContentModel|null findOneByCssID($val, array $opt=array())
 * @method static ContentModel|null findOneBySpace($val, array $opt=array())
 * @method static ContentModel|null findOneByInvisible($val, array $opt=array())
 * @method static ContentModel|null findOneByStart($val, array $opt=array())
 * @method static ContentModel|null findOneByStop($val, array $opt=array())
 * @method static ContentModel|null findOneByShowPreview($val, array $opt=array())
 *
 * @method static Collection|ContentModel[]|ContentModel|null findByPid($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByPtable($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findBySorting($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByTstamp($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByType($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByHeadline($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByText($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByAddImage($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByInline($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByOverwriteMeta($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findBySingleSRC($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByAlt($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByImageTitle($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findBySize($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByImagemargin($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByImageUrl($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByFullsize($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByCaption($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByFloating($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByHtml($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByListtype($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByListitems($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByTableitems($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findBySummary($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByThead($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByTfoot($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByTleft($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findBySortable($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findBySortIndex($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findBySortOrder($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByMooHeadline($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByMooStyle($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByMooClasses($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByHighlight($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByMarkdownSource($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByCode($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByUrl($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByTarget($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByOverwriteLink($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByTitleText($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByLinkTitle($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByEmbed($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByRel($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByUseImage($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByMultiSRC($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByOrderSRC($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByUseHomeDir($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByPerRow($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByPerPage($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByNumberOfItems($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findBySortBy($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByMetaIgnore($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByGalleryTpl($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByCustomTpl($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByPlayerSRC($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByYoutube($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByVimeo($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByPosterSRC($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByPlayerSize($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByPlayerOptions($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByPlayerStart($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByPlayerStop($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByPlayerCaption($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByPlayerAspect($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findBySplashImage($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByPlayerPreload($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByPlayerColor($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByYoutubeOptions($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByVimeoOptions($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findBySliderDelay($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findBySliderSpeed($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findBySliderStartSlide($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findBySliderContinuous($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByCteAlias($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByArticleAlias($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByArticle($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByForm($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByModule($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByProtected($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByGroups($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByGuests($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByCssID($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findBySpace($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByInvisible($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByStart($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByStop($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findByShowPreview($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findMultipleByIds($val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findBy($col, $val, array $opt=array())
 * @method static Collection|ContentModel[]|ContentModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByPid($val, array $opt=array())
 * @method static integer countByPtable($val, array $opt=array())
 * @method static integer countBySorting($val, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByType($val, array $opt=array())
 * @method static integer countByHeadline($val, array $opt=array())
 * @method static integer countByText($val, array $opt=array())
 * @method static integer countByAddImage($val, array $opt=array())
 * @method static integer countByInline($val, array $opt=array())
 * @method static integer countByOverwriteMeta($val, array $opt=array())
 * @method static integer countBySingleSRC($val, array $opt=array())
 * @method static integer countByAlt($val, array $opt=array())
 * @method static integer countByImageTitle($val, array $opt=array())
 * @method static integer countBySize($val, array $opt=array())
 * @method static integer countByImagemargin($val, array $opt=array())
 * @method static integer countByImageUrl($val, array $opt=array())
 * @method static integer countByFullsize($val, array $opt=array())
 * @method static integer countByCaption($val, array $opt=array())
 * @method static integer countByFloating($val, array $opt=array())
 * @method static integer countByHtml($val, array $opt=array())
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
 * @method static integer countByOrderSRC($val, array $opt=array())
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
 * @method static integer countByPlayerPreload($val, array $opt=array())
 * @method static integer countByPlayerColor($val, array $opt=array())
 * @method static integer countByYoutubeOptions($val, array $opt=array())
 * @method static integer countByVimeoOptions($val, array $opt=array())
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
 * @method static integer countByGuests($val, array $opt=array())
 * @method static integer countByCssID($val, array $opt=array())
 * @method static integer countBySpace($val, array $opt=array())
 * @method static integer countByInvisible($val, array $opt=array())
 * @method static integer countByStart($val, array $opt=array())
 * @method static integer countByStop($val, array $opt=array())
 * @method static integer countByShowPreview($val, array $opt=array())
 */
class ContentModel extends Model
{
	use ModelMetadataTrait;

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
	 * @return Collection|ContentModel[]|ContentModel|null A collection of models or null if there are no content elements
	 */
	public static function findPublishedByPidAndTable($intPid, $strParentTable, array $arrOptions=array())
	{
		$t = static::$strTable;

		// Also handle empty ptable fields
		if ($strParentTable == 'tl_article')
		{
			$arrColumns = array("$t.pid=? AND ($t.ptable=? OR $t.ptable='')");
		}
		else
		{
			$arrColumns = array("$t.pid=? AND $t.ptable=?");
		}

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.invisible='' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')";
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
	 * Find all published content elements by their parent ID and parent table
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

		// Also handle empty ptable fields (backwards compatibility)
		if ($strParentTable == 'tl_article')
		{
			$arrColumns = array("$t.pid=? AND ($t.ptable=? OR $t.ptable='')");
		}
		else
		{
			$arrColumns = array("$t.pid=? AND $t.ptable=?");
		}

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.invisible='' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')";
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

class_alias(ContentModel::class, 'ContentModel');
