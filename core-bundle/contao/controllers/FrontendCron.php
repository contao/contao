<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Cron\Cron;
use Symfony\Component\HttpFoundation\Response;

trigger_deprecation('contao/core-bundle', '4.9', 'Using the "Contao\FrontendCron" class has been deprecated and will no longer work in Contao 5.0. Use the Contao\CoreBundle\Cron\Cron service instead.');

/**
 * Command scheduler controller.
 *
 * @deprecated Deprecated since Contao 4.9, to be removed in Contao 5.0; use the
 *             Contao\CoreBundle\Cron\Cron service instead
 */
class FrontendCron extends Frontend
{
	/**
	 * Run the controller
	 *
	 * @return Response
	 */
	public function run()
	{
		// Do not run if there is POST data
		if (empty($_POST))
		{
			System::getContainer()->get('contao.cron')->run(Cron::SCOPE_WEB);
		}

		return new Response('', Response::HTTP_NO_CONTENT);
	}

	/**
	 * Check whether the last script execution was less than a minute ago
	 *
	 * @return boolean
	 */
	protected function hasToWait()
	{
		return false;
	}
}

class_alias(FrontendCron::class, 'FrontendCron');
