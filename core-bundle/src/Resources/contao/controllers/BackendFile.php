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
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

trigger_deprecation('contao/core-bundle', '4.13', 'Using the "Contao\BackendFile" class has been deprecated and will no longer work in Contao 5.0. Use the picker instead.');

/**
 * Back end file picker.
 *
 * @deprecated Deprecated since Contao 4.13, to be removed in Contao 5.0.
 *             Use the picker instead.
 */
class BackendFile extends Backend
{
	/**
	 * Current Ajax object
	 * @var Ajax
	 */
	protected $objAjax;

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
		$this->import(BackendUser::class, 'User');
		parent::__construct();

		if (!System::getContainer()->get('security.authorization_checker')->isGranted('ROLE_USER'))
		{
			throw new AccessDeniedException('Access denied');
		}

		System::loadLanguageFile('default');
	}

	/**
	 * Run the controller and parse the template
	 *
	 * @return Response
	 */
	public function run()
	{
		$container = System::getContainer();
		$objSession = $container->get('session');

		$objTemplate = new BackendTemplate('be_picker');
		$objTemplate->main = '';

		// Ajax request
		if ($_POST && Environment::get('isAjaxRequest'))
		{
			$this->objAjax = new Ajax(Input::post('action'));
			$this->objAjax->executePreActions();
		}

		$strTable = Input::get('table');
		$strField = Input::get('field');

		$id = $this->findCurrentId($strTable);
		$objSession->set('CURRENT_ID', $id);

		// Define the current ID
		\define('CURRENT_ID', (Input::get('table') ? $id : Input::get('id')));

		$this->loadDataContainer($strTable);
		$strDriver = DataContainer::getDriverForTable($strTable);
		$objDca = new $strDriver($strTable);
		$objDca->field = $strField;

		// Set the active record
		if ($this->Database->tableExists($strTable))
		{
			$strModel = Model::getClassFromTable($strTable);

			if (class_exists($strModel))
			{
				/** @var Model|null $objModel */
				$objModel = $strModel::findByPk(Input::get('id'));

				if ($objModel !== null)
				{
					$objDca->activeRecord = $objModel;
				}
			}
		}

		// AJAX request
		if ($_POST && Environment::get('isAjaxRequest'))
		{
			$this->objAjax->executePostActions($objDca);
		}

		$objSession->set('filePickerRef', Environment::get('request'));
		$arrValues = array_filter(explode(',', Input::get('value')));

		// Convert UUIDs to binary
		foreach ($arrValues as $k=>$v)
		{
			// Can be a UUID or a path
			if (Validator::isStringUuid($v))
			{
				$arrValues[$k] = StringUtil::uuidToBin($v);
			}
		}

		// Call the load_callback
		if (\is_array($GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['load_callback'] ?? null))
		{
			foreach ($GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['load_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					$this->import($callback[0]);
					$arrValues = $this->{$callback[0]}->{$callback[1]}($arrValues, $objDca);
				}
				elseif (\is_callable($callback))
				{
					$arrValues = $callback($arrValues, $objDca);
				}
			}
		}

		/** @var FileSelector $strClass */
		$strClass = $GLOBALS['BE_FFL']['fileSelector'] ?? null;

		/** @var FileSelector $objFileTree */
		$objFileTree = new $strClass($strClass::getAttributesFromDca($GLOBALS['TL_DCA'][$strTable]['fields'][$strField], $strField, $arrValues, $strField, $strTable, $objDca));

		/** @var AttributeBagInterface $objSessionBag */
		$objSessionBag = $objSession->getBag('contao_backend');

		$objTemplate->main = $objFileTree->generate();
		$objTemplate->theme = Backend::getTheme();
		$objTemplate->base = Environment::get('base');
		$objTemplate->language = $GLOBALS['TL_LANGUAGE'];
		$objTemplate->title = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['filepicker']);
		$objTemplate->host = Backend::getDecodedHostname();
		$objTemplate->charset = $container->getParameter('kernel.charset');
		$objTemplate->addSearch = true;
		$objTemplate->search = $GLOBALS['TL_LANG']['MSC']['search'];
		$objTemplate->searchExclude = $GLOBALS['TL_LANG']['MSC']['searchExclude'];
		$objTemplate->value = $objSessionBag->get('file_selector_search');
		$objTemplate->breadcrumb = $GLOBALS['TL_DCA']['tl_files']['list']['sorting']['breadcrumb'] ?? null;

		$security = $container->get('security.helper');

		if ($security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'files'))
		{
			$objTemplate->manager = $GLOBALS['TL_LANG']['MSC']['fileManager'];
			$objTemplate->managerHref = $container->get('router')->generate('contao_backend', array('do'=>'files', 'popup'=>'1'));
		}

		if (Input::get('switch') && $security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'page'))
		{
			$objTemplate->switch = $GLOBALS['TL_LANG']['MSC']['pagePicker'];
			$objTemplate->switchHref = str_replace('contao/file?', 'contao/page?', StringUtil::ampersand(Environment::get('request')));
		}

		return $objTemplate->getResponse();
	}
}

class_alias(BackendFile::class, 'BackendFile');
