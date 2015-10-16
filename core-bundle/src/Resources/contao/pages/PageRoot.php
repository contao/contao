<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Contao\CoreBundle\Exception\NoActivePageFoundException;


/**
 * Provide methods to handle a website root page.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PageRoot extends \Frontend
{

	/**
	 * Redirect to the first active regular page
	 *
	 * @param integer $rootPageId
	 * @param boolean $blnReturn
	 * @param boolean $blnPreferAlias
	 *
	 * @return integer
	 */
	public function generate($rootPageId, $blnReturn=false, $blnPreferAlias=false)
	{
		$objNextPage = \PageModel::findFirstPublishedByPid($rootPageId);

		// No published pages yet
		if (null === $objNextPage)
		{
			$this->log('No active page found under root page "' . $rootPageId . '")', __METHOD__, TL_ERROR);
			throw new NoActivePageFoundException('No active page found under root page.');
		}

		if (!$blnReturn)
		{
			/** @var \PageModel $objPage */
			global $objPage;

			$this->redirect($this->generateFrontendUrl($objNextPage->row(), null, $objPage->language));
		}

		if ($blnPreferAlias && $objNextPage->alias != '')
		{
			return $objNextPage->alias;
		}

		return $objNextPage->id;
	}
}
