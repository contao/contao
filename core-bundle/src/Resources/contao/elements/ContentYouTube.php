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
			return '<p><a href="https://youtu.be/' . $this->youtube . '" target="_blank">youtu.be/' . $this->youtube . '</a></p>';
		}

		return parent::generate();
	}


	/**
	 * Generate the module
	 */
	protected function compile()
	{
		$size = \StringUtil::deserialize($this->playerSize);

		if (!is_array($size) || empty($size[0]) || empty($size[1]))
		{
			$this->Template->size = ' width="640" height="360"';
		}
		else
		{
			$this->Template->size = ' width="' . $size[0] . '" height="' . $size[1] . '"';
		}

		$url = 'https://www.youtube.com/embed/' . $this->youtube;

		if ($this->autoplay)
		{
			$url .= '?autoplay=1';
		}

		$this->Template->src = $url;
	}
}
