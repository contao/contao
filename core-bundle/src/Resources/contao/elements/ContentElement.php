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
 * Parent class for content elements.
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
 * @property string            $playerPreload
 * @property string|integer    $playerStart
 * @property string|integer    $playerStop
 * @property string            $playerCaption
 * @property string            $playerAspect
 * @property string            $playerColor
 * @property string|array|null $youtubeOptions
 * @property string|array|null $vimeoOptions
 * @property string|boolean    $splashImage
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
 * @property string|array      $cssID
 * @property string|boolean    $invisible
 * @property string|integer    $start
 * @property string|integer    $stop
 *
 * @property string  $typePrefix
 * @property string  $classes
 * @property integer $origId
 * @property string  $hl
 */
abstract class ContentElement extends Frontend
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate;

	/**
	 * Column
	 * @var string
	 */
	protected $strColumn;

	/**
	 * Model
	 * @var ContentModel
	 */
	protected $objModel;

	/**
	 * Current record
	 * @var array
	 */
	protected $arrData = array();

	/**
	 * Style array
	 * @var array
	 */
	protected $arrStyle = array();

	/**
	 * Initialize the object
	 *
	 * @param ContentModel $objElement
	 * @param string       $strColumn
	 */
	public function __construct($objElement, $strColumn='main')
	{
		if ($objElement instanceof Model || $objElement instanceof Collection)
		{
			/** @var ContentModel $objModel */
			$objModel = $objElement;

			if ($objModel instanceof Collection)
			{
				$objModel = $objModel->current();
			}

			$this->objModel = $objModel;
		}

		parent::__construct();

		$this->arrData = $objElement->row();
		$this->cssID = StringUtil::deserialize($objElement->cssID, true);

		if ($this->customTpl)
		{
			$request = System::getContainer()->get('request_stack')->getCurrentRequest();

			// Use the custom template unless it is a back end request
			if (!$request || !System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
			{
				$this->strTemplate = $this->customTpl;
			}
		}

		$arrHeadline = StringUtil::deserialize($objElement->headline);
		$this->headline = \is_array($arrHeadline) ? $arrHeadline['value'] ?? '' : $arrHeadline;
		$this->hl = $arrHeadline['unit'] ?? 'h1';
		$this->strColumn = $strColumn;
	}

	/**
	 * Set an object property
	 *
	 * @param string $strKey
	 * @param mixed  $varValue
	 */
	public function __set($strKey, $varValue)
	{
		$this->arrData[$strKey] = $varValue;
	}

	/**
	 * Return an object property
	 *
	 * @param string $strKey
	 *
	 * @return mixed
	 */
	public function __get($strKey)
	{
		return $this->arrData[$strKey] ?? parent::__get($strKey);
	}

	/**
	 * Check whether a property is set
	 *
	 * @param string $strKey
	 *
	 * @return boolean
	 */
	public function __isset($strKey)
	{
		return isset($this->arrData[$strKey]);
	}

	/**
	 * Return the model
	 *
	 * @return Model
	 */
	public function getModel()
	{
		return $this->objModel;
	}

	/**
	 * Parse the template
	 *
	 * @return string
	 */
	public function generate()
	{
		if ($this->isHidden())
		{
			return '';
		}

		$this->Template = new FrontendTemplate($this->strTemplate);
		$this->Template->setData($this->arrData);

		$this->compile();

		// Do not change this order (see #6191)
		$this->Template->style = !empty($this->arrStyle) ? implode(' ', $this->arrStyle) : '';
		$this->Template->class = trim('ce_' . $this->type . ' ' . ($this->cssID[1] ?? ''));
		$this->Template->cssID = !empty($this->cssID[0]) ? ' id="' . $this->cssID[0] . '"' : '';

		$this->Template->inColumn = $this->strColumn;

		if (!$this->Template->headline)
		{
			$this->Template->headline = $this->headline;
		}

		if (!$this->Template->hl)
		{
			$this->Template->hl = $this->hl;
		}

		if (!empty($this->objModel->classes) && \is_array($this->objModel->classes))
		{
			$this->Template->class .= ' ' . implode(' ', $this->objModel->classes);
		}

		// Tag the content element (see #2137)
		if ($this->objModel !== null)
		{
			System::getContainer()->get('contao.cache.entity_tags')->tagWithModelInstance($this->objModel);
		}

		return $this->Template->parse();
	}

	protected function isHidden()
	{
		// Skip unsaved elements (see #2708)
		if (isset($this->tstamp) && !$this->tstamp)
		{
			return true;
		}

		$isInvisible = $this->invisible || ($this->start && $this->start > time()) || ($this->stop && $this->stop <= time());

		// The element is visible, so show it
		if (!$isInvisible)
		{
			return false;
		}

		$tokenChecker = System::getContainer()->get('contao.security.token_checker');

		// Preview mode is enabled, so show the element
		if ($tokenChecker->isPreviewMode())
		{
			return false;
		}

		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		// We are in the back end, so show the element
		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			return false;
		}

		return true;
	}

	/**
	 * Compile the content element
	 */
	abstract protected function compile();

	/**
	 * Find a content element in the TL_CTE array and return the class name
	 *
	 * @param string $strName The content element name
	 *
	 * @return string The class name
	 */
	public static function findClass($strName)
	{
		foreach ($GLOBALS['TL_CTE'] as $v)
		{
			foreach ($v as $kk=>$vv)
			{
				if ($kk == $strName)
				{
					return $vv;
				}
			}
		}

		return '';
	}
}
