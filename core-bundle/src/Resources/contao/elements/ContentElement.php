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
 * @property integer $id
 * @property integer $pid
 * @property string  $ptable
 * @property integer $sorting
 * @property integer $tstamp
 * @property string  $type
 * @property string  $headline
 * @property string  $text
 * @property boolean $addImage
 * @property boolean $inline
 * @property boolean $overwriteMeta
 * @property string  $singleSRC
 * @property string  $alt
 * @property string  $title
 * @property string  $size
 * @property string  $imagemargin
 * @property string  $imageUrl
 * @property boolean $fullsize
 * @property string  $caption
 * @property string  $floating
 * @property string  $html
 * @property string  $listtype
 * @property string  $listitems
 * @property string  $tableitems
 * @property string  $summary
 * @property boolean $thead
 * @property boolean $tfoot
 * @property boolean $tleft
 * @property boolean $sortable
 * @property integer $sortIndex
 * @property string  $sortOrder
 * @property string  $mooHeadline
 * @property string  $mooStyle
 * @property string  $mooClasses
 * @property string  $highlight
 * @property string  $code
 * @property string  $url
 * @property boolean $target
 * @property boolean $overwriteLink
 * @property string  $titleText
 * @property string  $linkTitle
 * @property string  $embed
 * @property string  $rel
 * @property boolean $useImage
 * @property string  $multiSRC
 * @property string  $orderSRC
 * @property boolean $useHomeDir
 * @property integer $perRow
 * @property integer $perPage
 * @property integer $numberOfItems
 * @property string  $sortBy
 * @property boolean $metaIgnore
 * @property string  $galleryTpl
 * @property string  $customTpl
 * @property string  $playerSRC
 * @property string  $youtube
 * @property string  $vimeo
 * @property string  $posterSRC
 * @property string  $playerSize
 * @property array   $playerOptions
 * @property string  $playerPreload
 * @property integer $playerStart
 * @property integer $playerStop
 * @property string  $playerCaption
 * @property string  $playerAspect
 * @property string  $playerColor
 * @property array   $youtubeOptions
 * @property array   $vimeoOptions
 * @property boolean $splashImage
 * @property integer $sliderDelay
 * @property integer $sliderSpeed
 * @property integer $sliderStartSlide
 * @property boolean $sliderContinuous
 * @property integer $cteAlias
 * @property integer $articleAlias
 * @property integer $article
 * @property integer $form
 * @property integer $module
 * @property boolean $protected
 * @property string  $groups
 * @property boolean $guests
 * @property string  $cssID
 * @property boolean $invisible
 * @property string  $start
 * @property string  $stop
 * @property string  $com_order
 * @property integer $com_perPage
 * @property boolean $com_moderate
 * @property boolean $com_bbcode
 * @property boolean $com_disableCaptcha
 * @property boolean $com_requireLogin
 * @property string  $com_template
 * @property string  $classes
 * @property string  $typePrefix
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

class_alias(ContentElement::class, 'ContentElement');
