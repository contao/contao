<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

/**
 * Content element "Vimeo".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContentVimeo extends ContentElement
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_vimeo';

	/**
	 * Show the Vimeo link in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
		if ($this->vimeo == '')
		{
			return '';
		}

		if (TL_MODE == 'BE')
		{
			return '<p><a href="https://vimeo.com/' . $this->vimeo . '" target="_blank" rel="noreferrer noopener">vimeo.com/' . $this->vimeo . '</a></p>';
		}

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		$size = StringUtil::deserialize($this->playerSize);

		if (!\is_array($size) || empty($size[0]) || empty($size[1]))
		{
			$this->Template->size = ' width="640" height="360"';
		}
		else
		{
			$this->Template->size = ' width="' . $size[0] . '" height="' . $size[1] . '"';
		}

		$params = array();
		$options = StringUtil::deserialize($this->vimeoOptions);
		$url = 'https://player.vimeo.com/video/' . $this->vimeo;

		if (\is_array($options))
		{
			foreach ($options as $option)
			{
				switch ($option)
				{
					case 'vimeo_portrait':
					case 'vimeo_title':
					case 'vimeo_byline':
						$params[] = substr($option, 6) . '=0';
						break;

					default:
						$params[] = substr($option, 6) . '=1';
				}
			}
		}

		if ($this->playerColor)
		{
			$params[] = 'color=' . $this->playerColor;
		}

		if (!empty($params))
		{
			$url .= '?' . implode('&amp;', $params);
		}

		if ($this->playerStart > 0)
		{
			$url .= '#t=' . (int) $this->playerStart . 's';
		}

		$this->Template->src = $url;
		$this->Template->aspect = str_replace(':', '', $this->playerAspect);
		$this->Template->caption = $this->playerCaption;
	}
}

class_alias(ContentVimeo::class, 'ContentVimeo');
