<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Exception\PageNotFoundException;

/**
 * Front end content element "download".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContentDownload extends ContentElement
{

	/**
	 * @var FilesModel
	 */
	protected $objFile;

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_download';

	/**
	 * Return if the file does not exist
	 *
	 * @return string
	 */
	public function generate()
	{
		// Return if there is no file
		if ($this->singleSRC == '')
		{
			return '';
		}

		$objFile = FilesModel::findByUuid($this->singleSRC);

		if ($objFile === null)
		{
			return '';
		}

		$allowedDownload = StringUtil::trimsplit(',', strtolower(Config::get('allowedDownload')));

		// Return if the file type is not allowed
		if (!\in_array($objFile->extension, $allowedDownload))
		{
			return '';
		}

		$file = Input::get('file', true);

		// Send the file to the browser (see #4632 and #8375)
		if ($file && (!isset($_GET['cid']) || Input::get('cid') == $this->id))
		{
			if ($file == $objFile->path)
			{
				Controller::sendFileToBrowser($file, (bool) $this->inline);
			}

			if (isset($_GET['cid']))
			{
				throw new PageNotFoundException('Invalid file name');
			}
		}

		$this->objFile = $objFile;
		$this->singleSRC = $objFile->path;

		return parent::generate();
	}

	/**
	 * Generate the content element
	 */
	protected function compile()
	{
		$objFile = new File($this->singleSRC);

		if (TL_MODE == 'FE')
		{
			global $objPage;

			$arrMeta = Frontend::getMetaData($this->objFile->meta, $objPage->language);

			if (empty($arrMeta) && $objPage->rootFallbackLanguage !== null)
			{
				$arrMeta = Frontend::getMetaData($this->objFile->meta, $objPage->rootFallbackLanguage);
			}
		}
		else
		{
			$arrMeta = Frontend::getMetaData($this->objFile->meta, $GLOBALS['TL_LANGUAGE']);
		}

		// Use the meta title (see #1459)
		if (!$this->overwriteLink && isset($arrMeta['title']))
		{
			$this->linkTitle = StringUtil::specialchars($arrMeta['title']);
		}

		if (!$this->titleText || !$this->overwriteLink)
		{
			$this->titleText = sprintf($GLOBALS['TL_LANG']['MSC']['download'], $objFile->basename);
		}

		$strHref = Environment::get('request');

		// Remove an existing file parameter (see #5683)
		if (isset($_GET['file']))
		{
			$strHref = preg_replace('/(&(amp;)?|\?)file=[^&]+/', '', $strHref);
		}

		if (isset($_GET['cid']))
		{
			$strHref = preg_replace('/(&(amp;)?|\?)cid=\d+/', '', $strHref);
		}

		$strHref .= (strpos($strHref, '?') !== false ? '&amp;' : '?') . 'file=' . System::urlEncode($objFile->value) . '&amp;cid=' . $this->id;

		$this->Template->link = $this->linkTitle ?: $objFile->basename;
		$this->Template->title = StringUtil::specialchars($this->titleText);
		$this->Template->href = $strHref;
		$this->Template->filesize = $this->getReadableSize($objFile->filesize, 1);
		$this->Template->icon = Image::getPath($objFile->icon);
		$this->Template->mime = $objFile->mime;
		$this->Template->extension = $objFile->extension;
		$this->Template->path = $objFile->dirname;
	}
}

class_alias(ContentDownload::class, 'ContentDownload');
