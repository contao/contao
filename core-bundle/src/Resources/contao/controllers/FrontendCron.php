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
use Contao\System;
use Symfony\Component\HttpFoundation\Response;

@trigger_error('Using the "Contao\FrontendCron" class has been deprecated and will be removed in Contao 5.0. Use the Contao\CoreBundle\Cron\Cron service instead.', E_USER_DEPRECATED);

/**
 * Command scheduler controller.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
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
			System::getContainer()->get(Cron::class)->run();
		}

		return new Response('', 204);
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
