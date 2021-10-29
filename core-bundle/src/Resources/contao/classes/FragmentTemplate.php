<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\EventListener\SubrequestCacheSubscriber;
use Symfony\Component\HttpFoundation\Response;

class FragmentTemplate extends FrontendTemplate
{
	/**
	 * Return a response object
	 *
	 * @return Response The response object
	 */
	public function getResponse($blnCheckRequest=false, $blnForceCacheHeaders=false)
	{
		$response = parent::getResponse();

		// Mark this response to affect the caching of the current page but remove any default cache headers
		$response->headers->set(SubrequestCacheSubscriber::MERGE_CACHE_HEADER, true);
		$response->headers->remove('Cache-Control');

		return $response;
	}

	protected function compile()
	{
		$this->strBuffer = $this->parse();
	}
}
