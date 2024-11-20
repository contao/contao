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
use Contao\CoreBundle\Exception\ResponseException;

/**
 * Front end module "personal data".
 *
 * @property array $editable
 */
class ModulePersonalData extends Module
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'member_default';

	/**
	 * Return a wildcard in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
		$container = System::getContainer();
		$request = $container->get('request_stack')->getCurrentRequest();

		if ($request && $container->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['personalData'][0] . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id)));

			return $objTemplate->parse();
		}

		$this->editable = StringUtil::deserialize($this->editable);

		// Return if there are no editable fields
		if (empty($this->editable) || !\is_array($this->editable))
		{
			return '';
		}

		$security = $container->get('security.helper');

		// Return if there is no logged-in user
		if (!$security->getUser() instanceof FrontendUser)
		{
			return '';
		}

		// Always require full authentication if the module allows to set a new password
		$reqFullAuth = $this->reqFullAuth || \in_array('password', $this->editable, true);

		if ($reqFullAuth && !$security->isGranted('IS_AUTHENTICATED_FULLY'))
		{
			throw new AccessDeniedException('Full authentication is required to edit the personal data.');
		}

		if ($this->memberTpl)
		{
			$this->strTemplate = $this->memberTpl;
		}

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		System::loadLanguageFile('tl_member');
		$this->loadDataContainer('tl_member');

		// Call onload_callback (e.g. to check permissions)
		if (\is_array($GLOBALS['TL_DCA']['tl_member']['config']['onload_callback'] ?? null))
		{
			foreach ($GLOBALS['TL_DCA']['tl_member']['config']['onload_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					System::importStatic($callback[0])->{$callback[1]}();
				}
				elseif (\is_callable($callback))
				{
					$callback();
				}
			}
		}

		$this->Template->fields = '';

		$arrFields = array();
		$doNotSubmit = false;
		$hasUpload = false;

		// Predefine the group order (other groups will be appended automatically)
		$arrGroups = array
		(
			'personal' => array(),
			'address'  => array(),
			'contact'  => array(),
			'login'    => array(),
			'profile'  => array()
		);

		$blnModified = false;
		$user = FrontendUser::getInstance();
		$objMember = MemberModel::findById($user->id);
		$strTable = $objMember->getTable();
		$strFormId = 'tl_member_' . $this->id;
		$session = System::getContainer()->get('request_stack')->getSession();
		$flashBag = $session->getFlashBag();

		// Initialize the versioning (see #7415)
		$objVersions = new Versions($strTable, $objMember->id);
		$objVersions->setUsername($objMember->username);
		$objVersions->setEditUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'member', 'act'=>'edit', 'id'=>$objMember->id)));
		$objVersions->initialize();

		$arrSubmitted = array();
		$arrFiles = array();
		$migrateSession = false;

		$db = Database::getInstance();

		// Build the form
		foreach ($this->editable as $field)
		{
			$arrData = $GLOBALS['TL_DCA']['tl_member']['fields'][$field] ?? array();

			// Map checkboxWizards to regular checkbox widgets
			if (($arrData['inputType'] ?? null) == 'checkboxWizard')
			{
				$arrData['inputType'] = 'checkbox';
			}

			// Map fileTrees to upload widgets (see #8091)
			if (($arrData['inputType'] ?? null) == 'fileTree')
			{
				$arrData['inputType'] = 'upload';
			}

			/** @var class-string<Widget> $strClass */
			$strClass = $GLOBALS['TL_FFL'][$arrData['inputType'] ?? null] ?? null;

			// Continue if the class does not exist
			if (!($arrData['eval']['feEditable'] ?? null) || !class_exists($strClass))
			{
				continue;
			}

			$strGroup = $arrData['eval']['feGroup'] ?? null;

			$arrData['eval']['required'] = false;

			if ($arrData['eval']['mandatory'] ?? null)
			{
				if (\is_array($user->$field))
				{
					if (empty($user->$field))
					{
						$arrData['eval']['required'] = true;
					}
				}
				// Use strlen() here (see #3277)
				elseif (!\strlen($user->$field))
				{
					$arrData['eval']['required'] = true;
				}
			}

			$varValue = $user->$field;

			// Convert CSV fields (see #4980)
			if (($arrData['eval']['multiple'] ?? null) && isset($arrData['eval']['csv']))
			{
				$varValue = StringUtil::trimsplit($arrData['eval']['csv'], $varValue);
			}

			// Call the load_callback
			if (\is_array($arrData['load_callback'] ?? null))
			{
				foreach ($arrData['load_callback'] as $callback)
				{
					if (\is_array($callback))
					{
						$varValue = System::importStatic($callback[0])->{$callback[1]}($varValue, $user, $this);
					}
					elseif (\is_callable($callback))
					{
						$varValue = $callback($varValue, $user, $this);
					}
				}
			}

			$objWidget = new $strClass($strClass::getAttributesFromDca($arrData, $field, $varValue, $field, $strTable, $this));

			// Append the module ID to prevent duplicate IDs (see #1493)
			$objWidget->id .= '_' . $this->id;
			$objWidget->storeValues = true;
			$objWidget->currentRecord = $objMember->id;

			if ($objWidget instanceof FormPassword && $objMember->password)
			{
				$objWidget->mandatory = false;
			}

			// Validate the form data
			if (Input::post('FORM_SUBMIT') == $strFormId)
			{
				$objWidget->validate();
				$varValue = $objWidget->value;

				$rgxp = $arrData['eval']['rgxp'] ?? null;

				// Convert date formats into timestamps (check the eval setting first -> #3063)
				if ($varValue !== null && $varValue !== '' && \in_array($rgxp, array('date', 'time', 'datim')))
				{
					try
					{
						$objDate = new Date($varValue, Date::getFormatFromRgxp($rgxp));
						$varValue = $objDate->tstamp;
					}
					catch (\OutOfBoundsException $e)
					{
						$objWidget->addError(\sprintf($GLOBALS['TL_LANG']['ERR']['invalidDate'], $varValue));
					}
				}

				// Convert arrays (see #4980)
				if (($arrData['eval']['multiple'] ?? null) && isset($arrData['eval']['csv']) && \is_array($varValue))
				{
					$varValue = implode($arrData['eval']['csv'], $varValue);
				}

				// Make sure that unique fields are unique (check the eval setting first -> #3063)
				if (($arrData['eval']['unique'] ?? null) && (\is_array($varValue) || (string) $varValue !== '') && !$db->isUniqueValue('tl_member', $field, $varValue, $user->id))
				{
					$objWidget->addError(\sprintf($GLOBALS['TL_LANG']['ERR']['unique'], $arrData['label'][0] ?? $field));
				}

				// Trigger the save_callback (see #5247)
				if (\is_array($arrData['save_callback'] ?? null) && $objWidget->submitInput() && !$objWidget->hasErrors())
				{
					foreach ($arrData['save_callback'] as $callback)
					{
						try
						{
							if (\is_array($callback))
							{
								$varValue = System::importStatic($callback[0])->{$callback[1]}($varValue, $user, $this);
							}
							elseif (\is_callable($callback))
							{
								$varValue = $callback($varValue, $user, $this);
							}
						}
						catch (ResponseException $e)
						{
							throw $e;
						}
						catch (\Exception $e)
						{
							$objWidget->class = 'error';
							$objWidget->addError($e->getMessage());
						}
					}
				}

				// Do not submit the field if there are errors
				if ($objWidget->hasErrors())
				{
					$doNotSubmit = true;
				}
				elseif ($objWidget->submitInput())
				{
					// Store the form data
					$arrSubmitted[$field] = $varValue;

					// Set the correct empty value (see #6284, #6373)
					if ($varValue === '')
					{
						$varValue = $objWidget->getEmptyValue();
					}

					// Set the new value
					if ($varValue !== $user->$field)
					{
						$user->$field = $varValue;

						// Set the new field in the member model
						$blnModified = true;
						$objMember->$field = $varValue;

						if (\in_array($field, array('username', 'password')))
						{
							$migrateSession = true;
						}

						if ($field == 'password')
						{
							// Delete unconfirmed "change password" tokens
							$models = OptInModel::findUnconfirmedByRelatedTableAndId('tl_member', $objMember->id);

							foreach ($models ?? array() as $model)
							{
								$model->delete();
							}
						}
					}
				}
			}

			if ($objWidget instanceof UploadableWidgetInterface)
			{
				$arrFiles[$objWidget->name] = $objWidget->value;
				$hasUpload = true;
			}

			$temp = $objWidget->parse();

			$this->Template->fields .= $temp;

			if (!isset($arrFields[$strGroup][$field]))
			{
				$arrFields[$strGroup][$field] = '';
			}

			$arrFields[$strGroup][$field] .= $temp;
		}

		// Save the model
		if ($blnModified && !$doNotSubmit)
		{
			$objMember->tstamp = time();
			$objMember->save();

			// Generate a new session ID
			if ($migrateSession)
			{
				$session->migrate();
			}
		}

		$this->Template->hasError = $doNotSubmit;

		// Redirect or reload if there was no error
		if (!$doNotSubmit && Input::post('FORM_SUBMIT') == $strFormId)
		{
			// HOOK: updated personal data
			if (isset($GLOBALS['TL_HOOKS']['updatePersonalData']) && \is_array($GLOBALS['TL_HOOKS']['updatePersonalData']))
			{
				foreach ($GLOBALS['TL_HOOKS']['updatePersonalData'] as $callback)
				{
					System::importStatic($callback[0])->{$callback[1]}($user, $arrSubmitted, $this, $arrFiles);
				}
			}

			// Call the onsubmit_callback
			if (\is_array($GLOBALS['TL_DCA']['tl_member']['config']['onsubmit_callback'] ?? null))
			{
				foreach ($GLOBALS['TL_DCA']['tl_member']['config']['onsubmit_callback'] as $callback)
				{
					if (\is_array($callback))
					{
						System::importStatic($callback[0])->{$callback[1]}($user, $this);
					}
					elseif (\is_callable($callback))
					{
						$callback($user, $this);
					}
				}
			}

			// Create a new version
			if ($blnModified && ($GLOBALS['TL_DCA'][$strTable]['config']['enableVersioning'] ?? null))
			{
				$objVersions->create();
			}

			// Check whether there is a jumpTo page
			if ($objJumpTo = PageModel::findById($this->objModel->jumpTo))
			{
				$this->jumpToOrReload($objJumpTo->row());
			}

			$flashBag->set('mod_personal_data_confirm', $GLOBALS['TL_LANG']['MSC']['savedData']);
			$this->reload();
		}

		$this->Template->loginDetails = $GLOBALS['TL_LANG']['tl_member']['loginDetails'];
		$this->Template->addressDetails = $GLOBALS['TL_LANG']['tl_member']['addressDetails'];
		$this->Template->contactDetails = $GLOBALS['TL_LANG']['tl_member']['contactDetails'];
		$this->Template->personalDetails = $GLOBALS['TL_LANG']['tl_member']['personalDetails'];

		// Add the groups
		foreach ($arrFields as $k=>$v)
		{
			$key = $k . 'Details';
			$arrGroups[$GLOBALS['TL_LANG']['tl_member'][$key] ?? $key] = $v;
		}

		// Confirmation message
		if ($session->isStarted() && $flashBag->has('mod_personal_data_confirm'))
		{
			$arrMessages = $flashBag->get('mod_personal_data_confirm');
			$this->Template->message = $arrMessages[0];
		}

		$this->Template->categories = array_filter($arrGroups);
		$this->Template->formId = $strFormId;
		$this->Template->slabel = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['saveData']);
		$this->Template->enctype = $hasUpload ? 'multipart/form-data' : 'application/x-www-form-urlencoded';
	}
}
