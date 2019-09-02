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

/**
 * Command scheduler controller.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
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
		@trigger_error('Using the "Contao\FrontendCron" class has been deprecated and will be removed in Contao 5.0. Use the Contao\CoreBundle\Cron\Cron service instead.', E_USER_DEPRECATED);

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
		$return = true;

		// Get the timestamp without seconds (see #5775)
		$time = strtotime(date('Y-m-d H:i'));

		// Lock the table
		$this->Database->lockTables(array('tl_cron'=>'WRITE'));

		// Get the last execution date
		$objCron = $this->Database->prepare("SELECT * FROM tl_cron WHERE name='lastrun'")
								  ->limit(1)
								  ->execute();

		// Add the cron entry
		if ($objCron->numRows < 1)
		{
			$this->Database->query("INSERT INTO tl_cron (name, value) VALUES ('lastrun', $time)");
			$return = false;
		}

		// Check the last execution time
		elseif ($objCron->value <= ($time - $this->getCronTimeout()))
		{
			$this->Database->query("UPDATE tl_cron SET value=$time WHERE name='lastrun'");
			$return = false;
		}

		$this->Database->unlockTables();

		return $return;
	}
}

class_alias(FrontendCron::class, 'FrontendCron');
