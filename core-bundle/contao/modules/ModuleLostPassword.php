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
 * Front end module "lost password".
 */
class ModuleLostPassword extends Module
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_lostPassword';

	/**
	 * Display a wildcard in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['lostPassword'][0] . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id)));

			return $objTemplate->parse();
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
					$this->import($callback[0]);
					$this->{$callback[0]}->{$callback[1]}();
				}
				elseif (\is_callable($callback))
				{
					$callback();
				}
			}
		}

		// Set new password
		if (strncmp(Input::get('token'), 'pw-', 3) === 0)
		{
			$this->setNewPassword();

			return;
		}

		// Username widget
		if (!$this->reg_skipName)
		{
			$arrFields['username'] = $GLOBALS['TL_DCA']['tl_member']['fields']['username'];
			$arrFields['username']['name'] = 'username';
		}

		// E-mail widget
		$arrFields['email'] = $GLOBALS['TL_DCA']['tl_member']['fields']['email'];
		$arrFields['email']['name'] = 'email';

		// Captcha widget
		if (!$this->disableCaptcha)
		{
			$arrFields['captcha'] = array
			(
				'name' => 'lost_password',
				'label' => $GLOBALS['TL_LANG']['MSC']['securityQuestion'],
				'inputType' => 'captcha',
				'eval' => array('mandatory'=>true)
			);
		}

		$strFields = '';
		$doNotSubmit = false;
		$strFormId = 'tl_lost_password_' . $this->id;

		// Initialize the widgets
		foreach ($arrFields as $arrField)
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

			// Validate the widget
			if (Input::post('FORM_SUBMIT') == $strFormId)
			{
				$objWidget->validate();

				if ($objWidget->hasErrors())
				{
					$doNotSubmit = true;
				}
			}

			$strFields .= $objWidget->parse();
		}

		$this->Template->fields = $strFields;
		$this->Template->hasError = $doNotSubmit;

		// Look for an account and send the password link
		if (!$doNotSubmit && Input::post('FORM_SUBMIT') == $strFormId)
		{
			if ($this->reg_skipName)
			{
				$objMember = MemberModel::findActiveByEmailAndUsername(Input::post('email', true));
			}
			else
			{
				$objMember = MemberModel::findActiveByEmailAndUsername(Input::post('email', true), Input::post('username'));
			}

			if ($objMember === null)
			{
				$this->Template->error = $GLOBALS['TL_LANG']['MSC']['accountNotFound'];
			}
			else
			{
				$this->sendPasswordLink($objMember);
			}
		}

		$this->Template->formId = $strFormId;
		$this->Template->username = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['username']);
		$this->Template->email = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['emailAddress']);
		$this->Template->slabel = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['requestPassword']);
	}

	/**
	 * Set the new password
	 */
	protected function setNewPassword()
	{
		$optIn = System::getContainer()->get('contao.opt_in');

		// Find an unconfirmed token with only one related record
		if ((!$optInToken = $optIn->find(Input::get('token'))) || !$optInToken->isValid() || \count($arrRelated = $optInToken->getRelatedRecords()) != 1 || key($arrRelated) != 'tl_member' || \count($arrIds = current($arrRelated)) != 1 || (!$objMember = MemberModel::findByPk($arrIds[0])))
		{
			$this->strTemplate = 'mod_message';

			$this->Template = new FrontendTemplate($this->strTemplate);
			$this->Template->type = 'error';
			$this->Template->message = $GLOBALS['TL_LANG']['MSC']['invalidToken'];

			return;
		}

		if ($optInToken->isConfirmed())
		{
			$this->strTemplate = 'mod_message';

			$this->Template = new FrontendTemplate($this->strTemplate);
			$this->Template->type = 'error';
			$this->Template->message = $GLOBALS['TL_LANG']['MSC']['tokenConfirmed'];

			return;
		}

		if ($optInToken->getEmail() != $objMember->email)
		{
			$this->strTemplate = 'mod_message';

			$this->Template = new FrontendTemplate($this->strTemplate);
			$this->Template->type = 'error';
			$this->Template->message = $GLOBALS['TL_LANG']['MSC']['tokenEmailMismatch'];

			return;
		}

		// Initialize the versioning (see #8301)
		$objVersions = new Versions('tl_member', $objMember->id);
		$objVersions->setUsername($objMember->username);
		$objVersions->setUserId(0);
		$objVersions->setEditUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'member', 'act'=>'edit', 'id'=>$objMember->id, 'rt'=>'1')));
		$objVersions->initialize();

		// Define the form field
		$arrField = $GLOBALS['TL_DCA']['tl_member']['fields']['password'];
		$strClass = $GLOBALS['TL_FFL']['password'] ?? null;

		// Fallback to default if the class is not defined
		if (!class_exists($strClass))
		{
			$strClass = 'FormPassword';
		}

		/** @var Widget $objWidget */
		$objWidget = new $strClass($strClass::getAttributesFromDca($arrField, 'password'));
		$objSession = System::getContainer()->get('request_stack')->getSession();

		// Validate the field
		if (Input::post('FORM_SUBMIT') && Input::post('FORM_SUBMIT') == $objSession->get('setPasswordToken'))
		{
			$objWidget->validate();

			// Set the new password and redirect
			if (!$objWidget->hasErrors())
			{
				$objSession->set('setPasswordToken', '');

				$objMember->tstamp = time();
				$objMember->password = $objWidget->value;
				$objMember->save();

				$optInToken->confirm();

				// Create a new version
				if ($GLOBALS['TL_DCA']['tl_member']['config']['enableVersioning'] ?? null)
				{
					$objVersions->create();
				}

				// HOOK: set new password callback
				if (isset($GLOBALS['TL_HOOKS']['setNewPassword']) && \is_array($GLOBALS['TL_HOOKS']['setNewPassword']))
				{
					foreach ($GLOBALS['TL_HOOKS']['setNewPassword'] as $callback)
					{
						$this->import($callback[0]);
						$this->{$callback[0]}->{$callback[1]}($objMember, $objWidget->value, $this);
					}
				}

				// Redirect to the jumpTo page
				if (($objTarget = $this->objModel->getRelated('reg_jumpTo')) instanceof PageModel)
				{
					/** @var PageModel $objTarget */
					$this->redirect($objTarget->getFrontendUrl());
				}

				// Confirm
				$this->strTemplate = 'mod_message';

				$this->Template = new FrontendTemplate($this->strTemplate);
				$this->Template->type = 'confirm';
				$this->Template->message = $GLOBALS['TL_LANG']['MSC']['newPasswordSet'];

				return;
			}
		}

		$strToken = md5(uniqid(mt_rand(), true));
		$objSession->set('setPasswordToken', $strToken);

		$this->Template->formId = $strToken;
		$this->Template->fields = $objWidget->parse();
		$this->Template->slabel = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['setNewPassword']);
	}

	/**
	 * Create a new user and redirect
	 *
	 * @param MemberModel $objMember
	 */
	protected function sendPasswordLink($objMember)
	{
		$optIn = System::getContainer()->get('contao.opt_in');
		$optInToken = $optIn->create('pw', $objMember->email, array('tl_member'=>array($objMember->id)));

		// Prepare the simple token data
		$arrData = $objMember->row();
		$arrData['activation'] = $optInToken->getIdentifier();
		$arrData['domain'] = Idna::decode(Environment::get('host'));
		$arrData['link'] = Idna::decode(Environment::get('url')) . Environment::get('requestUri') . ((strpos(Environment::get('requestUri'), '?') !== false) ? '&' : '?') . 'token=' . $optInToken->getIdentifier();

		// Send the token
		$optInToken->send(
			sprintf($GLOBALS['TL_LANG']['MSC']['passwordSubject'], Idna::decode(Environment::get('host'))),
			System::getContainer()->get('contao.string.simple_token_parser')->parse($this->reg_password, $arrData)
		);

		System::getContainer()->get('monolog.logger.contao.access')->info('A new password has been requested for user ID ' . $objMember->id . ' (' . Idna::decodeEmail($objMember->email) . ')');

		// Check whether there is a jumpTo page
		if (($objJumpTo = $this->objModel->getRelated('jumpTo')) instanceof PageModel)
		{
			$this->jumpToOrReload($objJumpTo->row());
		}

		$this->reload();
	}
}
