<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Contao\CoreBundle\Exception\NoContentResponseException;
use Contao\CoreBundle\Exception\ResponseException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;


/**
 * Provide methods to handle Ajax requests.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class Ajax extends \Backend
{

	/**
	 * Ajax action
	 * @var string
	 */
	protected $strAction;

	/**
	 * Ajax id
	 * @var string
	 */
	protected $strAjaxId;

	/**
	 * Ajax key
	 * @var string
	 */
	protected $strAjaxKey;

	/**
	 * Ajax name
	 * @var string
	 */
	protected $strAjaxName;


	/**
	 * Get the current action
	 *
	 * @param string $strAction
	 *
	 * @throws \Exception
	 */
	public function __construct($strAction)
	{
		if ($strAction == '')
		{
			throw new \Exception('Missing Ajax action');
		}

		$this->strAction = $strAction;
		parent::__construct();
	}


	/**
	 * Ajax actions that do not require a data container object
	 */
	public function executePreActions()
	{
		/** @var AttributeBagInterface $objSessionBag */
		$objSessionBag = \System::getContainer()->get('session')->getBag('contao_backend');

		switch ($this->strAction)
		{
			// Toggle navigation menu
			case 'toggleNavigation':
				$bemod = $objSessionBag->get('backend_modules');
				$bemod[\Input::post('id')] = intval(\Input::post('state'));
				$objSessionBag->set('backend_modules', $bemod);
				throw new NoContentResponseException();

			// Load a navigation menu group
			case 'loadNavigation':
				$bemod = $objSessionBag->get('backend_modules');
				$bemod[\Input::post('id')] = intval(\Input::post('state'));
				$objSessionBag->set('backend_modules', $bemod);

				$this->import('BackendUser', 'User');

				/** @var BackendTemplate|object $objTemplate */
				$objTemplate = new \BackendTemplate('be_navigation');
				$navigation = $this->User->navigation();
				$objTemplate->modules = $navigation[\Input::post('id')]['modules'];
				throw new ResponseException($objTemplate->getResponse());

			// Toggle nodes of the file or page tree
			case 'toggleStructure':
			case 'toggleFileManager':
			case 'togglePagetree':
			case 'toggleFiletree':
				$this->strAjaxId = preg_replace('/.*_([0-9a-zA-Z]+)$/', '$1', \Input::post('id'));
				$this->strAjaxKey = str_replace('_' . $this->strAjaxId, '', \Input::post('id'));

				if (\Input::get('act') == 'editAll')
				{
					$this->strAjaxKey = preg_replace('/(.*)_[0-9a-zA-Z]+$/', '$1', $this->strAjaxKey);
					$this->strAjaxName = preg_replace('/.*_([0-9a-zA-Z]+)$/', '$1', \Input::post('name'));
				}

				$nodes = $objSessionBag->get($this->strAjaxKey);
				$nodes[$this->strAjaxId] = intval(\Input::post('state'));
				$objSessionBag->set($this->strAjaxKey, $nodes);
				throw new NoContentResponseException();

			// Load nodes of the file or page tree
			case 'loadStructure':
			case 'loadFileManager':
			case 'loadPagetree':
			case 'loadFiletree':
				$this->strAjaxId = preg_replace('/.*_([0-9a-zA-Z]+)$/', '$1', \Input::post('id'));
				$this->strAjaxKey = str_replace('_' . $this->strAjaxId, '', \Input::post('id'));

				if (\Input::get('act') == 'editAll')
				{
					$this->strAjaxKey = preg_replace('/(.*)_[0-9a-zA-Z]+$/', '$1', $this->strAjaxKey);
					$this->strAjaxName = preg_replace('/.*_([0-9a-zA-Z]+)$/', '$1', \Input::post('name'));
				}

				$nodes = $objSessionBag->get($this->strAjaxKey);
				$nodes[$this->strAjaxId] = intval(\Input::post('state'));
				$objSessionBag->set($this->strAjaxKey, $nodes);
				break;

			// Toggle the visibility of a fieldset
			case 'toggleFieldset':
				$fs = $objSessionBag->get('fieldset_states');
				$fs[\Input::post('table')][\Input::post('id')] = intval(\Input::post('state'));
				$objSessionBag->set('fieldset_states', $fs);
				throw new NoContentResponseException();

			// Toggle checkbox groups
			case 'toggleCheckboxGroup':
				$state = $objSessionBag->get('checkbox_groups');
				$state[\Input::post('id')] = intval(\Input::post('state'));
				$objSessionBag->set('checkbox_groups', $state);
				break;

			// HOOK: pass unknown actions to callback functions
			default:
				if (isset($GLOBALS['TL_HOOKS']['executePreActions']) && is_array($GLOBALS['TL_HOOKS']['executePreActions']))
				{
					foreach ($GLOBALS['TL_HOOKS']['executePreActions'] as $callback)
					{
						$this->import($callback[0]);
						$this->{$callback[0]}->{$callback[1]}($this->strAction);
					}
				}
				break;
		}
	}


	/**
	 * Ajax actions that do require a data container object
	 *
	 * @param DataContainer $dc
	 *
	 * @throws NoContentResponseException
	 * @throws ResponseException
	 * @throws BadRequestHttpException
	 */
	public function executePostActions(DataContainer $dc)
	{
		header('Content-Type: text/html; charset=' . \Config::get('characterSet'));

		// Bypass any core logic for non-core drivers (see #5957)
		if (!($dc instanceof DC_File) && !($dc instanceof DC_Folder) && !($dc instanceof DC_Table))
		{
			$this->executePostActionsHook($dc);
			throw new NoContentResponseException();
		}

		switch ($this->strAction)
		{
			// Load nodes of the page structure tree
			case 'loadStructure':
				throw new ResponseException($this->convertToResponse($dc->ajaxTreeView($this->strAjaxId, intval(\Input::post('level')))));

			// Load nodes of the file manager tree
			case 'loadFileManager':
				throw new ResponseException($this->convertToResponse($dc->ajaxTreeView(\Input::post('folder', true), intval(\Input::post('level')))));

			// Load nodes of the page tree
			case 'loadPagetree':
				$varValue = null;
				$strField = $dc->field = \Input::post('name');

				// Call the load_callback
				if (is_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$strField]['load_callback']))
				{
					foreach ($GLOBALS['TL_DCA'][$dc->table]['fields'][$strField]['load_callback'] as $callback)
					{
						if (is_array($callback))
						{
							$this->import($callback[0]);
							$varValue = $this->{$callback[0]}->{$callback[1]}($varValue, $dc);
						}
						elseif (is_callable($callback))
						{
							$varValue = $callback($varValue, $dc);
						}
					}
				}

				/** @var PageSelector $strClass */
				$strClass = $GLOBALS['BE_FFL']['pageSelector'];

				/** @var PageSelector $objWidget */
				$objWidget = new $strClass($strClass::getAttributesFromDca($GLOBALS['TL_DCA'][$dc->table]['fields'][$strField], $dc->field, $varValue, $strField, $dc->table, $dc));

				throw new ResponseException($this->convertToResponse($objWidget->generateAjax($this->strAjaxId, \Input::post('field'), intval(\Input::post('level')))));

			// Load nodes of the file tree
			case 'loadFiletree':
				$varValue = null;
				$strField = $dc->field = \Input::post('name');

				// Call the load_callback
				if (is_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$strField]['load_callback']))
				{
					foreach ($GLOBALS['TL_DCA'][$dc->table]['fields'][$strField]['load_callback'] as $callback)
					{
						if (is_array($callback))
						{
							$this->import($callback[0]);
							$varValue = $this->{$callback[0]}->{$callback[1]}($varValue, $dc);
						}
						elseif (is_callable($callback))
						{
							$varValue = $callback($varValue, $dc);
						}
					}
				}

				/** @var FileSelector $strClass */
				$strClass = $GLOBALS['BE_FFL']['fileSelector'];

				/** @var FileSelector $objWidget */
				$objWidget = new $strClass($strClass::getAttributesFromDca($GLOBALS['TL_DCA'][$dc->table]['fields'][$strField], $dc->field, $varValue, $strField, $dc->table, $dc));

				// Load a particular node
				if (\Input::post('folder', true) != '')
				{
					throw new ResponseException($this->convertToResponse($objWidget->generateAjax(\Input::post('folder', true), \Input::post('field'), intval(\Input::post('level')))));
				}

				throw new ResponseException($this->convertToResponse($objWidget->generate()));

			// Reload the page/file picker
			case 'reloadPagetree':
			case 'reloadFiletree':
				$intId = \Input::get('id');
				$strField = $dc->inputName = \Input::post('name');

				// Handle the keys in "edit multiple" mode
				if (\Input::get('act') == 'editAll')
				{
					$intId = preg_replace('/.*_([0-9a-zA-Z]+)$/', '$1', $strField);
					$strField = preg_replace('/(.*)_[0-9a-zA-Z]+$/', '$1', $strField);
				}

				$dc->field = $strField;

				// The field does not exist
				if (!isset($GLOBALS['TL_DCA'][$dc->table]['fields'][$strField]))
				{
					$this->log('Field "' . $strField . '" does not exist in DCA "' . $dc->table . '"', __METHOD__, TL_ERROR);
					throw new BadRequestHttpException('Bad request');
				}

				$objRow = null;
				$varValue = null;

				// Load the value
				if (\Input::get('act') != 'overrideAll')
				{
					if ($GLOBALS['TL_DCA'][$dc->table]['config']['dataContainer'] == 'File')
					{
						$varValue = \Config::get($strField);
					}
					elseif ($intId > 0 && $this->Database->tableExists($dc->table))
					{
						$objRow = $this->Database->prepare("SELECT * FROM " . $dc->table . " WHERE id=?")
												 ->execute($intId);

						// The record does not exist
						if ($objRow->numRows < 1)
						{
							$this->log('A record with the ID "' . $intId . '" does not exist in table "' . $dc->table . '"', __METHOD__, TL_ERROR);
							throw new BadRequestHttpException('Bad request');
						}

						$varValue = $objRow->$strField;
						$dc->activeRecord = $objRow;
					}
				}

				// Call the load_callback
				if (is_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$strField]['load_callback']))
				{
					foreach ($GLOBALS['TL_DCA'][$dc->table]['fields'][$strField]['load_callback'] as $callback)
					{
						if (is_array($callback))
						{
							$this->import($callback[0]);
							$varValue = $this->{$callback[0]}->{$callback[1]}($varValue, $dc);
						}
						elseif (is_callable($callback))
						{
							$varValue = $callback($varValue, $dc);
						}
					}
				}

				// Set the new value
				$varValue = \Input::post('value', true);
				$strKey = ($this->strAction == 'reloadPagetree') ? 'pageTree' : 'fileTree';

				// Convert the selected values
				if ($varValue != '')
				{
					$varValue = \StringUtil::trimsplit("\t", $varValue);

					// Automatically add resources to the DBAFS
					if ($strKey == 'fileTree')
					{
						foreach ($varValue as $k=>$v)
						{
							if (\Dbafs::shouldBeSynchronized($v))
							{
								$objFile = \FilesModel::findByPath($v);

								if ($objFile === null)
								{
									$objFile = \Dbafs::addResource($v);
								}

								$varValue[$k] = $objFile->uuid;
							}
						}
					}

					$varValue = serialize($varValue);
				}

				/** @var FileTree|PageTree $strClass */
				$strClass = $GLOBALS['BE_FFL'][$strKey];

				/** @var FileTree|PageTree $objWidget */
				$objWidget = new $strClass($strClass::getAttributesFromDca($GLOBALS['TL_DCA'][$dc->table]['fields'][$strField], $dc->inputName, $varValue, $strField, $dc->table, $dc));

				throw new ResponseException($this->convertToResponse($objWidget->generate()));

			// Feature/unfeature an element
			case 'toggleFeatured':
				if (class_exists($dc->table, false))
				{
					$dca = new $dc->table();

					if (method_exists($dca, 'toggleFeatured'))
					{
						$dca->toggleFeatured(\Input::post('id'), ((\Input::post('state') == 1) ? true : false));
					}
				}

				throw new NoContentResponseException();

			// Toggle subpalettes
			case 'toggleSubpalette':
				$this->import('BackendUser', 'User');

				// Check whether the field is a selector field and allowed for regular users (thanks to Fabian Mihailowitsch) (see #4427)
				if (!is_array($GLOBALS['TL_DCA'][$dc->table]['palettes']['__selector__']) || !in_array(\Input::post('field'), $GLOBALS['TL_DCA'][$dc->table]['palettes']['__selector__']) || ($GLOBALS['TL_DCA'][$dc->table]['fields'][\Input::post('field')]['exclude'] && !$this->User->hasAccess($dc->table . '::' . \Input::post('field'), 'alexf')))
				{
					$this->log('Field "' . \Input::post('field') . '" is not an allowed selector field (possible SQL injection attempt)', __METHOD__, TL_ERROR);
					throw new BadRequestHttpException('Bad request');
				}

				if ($dc instanceof DC_Table)
				{
					if (\Input::get('act') == 'editAll')
					{
						$this->strAjaxId = preg_replace('/.*_([0-9a-zA-Z]+)$/', '$1', \Input::post('id'));
						$this->Database->prepare("UPDATE " . $dc->table . " SET " . \Input::post('field') . "='" . (intval(\Input::post('state') == 1) ? 1 : '') . "' WHERE id=?")->execute($this->strAjaxId);

						if (\Input::post('load'))
						{
							echo $dc->editAll($this->strAjaxId, \Input::post('id'));
						}
					}
					else
					{
						$this->Database->prepare("UPDATE " . $dc->table . " SET " . \Input::post('field') . "='" . (intval(\Input::post('state') == 1) ? 1 : '') . "' WHERE id=?")->execute($dc->id);

						if (\Input::post('load'))
						{
							throw new ResponseException($this->convertToResponse($dc->edit(false, \Input::post('id'))));
						}
					}
				}
				elseif ($dc instanceof DC_File)
				{
					$val = (intval(\Input::post('state') == 1) ? true : false);
					\Config::persist(\Input::post('field'), $val);

					if (\Input::post('load'))
					{
						\Config::set(\Input::post('field'), $val);

						throw new ResponseException($this->convertToResponse($dc->edit(false, \Input::post('id'))));
					}
				}

				throw new NoContentResponseException();

			// DropZone file upload
			case 'fileupload':
				$dc->move();
				throw new NoContentResponseException();

			// HOOK: pass unknown actions to callback functions
			default:
				$this->executePostActionsHook($dc);
				throw new NoContentResponseException();
		}
	}


	/**
	 * Execute the post actions hook
	 *
	 * @param DataContainer $dc
	 */
	protected function executePostActionsHook(DataContainer $dc)
	{
		if (isset($GLOBALS['TL_HOOKS']['executePostActions']) && is_array($GLOBALS['TL_HOOKS']['executePostActions']))
		{
			foreach ($GLOBALS['TL_HOOKS']['executePostActions'] as $callback)
			{
				$this->import($callback[0]);
				$this->{$callback[0]}->{$callback[1]}($this->strAction, $dc);
			}
		}
	}


	/**
	 * Convert a string to a response object
	 *
	 * @param string $str
	 *
	 * @return Response
	 */
	protected function convertToResponse($str)
	{
		return new Response(\Controller::replaceOldBePaths($str));
	}
}
