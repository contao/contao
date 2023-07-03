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
 * Front end module "change password".
 */
class ModuleChangePassword extends Module
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_changePassword';

	/**
	 * Display a wildcard in the back end
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
			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['changePassword'][0] . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id)));

			return $objTemplate->parse();
		}

		// Return if there is no logged-in user
		if (!$container->get('contao.security.token_checker')->hasFrontendUser())
		{
			return '';
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
					System::importStatic($callback[0])->{$callback[1]}();
				}
				elseif (\is_callable($callback))
				{
					$callback();
				}
			}
		}

		// Old password widget
		$arrFields['oldPassword'] = array
		(
			'name'      => 'oldpassword',
			'label'     => &$GLOBALS['TL_LANG']['MSC']['oldPassword'],
			'inputType' => 'text',
			'eval'      => array('mandatory'=>true, 'preserveTags'=>true, 'hideInput'=>true, 'autocomplete'=>'current-password'),
		);

		// New password widget
		$arrFields['newPassword'] = $GLOBALS['TL_DCA']['tl_member']['fields']['password'];
		$arrFields['newPassword']['name'] = 'password';
		$arrFields['newPassword']['label'] = &$GLOBALS['TL_LANG']['MSC']['newPassword'];

		$strFields = '';
		$doNotSubmit = false;
		$objMember = MemberModel::findByPk($this->User->id);
		$strFormId = 'tl_change_password_' . $this->id;
		$strTable = $objMember->getTable();
		$session = System::getContainer()->get('request_stack')->getSession();
		$flashBag = $session->getFlashBag();

		// Initialize the versioning (see #8301)
		$objVersions = new Versions($strTable, $objMember->id);
		$objVersions->setUsername($objMember->username);
		$objVersions->setEditUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'member', 'act'=>'edit', 'id'=>$objMember->id)));
		$objVersions->initialize();

		/** @var FormPassword $objNewPassword */
		$objNewPassword = null;

		// Initialize the widgets
		foreach ($arrFields as $strKey=>$arrField)
		{
			$strClass = $GLOBALS['TL_FFL'][$arrField['inputType']] ?? null;

			// Continue if the class is not defined
			if (!class_exists($strClass))
			{
				continue;
			}

			$arrField['eval']['required'] = $arrField['eval']['mandatory'] ?? null;

			/** @var Widget $objWidget */
			$objWidget = new $strClass($strClass::getAttributesFromDca($arrField, $arrField['name']));
			$objWidget->storeValues = true;
			$objWidget->currentRecord = $objMember->id;

			// Store the widget objects
			$strVar  = 'obj' . ucfirst($strKey);
			$$strVar = $objWidget;

			// Validate the widget
			if (Input::post('FORM_SUBMIT') == $strFormId)
			{
				$objWidget->validate();

				// Validate the old password
				if ($strKey == 'oldPassword')
				{
					$passwordHasher = System::getContainer()->get('security.password_hasher_factory')->getPasswordHasher(FrontendUser::class);

					if (!$passwordHasher->verify($objMember->password, $objWidget->value))
					{
						$objWidget->value = '';
						$objWidget->addError($GLOBALS['TL_LANG']['MSC']['oldPasswordWrong']);
					}
				}

				if ($objWidget->hasErrors())
				{
					$doNotSubmit = true;
				}
			}

			$strFields .= $objWidget->parse();
		}

		$this->Template->fields = $strFields;
		$this->Template->hasError = $doNotSubmit;

		// Store the new password
		if (!$doNotSubmit && Input::post('FORM_SUBMIT') == $strFormId)
		{
			$objMember->tstamp = time();
			$objMember->password = $objNewPassword->value;
			$objMember->save();

			// Create a new version
			if ($GLOBALS['TL_DCA'][$strTable]['config']['enableVersioning'] ?? null)
			{
				$objVersions->create();
			}

			// HOOK: set new password callback
			if (isset($GLOBALS['TL_HOOKS']['setNewPassword']) && \is_array($GLOBALS['TL_HOOKS']['setNewPassword']))
			{
				foreach ($GLOBALS['TL_HOOKS']['setNewPassword'] as $callback)
				{
					System::importStatic($callback[0])->{$callback[1]}($objMember, $objNewPassword->value, $this);
				}
			}

			// Update the current user, so they are not logged out automatically
			$this->User->findBy('id', $objMember->id);

			// Check whether there is a jumpTo page
			if (($objJumpTo = $this->objModel->getRelated('jumpTo')) instanceof PageModel)
			{
				$this->jumpToOrReload($objJumpTo->row());
			}

			$flashBag->set('mod_changePassword_confirm', $GLOBALS['TL_LANG']['MSC']['newPasswordSet']);
			$this->reload();
		}

		// Confirmation message
		if ($session->isStarted() && $flashBag->has('mod_changePassword_confirm'))
		{
			$arrMessages = $flashBag->get('mod_changePassword_confirm');
			$this->Template->message = $arrMessages[0];
		}

		$this->Template->formId = $strFormId;
		$this->Template->slabel = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['changePassword']);
	}
}
