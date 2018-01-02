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
 * Content element "YouTube".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContentYouTube extends \ContentElement
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_youtube';


	/**
	 * Show the YouTube link in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
		if ($this->youtube == '')
		{
			return '';
		}

		if (TL_MODE == 'BE')
		{
			return '<p><a href="https://youtu.be/' . $this->youtube . '" target="_blank" rel="noopener">youtu.be/' . $this->youtube . '</a></p>';
		}

		return parent::generate();
	}


	/**
	 * Generate the module
	 */
	protected function compile()
	{
		$size = \StringUtil::deserialize($this->playerSize);

		if (!\is_array($size) || empty($size[0]) || empty($size[1]))
		{
			$this->Template->size = ' width="640" height="360"';
		}
		else
		{
			$this->Template->size = ' width="' . $size[0] . '" height="' . $size[1] . '"';
		}

		$params = array();

		if ($this->autoplay)
		{
			$params[] = 'autoplay=1';
		}

		$options = \StringUtil::deserialize($this->youtubeOptions);

		if (\is_array($options))
		{
			foreach ($options as $option)
			{
				switch ($option)
				{
					case 'youtube_fs':
					case 'youtube_rel':
					case 'youtube_showinfo':
						$params[] = substr($option, 8) . '=0';
						break;

					case 'youtube_hl':
						$params[] = substr($option, 8) . '=' . substr($GLOBALS['TL_LANGUAGE'], 0, 2);
						break;

					case 'youtube_iv_load_policy':
						$params[] = substr($option, 8) . '=3';
						break;

					default:
						$params[] = substr($option, 8) . '=1';
				}
			}
		}

		if ($this->youtubeStart > 0)
		{
			$params[] = 'start=' . (int) $this->youtubeStart;
		}

		if ($this->youtubeStop > 0)
		{
			$params[] = 'end=' . (int) $this->youtubeStop;
		}

		$url = 'https://www.youtube.com/embed/' . $this->youtube;

		if (!empty($params))
		{
			$url .= '?' . implode('&amp;', $params);
		}

		$this->Template->src = $url;
	}
}
