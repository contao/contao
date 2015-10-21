<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;


/**
 * Maintenance module "maintenance mode".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class Maintenance extends \Backend implements \executable
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
		/** @var \BackendTemplate|object $objTemplate */
		$objTemplate = new \BackendTemplate('be_maintenance_mode');
		$objTemplate->action = ampersand(\Environment::get('request'));
		$objTemplate->headline = $GLOBALS['TL_LANG']['tl_maintenance']['maintenanceMode'];
		$objTemplate->isActive = $this->isActive();

		try
		{
			$driver   = \System::getContainer()->get('lexik_maintenance.driver.factory')->getDriver();
			$isLocked = $driver->isExists();
		}
		catch (\Exception $e)
		{
			return '';
		}

		// Toggle the maintenance mode
		if (\Input::post('FORM_SUBMIT') == 'tl_maintenance_mode')
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
			$objTemplate->class= 'tl_confirm';
			$objTemplate->explain = $GLOBALS['TL_LANG']['MSC']['maintenanceEnabled'];
			$objTemplate->submit = $GLOBALS['TL_LANG']['tl_maintenance']['maintenanceDisable'];
		}
		else
		{
			$objTemplate->class= 'tl_info';
			$objTemplate->explain = $GLOBALS['TL_LANG']['MSC']['maintenanceDisabled'];
			$objTemplate->submit = $GLOBALS['TL_LANG']['tl_maintenance']['maintenanceEnable'];
		}

		return $objTemplate->parse();
	}
}
