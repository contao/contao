<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Util\LocaleUtil;
use Contao\Model\Collection;

/**
 * Content element "player".
 */
class ContentPlayer extends ContentElement
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_player';

	/**
	 * Files object
	 * @var Collection<FilesModel>
	 */
	protected $objFiles;

	/**
	 * Return if there are no files
	 *
	 * @return string
	 */
	public function generate()
	{
		if (!$this->playerSRC)
		{
			return '';
		}

		$source = StringUtil::deserialize($this->playerSRC);

		if (empty($source) || !\is_array($source))
		{
			return '';
		}

		$objFiles = FilesModel::findMultipleByUuidsAndExtensions($source, array('mp4', 'm4v', 'mov', 'wmv', 'webm', 'ogv', 'm4a', 'mp3', 'wma', 'mpeg', 'wav', 'ogg'));

		if ($objFiles === null)
		{
			return '';
		}

		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		// Display a list of files in the back end
		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$return = '<ul>';

			while ($objFiles->next())
			{
				$objFile = new File($objFiles->path);
				$return .= '<li>' . Image::getHtml($objFile->icon, '', 'class="mime_icon"') . ' <span>' . $objFile->name . '</span> <span class="size">(' . $this->getReadableSize($objFile->size) . ')</span></li>';
			}

			$return .= '</ul>';

			if ($this->headline)
			{
				$return = '<' . $this->hl . '>' . $this->headline . '</' . $this->hl . '>' . $return;
			}

			return $return;
		}

		$this->objFiles = $objFiles;

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		global $objPage;

		$this->Template->poster = false;

		// Optional poster
		if ($this->posterSRC && ($objFile = FilesModel::findByUuid($this->posterSRC)) !== null)
		{
			$this->Template->poster = System::getContainer()->get('contao.assets.files_context')->getStaticUrl() . $objFile->path;
		}

		$objFiles = $this->objFiles;
		$objFirst = $objFiles->current();

		// Pre-sort the array by preference
		if (\in_array($objFirst->extension, array('mp4', 'm4v', 'mov', 'wmv', 'webm', 'ogv')))
		{
			$this->Template->isVideo = true;
			$this->Template->containerClass = 'video_container';

			$arrFiles = array('webm'=>null, 'mp4'=>null, 'm4v'=>null, 'mov'=>null, 'wmv'=>null, 'ogv'=>null);
		}
		else
		{
			$this->Template->isVideo = false;
			$this->Template->containerClass = 'audio_container';

			$arrFiles = array('m4a'=>null, 'mp3'=>null, 'wma'=>null, 'mpeg'=>null, 'wav'=>null, 'ogg'=>null);
		}

		// Convert the language to a locale (see #5678)
		$strLanguage = LocaleUtil::formatAsLocale($objPage->language);
		$strCaption = $this->playerCaption;

		// Pass File objects to the template
		foreach ($objFiles as $objFileModel)
		{
			$objMeta = $objFileModel->getMetadata($strLanguage);
			$strTitle = null;

			if (null !== $objMeta)
			{
				$strTitle = $objMeta->getTitle();

				if (empty($strCaption))
				{
					$strCaption = $objMeta->getCaption();
				}
			}

			$objFile = new File($objFileModel->path);

			$arrFiles[$objFile->extension] = (object) array
			(
				'title' => StringUtil::specialchars($strTitle ?: $objFile->name),
				'path' => System::getContainer()->get('contao.assets.files_context')->getStaticUrl() . $objFileModel->path,
				'mime' => $objFile->mime,
			);
		}

		$size = StringUtil::deserialize($this->playerSize);

		if (\is_array($size) && !empty($size[0]) && !empty($size[1]))
		{
			$this->Template->size = ' width="' . $size[0] . '" height="' . $size[1] . '"';
		}
		else
		{
			// $this->size might contain image size data, therefore unset it (see #2351)
			$this->Template->size = '';
		}

		$this->Template->files = array_values(array_filter($arrFiles));

		$attributes = array('controls' => 'controls');
		$options = StringUtil::deserialize($this->playerOptions);

		if (\is_array($options))
		{
			foreach ($options as $option)
			{
				if ($option == 'player_nocontrols')
				{
					unset($attributes['controls']);
				}
				else
				{
					$attributes[substr($option, 7)] = substr($option, 7);
				}
			}
		}

		$this->Template->attributes = $attributes;
		$this->Template->preload = $this->playerPreload;
		$this->Template->caption = $strCaption;

		if ($this->playerStart || $this->playerStop)
		{
			$range = '#t=';

			if ($this->playerStart)
			{
				$range .= $this->playerStart;
			}

			if ($this->playerStop)
			{
				$range .= ',' . $this->playerStop;
			}

			$this->Template->range = $range;
		}
	}
}
