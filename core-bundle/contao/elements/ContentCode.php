<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Core
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao;


/**
 * Class ContentCode
 *
 * Front end content element "code".
 * @copyright  Leo Feyer 2005-2014
 * @author     Leo Feyer <https://contao.org>
 * @package    Core
 */
class ContentCode extends \ContentElement
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_code';


	/**
	 * Show the raw code in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$return = '<pre>'. specialchars($this->code) .'</pre>';

			if ($this->headline != '')
			{
				$return = '<'. $this->hl .'>'. $this->headline .'</'. $this->hl .'>'. $return;
			}

			return $return;
		}

		return parent::generate();
	}


	/**
	 * Generate the content element
	 */
	protected function compile()
	{
		if ($this->highlight == 'C#')
		{
			$this->highlight = 'csharp';
		}
		elseif ($this->highlight == 'C++')
		{
			$this->highlight = 'cpp';
		}

		$this->Template->code = htmlspecialchars($this->code);
		$this->Template->cssClass = strtolower($this->highlight) ?: 'nohighlight';
	}
}
