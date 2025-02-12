<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Search\Backend\ReindexConfig;

/**
 * Maintenance module "rebuild backend search index".
 */
class RebuildBackendSearchIndex extends Backend implements MaintenanceModuleInterface
{
	public function isActive()
	{
		return false;
	}

	/**
	 * Generate the module
	 *
	 * @return string
	 */
	public function run()
	{
		// Not even configured, hide the section entirely
		if (!System::getContainer()->has('contao.search.backend'))
		{
			return '';
		}

		$backendSearch = System::getContainer()->get('contao.search.backend');

		if (!$backendSearch->isAvailable())
		{
			Message::addInfo(
				\sprintf(
					$GLOBALS['TL_LANG']['tl_maintenance']['backend_search']['disabled'],
					'https://to.contao.org/docs/cronjob-framework'
				),
				self::class
			);
		}

		$objTemplate = new BackendTemplate('be_rebuild_backend_search');
		$objTemplate->disabled = !$backendSearch->isAvailable();
		$objTemplate->message = Message::generateUnwrapped(self::class);

		if (Input::post('FORM_SUBMIT') == 'tl_rebuild_backend_search' && $backendSearch->isAvailable())
		{
			$jobs = System::getContainer()->get('contao.job.jobs');
			$job = $jobs->createUserJob();

			$reindexConfig = (new ReindexConfig())->withJobId($job->getUuid());
			$backendSearch->reindex($reindexConfig);
			Message::addConfirmation($GLOBALS['TL_LANG']['tl_maintenance']['backend_search']['confirmation'], self::class);
			$this->reload();
		}

		return $objTemplate->parse();
	}
}
