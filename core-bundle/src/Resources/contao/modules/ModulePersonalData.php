<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

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

		// Return if there are no editable fields or if there is no logged-in user
		if (empty($this->editable) || !\is_array($this->editable) || !$container->get('contao.security.token_checker')->hasFrontendUser())
		{
			return '';
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
		$this->import(FrontendUser::class, 'User');

		System::loadLanguageFile('tl_member');
		$this->loadDataContainer('tl_member');

		// Call onload_callback (e.g. to check permissions)
		if (\is_array($GLOBALS['TL_DCA']['tl_member']['config']['onload_callback'] ?? null))
		{
			foreach ($GLOBALS['TL_DCA']['tl_member']['config']['onload_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					$this->import($callback[0]);
					$this->{$callback[0]}->{$callback[1]}();
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
		$row = 0;

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
		$objMember = MemberModel::findByPk($this->User->id);
		$strTable = $objMember->getTable();
		$strFormId = 'tl_member_' . $this->id;
		$session = System::getContainer()->get('session');
		$flashBag = $session->getFlashBag();

		// Initialize the versioning (see #7415)
		$objVersions = new Versions($strTable, $objMember->id);
		$objVersions->setUsername($objMember->username);
		$objVersions->setUserId(0);
		$objVersions->setEditUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'member', 'act'=>'edit', 'id'=>'%s', 'rt'=>'1')));
		$objVersions->initialize();

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

			$strClass = $GLOBALS['TL_FFL'][$arrData['inputType']] ?? null;

			// Continue if the class does not exist
			if (!($arrData['eval']['feEditable'] ?? null) || !class_exists($strClass))
			{
				continue;
			}

			$strGroup = $arrData['eval']['feGroup'] ?? null;

			$arrData['eval']['required'] = false;

			if ($arrData['eval']['mandatory'] ?? null)
			{
				if (\is_array($this->User->$field))
				{
					if (empty($this->User->$field))
					{
						$arrData['eval']['required'] = true;
					}
				}
				// Use strlen() here (see #3277)
				elseif (!\strlen($this->User->$field))
				{
					$arrData['eval']['required'] = true;
				}
			}

			$varValue = $this->User->$field;

			// Call the load_callback
			if (\is_array($arrData['load_callback'] ?? null))
			{
				foreach ($arrData['load_callback'] as $callback)
				{
					if (\is_array($callback))
					{
						$this->import($callback[0]);
						$varValue = $this->{$callback[0]}->{$callback[1]}($varValue, $this->User, $this);
					}
					elseif (\is_callable($callback))
					{
						$varValue = $callback($varValue, $this->User, $this);
					}
				}
			}

			/** @var Widget $objWidget */
			$objWidget = new $strClass($strClass::getAttributesFromDca($arrData, $field, $varValue, $field, $strTable, $this));

			// Append the module ID to prevent duplicate IDs (see #1493)
			$objWidget->id .= '_' . $this->id;
			$objWidget->storeValues = true;
			$objWidget->rowClass = 'row_' . $row . (($row == 0) ? ' row_first' : '') . ((($row % 2) == 0) ? ' even' : ' odd');

			// Increase the row count if it is a password field
			if ($objWidget instanceof FormPassword)
			{
				if ($objMember->password)
				{
					$objWidget->mandatory = false;
				}

				$objWidget->rowClassConfirm = 'row_' . ++$row . ((($row % 2) == 0) ? ' even' : ' odd');
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
						$objWidget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['invalidDate'], $varValue));
					}
				}

				// Make sure that unique fields are unique (check the eval setting first -> #3063)
				if (($arrData['eval']['unique'] ?? null) && (\is_array($varValue) || (string) $varValue !== '') && !$this->Database->isUniqueValue('tl_member', $field, $varValue, $this->User->id))
				{
					$objWidget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['unique'], $arrData['label'][0] ?: $field));
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
								$this->import($callback[0]);
								$varValue = $this->{$callback[0]}->{$callback[1]}($varValue, $this->User, $this);
							}
							elseif (\is_callable($callback))
							{
								$varValue = $callback($varValue, $this->User, $this);
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
					$_SESSION['FORM_DATA'][$field] = $varValue;

					// Set the correct empty value (see #6284, #6373)
					if ($varValue === '')
					{
						$varValue = $objWidget->getEmptyValue();
					}

					// Encrypt the value (see #7815)
					if ($arrData['eval']['encrypt'] ?? null)
					{
						$varValue = Encryption::encrypt($varValue);
					}

					// Set the new value
					if ($varValue !== $this->User->$field)
					{
						$this->User->$field = $varValue;

						// Set the new field in the member model
						$blnModified = true;
						$objMember->$field = $varValue;
					}
				}
			}

			if ($objWidget instanceof UploadableWidgetInterface)
			{
				$hasUpload = true;
			}

			$temp = $objWidget->parse();

			$this->Template->fields .= $temp;

			if (!isset($arrFields[$strGroup][$field]))
			{
				$arrFields[$strGroup][$field] = '';
			}

			$arrFields[$strGroup][$field] .= $temp;

			++$row;
		}

		// Save the model
		if ($blnModified && !$doNotSubmit)
		{
			$objMember->tstamp = time();
			$objMember->save();
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
					$this->import($callback[0]);
					$this->{$callback[0]}->{$callback[1]}($this->User, $_SESSION['FORM_DATA'], $this);
				}
			}

			// Call the onsubmit_callback
			if (\is_array($GLOBALS['TL_DCA']['tl_member']['config']['onsubmit_callback'] ?? null))
			{
				foreach ($GLOBALS['TL_DCA']['tl_member']['config']['onsubmit_callback'] as $callback)
				{
					if (\is_array($callback))
					{
						$this->import($callback[0]);
						$this->{$callback[0]}->{$callback[1]}($this->User, $this);
					}
					elseif (\is_callable($callback))
					{
						$callback($this->User, $this);
					}
				}
			}

			// Create a new version
			if ($blnModified && ($GLOBALS['TL_DCA'][$strTable]['config']['enableVersioning'] ?? null))
			{
				$objVersions->create();
			}

			// Check whether there is a jumpTo page
			if (($objJumpTo = $this->objModel->getRelated('jumpTo')) instanceof PageModel)
			{
				$this->jumpToOrReload($objJumpTo->row());
			}

			$flashBag->set('mod_personal_data_confirm', $GLOBALS['TL_LANG']['MSC']['savedData']);
			$this->reload();
		}

		$this->Template->loginDetails = $GLOBALS['TL_LANG']['tl_member']['loginDetails'];
		$this->Template->addressDetails = $GLOBALS['TL_LANG']['tl_member']['addressDetails'];
		$this->Template->contactDetails = $GLOBALS['TL_LANG']['tl_member']['contactDetails'];
		$this->Template->personalData = $GLOBALS['TL_LANG']['tl_member']['personalData'];

		// Add the groups
		foreach ($arrFields as $k=>$v)
		{
			// Deprecated since Contao 4.0, to be removed in Contao 5.0
			$this->Template->$k = $v;

			$key = $k . (($k == 'personal') ? 'Data' : 'Details');
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
		$this->Template->rowLast = 'row_' . $row . ((($row % 2) == 0) ? ' even' : ' odd');
		$this->Template->requestToken = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
	}
}

class_alias(ModulePersonalData::class, 'ModulePersonalData');
