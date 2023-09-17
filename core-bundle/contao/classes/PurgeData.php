<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Symfony\Component\Finder\Finder;

/**
 * Maintenance module "purge data".
 */
class PurgeData extends Backend implements MaintenanceModuleInterface
{
	/**
	 * Return true if the module is active
	 *
	 * @return boolean
	 */
	public function isActive()
	{
		return Input::post('FORM_SUBMIT') == 'tl_purge';
	}

	/**
	 * Generate the module
	 *
	 * @return string
	 */
	public function run()
	{
		$arrJobs = array();

		$objTemplate = new BackendTemplate('be_purge_data');
		$objTemplate->isActive = $this->isActive();
		$objTemplate->message = Message::generateUnwrapped(self::class);

		// Run the jobs
		if (Input::post('FORM_SUBMIT') == 'tl_purge')
		{
			$purge = Input::post('purge');

			if (!empty($purge) && \is_array($purge))
			{
				foreach ($purge as $group=>$jobs)
				{
					foreach ($jobs as $job)
					{
						list($class, $method) = $GLOBALS['TL_PURGE'][$group][$job]['callback'];
						System::importStatic($class)->$method();
					}
				}
			}

			Message::addConfirmation($GLOBALS['TL_LANG']['tl_maintenance']['cacheCleared'], self::class);
			$this->reload();
		}

		// Tables
		foreach ($GLOBALS['TL_PURGE']['tables'] as $key=>$config)
		{
			$arrJobs[$key] = array
			(
				'id' => 'purge_' . $key,
				'title' => $GLOBALS['TL_LANG']['tl_maintenance_jobs'][$key][0],
				'description' => $GLOBALS['TL_LANG']['tl_maintenance_jobs'][$key][1],
				'group' => 'tables',
				'affected' => ''
			);

			$db = Database::getInstance();

			// Get the current table size
			foreach ($config['affected'] as $table)
			{
				$objCount = $db->execute("SELECT COUNT(*) AS count FROM " . $table);
				$arrJobs[$key]['affected'] .= '<br>' . $table . ': <span>' . sprintf($GLOBALS['TL_LANG']['MSC']['entries'], $objCount->count) . ', ' . $this->getReadableSize($db->getSizeOf($table), 0) . '</span>';
			}
		}

		$container = System::getContainer();
		$projectDir = $container->getParameter('kernel.project_dir');
		$strCachePath = StringUtil::stripRootDir($container->getParameter('kernel.cache_dir'));

		// Folders
		foreach ($GLOBALS['TL_PURGE']['folders'] as $key=>$config)
		{
			$arrJobs[$key] = array
			(
				'id' => 'purge_' . $key,
				'title' => $GLOBALS['TL_LANG']['tl_maintenance_jobs'][$key][0],
				'description' => $GLOBALS['TL_LANG']['tl_maintenance_jobs'][$key][1],
				'group' => 'folders',
				'affected' => ''
			);

			// Get the current folder size
			foreach ($config['affected'] as $folder)
			{
				$total = 0;
				$folder = sprintf($folder, $strCachePath);

				// Only check existing folders
				if (is_dir($projectDir . '/' . $folder))
				{
					$objFiles = Finder::create()->in($projectDir . '/' . $folder)->files();

					// Do not count the deferred images JSON files
					if ($key == 'images')
					{
						$objFiles->notPath('deferred');
					}

					$total = iterator_count($objFiles);
				}

				$arrJobs[$key]['affected'] .= '<br>' . $folder . ': <span>' . sprintf($GLOBALS['TL_LANG']['MSC']['files'], $total) . '</span>';
			}
		}

		// Custom
		foreach ($GLOBALS['TL_PURGE']['custom'] as $key=>$job)
		{
			$arrJobs[$key] = array
			(
				'id' => 'purge_' . $key,
				'title' => $GLOBALS['TL_LANG']['tl_maintenance_jobs'][$key][0],
				'description' => $GLOBALS['TL_LANG']['tl_maintenance_jobs'][$key][1],
				'group' => 'custom',
				'affected' => ''
			);
		}

		$objTemplate->jobs = $arrJobs;
		$objTemplate->headline = $GLOBALS['TL_LANG']['tl_maintenance']['clearCache'];
		$objTemplate->job = $GLOBALS['TL_LANG']['tl_maintenance']['job'];
		$objTemplate->description = $GLOBALS['TL_LANG']['tl_maintenance']['description'];
		$objTemplate->submit = StringUtil::specialchars($GLOBALS['TL_LANG']['tl_maintenance']['clearCache']);
		$objTemplate->help = (Config::get('showHelp') && $GLOBALS['TL_LANG']['tl_maintenance']['cacheTables'][1]) ? $GLOBALS['TL_LANG']['tl_maintenance']['cacheTables'][1] : '';

		return $objTemplate->parse();
	}
}
