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
 * Maintenance module "maintenance mode".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class Maintenance extends Backend implements \executable
{

	/**
	 * Return true if the module is active
	 *
	 * @return boolean
	 */
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
		$objTemplate = new BackendTemplate('be_maintenance_mode');
		$objTemplate->action = ampersand(Environment::get('request'));
		$objTemplate->headline = $GLOBALS['TL_LANG']['tl_maintenance']['maintenanceMode'];
		$objTemplate->isActive = $this->isActive();

		try
		{
			$driver = System::getContainer()->get('lexik_maintenance.driver.factory')->getDriver();
			$isLocked = $driver->isExists();
		}
		catch (\Exception $e)
		{
			return '';
		}

		// Toggle the maintenance mode
		if (Input::post('FORM_SUBMIT') == 'tl_maintenance_mode')
		{
			if ($isLocked)
			{
				$driver->unlock();
			}
			else
			{
				$driver->lock();
			}

			$this->reload();
		}

		if ($isLocked)
		{
			$objTemplate->class= 'tl_error';
			$objTemplate->explain = $GLOBALS['TL_LANG']['MSC']['maintenanceEnabled'];
			$objTemplate->submit = $GLOBALS['TL_LANG']['MSC']['disable'];
		}
		else
		{
			$objTemplate->class= 'tl_info';
			$objTemplate->submit = $GLOBALS['TL_LANG']['MSC']['enable'];
		}

		return $objTemplate->parse();
	}
}

class_alias(Maintenance::class, 'Maintenance');
