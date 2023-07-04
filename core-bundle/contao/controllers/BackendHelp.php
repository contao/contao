<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Back end help wizard.
 */
class BackendHelp extends Backend
{
	/**
	 * Initialize the controller
	 *
	 * 1. Import the user
	 * 2. Call the parent constructor
	 * 3. Authenticate the user
	 * 4. Load the language files
	 * DO NOT CHANGE THIS ORDER!
	 */
	public function __construct()
	{
		parent::__construct();

		if (!System::getContainer()->get('security.authorization_checker')->isGranted('ROLE_USER'))
		{
			throw new AccessDeniedException('Access denied');
		}

		System::loadLanguageFile('default');
		System::loadLanguageFile('modules');
	}

	/**
	 * Run the controller and parse the template
	 *
	 * @return Response
	 */
	public function run()
	{
		$table = Input::get('table');
		$field = Input::get('field');

		System::loadLanguageFile($table);
		$this->loadDataContainer($table);

		$objTemplate = new BackendTemplate('be_help');
		$objTemplate->rows = array();
		$objTemplate->explanation = '';

		$arrData = $GLOBALS['TL_DCA'][$table]['fields'][$field] ?? array();

		// Add the reference
		if (!empty($arrData['reference']))
		{
			$rows = array();

			if (\is_array($arrData['options'] ?? null))
			{
				$options = $arrData['options'];
			}
			elseif (\is_array($arrData['options_callback'] ?? null))
			{
				$options = System::importStatic($arrData['options_callback'][0])->{$arrData['options_callback'][1]}(new DC_Table($table));
			}
			elseif (\is_callable($arrData['options_callback'] ?? null))
			{
				$options = $arrData['options_callback']();
			}
			else
			{
				$options = array_keys($arrData['reference']);
			}

			foreach ($options as $key=>$option)
			{
				if (\is_array($option))
				{
					if (empty($option) || !isset($arrData['reference'][$key]))
					{
						continue;
					}

					if (\is_array($arrData['reference'][$key]))
					{
						$rows[] = array('headspan', $arrData['reference'][$key][0]);
					}
					else
					{
						$rows[] = array('headspan', $arrData['reference'][$key]);
					}

					foreach ($option as $opt)
					{
						$rows[] = $arrData['reference'][$opt] ?? null;
					}
				}
				elseif (isset($arrData['reference'][$key]))
				{
					$rows[] = $arrData['reference'][$key];
				}
				elseif (\is_array($arrData['reference'][$option] ?? null))
				{
					$rows[] = $arrData['reference'][$option];
				}
				else
				{
					$rows[] = array('headspan', $arrData['reference'][$option] ?? null);
				}
			}

			$objTemplate->rows = $rows;
		}

		// Add an explanation
		if (isset($arrData['explanation']))
		{
			System::loadLanguageFile('explain');
			$key = $arrData['explanation'];

			if (isset($GLOBALS['TL_LANG']['XPL'][$key]))
			{
				if (\is_array($GLOBALS['TL_LANG']['XPL'][$key]))
				{
					$objTemplate->rows = $GLOBALS['TL_LANG']['XPL'][$key];
				}
				else
				{
					$objTemplate->explanation = trim($GLOBALS['TL_LANG']['XPL'][$key]);
				}
			}
		}

		$objTemplate->theme = Backend::getTheme();
		$objTemplate->language = $GLOBALS['TL_LANGUAGE'];
		$objTemplate->title = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['helpWizardTitle']);
		$objTemplate->host = Backend::getDecodedHostname();
		$objTemplate->charset = System::getContainer()->getParameter('kernel.charset');
		$objTemplate->headline = $arrData['label'][0] ?? $field;
		$objTemplate->helpWizard = $GLOBALS['TL_LANG']['MSC']['helpWizard'];

		return $objTemplate->getResponse();
	}
}
