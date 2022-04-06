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
 * Back end module "maintenance".
 */
class ModuleMaintenance extends BackendModule
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'be_maintenance';

	/**
	 * Generate the module
	 *
	 * @throws \Exception
	 */
	protected function compile()
	{
		System::loadLanguageFile('tl_maintenance');

		$this->Template->content = '';
		$this->Template->href = $this->getReferer(true);
		$this->Template->title = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']);
		$this->Template->button = $GLOBALS['TL_LANG']['MSC']['backBT'];

		foreach ($GLOBALS['TL_MAINTENANCE'] as $callback)
		{
			$this->import($callback);

			if (!$this->$callback instanceof MaintenanceModuleInterface)
			{
				throw new \Exception("$callback is not an executable class");
			}

			$buffer = $this->$callback->run();

			if ($this->$callback->isActive())
			{
				$this->Template->content = $buffer;
				break;
			}

			$this->Template->content .= $buffer;
		}
	}
}
