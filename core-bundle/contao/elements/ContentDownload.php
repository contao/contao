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
use Contao\CoreBundle\Image\Preview\MissingPreviewProviderException;
use Contao\CoreBundle\Image\Preview\UnableToGeneratePreviewException;

/**
 * Front end content element "download".
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
		if ($this->isHidden())
		{
			return '';
		}

		// Return if there is no file
		if (!$this->singleSRC)
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
		if ($file && (Input::get('cid') === null || Input::get('cid') == $this->id))
		{
			if ($file == $objFile->path)
			{
				Controller::sendFileToBrowser($file, $this->inline);
			}

			if (Input::get('cid') !== null)
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
		$container = System::getContainer();
		$request = $container->get('request_stack')->getCurrentRequest();

		if ($request && $container->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$arrMeta = Frontend::getMetaData($this->objFile->meta, $GLOBALS['TL_LANGUAGE']);
		}
		else
		{
			/** @var PageModel $objPage */
			global $objPage;

			$arrMeta = Frontend::getMetaData($this->objFile->meta, $objPage->language);

			if (empty($arrMeta) && $objPage->rootFallbackLanguage !== null)
			{
				$arrMeta = Frontend::getMetaData($this->objFile->meta, $objPage->rootFallbackLanguage);
			}
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

		$strHref = Environment::get('requestUri');

		// Remove an existing file parameter (see #5683)
		if (Input::get('file') !== null)
		{
			$strHref = preg_replace('/(&(amp;)?|\?)file=[^&]+/', '', $strHref);
		}

		if (Input::get('cid') !== null)
		{
			$strHref = preg_replace('/(&(amp;)?|\?)cid=\d+/', '', $strHref);
		}

		$strHref .= (strpos($strHref, '?') !== false ? '&amp;' : '?') . 'file=' . System::urlEncode($objFile->value) . '&amp;cid=' . $this->id;

		$this->Template->link = $this->linkTitle ?: $objFile->basename;
		$this->Template->title = StringUtil::specialchars($this->titleText);
		$this->Template->href = $strHref;
		$this->Template->filesize = $this->getReadableSize($objFile->filesize);
		$this->Template->icon = Image::getPath($objFile->icon);
		$this->Template->mime = $objFile->mime;
		$this->Template->meta = $arrMeta;
		$this->Template->extension = $objFile->extension;
		$this->Template->path = $objFile->dirname;
		$this->Template->previews = $this->getPreviews($objFile->path, $strHref);
	}

	protected function getPreviews(string $path, string $downloadUrl): array
	{
		if (!$this->showPreview)
		{
			return array();
		}

		$container = System::getContainer();
		$factory = $container->get('contao.image.preview_factory');
		$sourcePath = $container->getParameter('kernel.project_dir') . '/' . $path;
		$builder = $container->get('contao.image.studio')->createFigureBuilder()->setSize($this->size);
		$numberOfItems = $this->numberOfItems;
		$lightboxSize = null;

		if ($this->fullsize)
		{
			if (!empty($GLOBALS['objPage']) && ($layoutId = $GLOBALS['objPage']->layout) && ($layout = LayoutModel::findByPk($layoutId)))
			{
				$lightboxSize = StringUtil::deserialize($layout->lightboxSize, true);
			}

			$builder->enableLightbox()->setLightboxGroupIdentifier('lb' . $this->id)->setLightboxSize($lightboxSize);
		}
		else
		{
			$builder->setLinkHref($downloadUrl);
		}

		try
		{
			$lightboxPreviews = array();
			$previews = $factory->createPreviews($sourcePath, $factory->getPreviewSizeFromImageSize($this->size), $numberOfItems ?: PHP_INT_MAX);
			$previews = \is_array($previews) ? array_values($previews) : iterator_to_array($previews, false);

			if ($this->fullsize)
			{
				$lightboxPreviews = $factory->createPreviews($sourcePath, $factory->getPreviewSizeFromImageSize($lightboxSize), $numberOfItems ?: PHP_INT_MAX);
				$lightboxPreviews = \is_array($lightboxPreviews) ? array_values($lightboxPreviews) : iterator_to_array($lightboxPreviews, false);
			}

			foreach ($previews as $index => $preview)
			{
				$builder->fromImage($preview);

				if (!empty($lightboxPreviews[$index]))
				{
					$builder->setLightboxResourceOrUrl($lightboxPreviews[$index]);
				}

				$previews[$index] = $builder->build();
			}

			return $previews;
		}
		catch (UnableToGeneratePreviewException|MissingPreviewProviderException $exception)
		{
			return array();
		}
	}
}
