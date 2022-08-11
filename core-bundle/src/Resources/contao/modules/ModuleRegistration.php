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
use Contao\CoreBundle\OptIn\OptIn;
use Patchwork\Utf8;

/**
 * Front end module "registration".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ModuleRegistration extends Module
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'member_default';

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
			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['registration'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		$this->editable = StringUtil::deserialize($this->editable);

		// Return if there are no editable fields
		if (empty($this->editable) || !\is_array($this->editable))
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
		/** @var PageModel $objPage */
		global $objPage;

		$GLOBALS['TL_LANGUAGE'] = $objPage->language;

		System::loadLanguageFile('tl_member');
		$this->loadDataContainer('tl_member');

		// Call onload_callback (e.g. to check permissions)
		if (\is_array($GLOBALS['TL_DCA']['tl_member']['config']['onload_callback']))
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

		$strFormId = 'tl_registration_' . $this->id;

		// Remove expired registration (#3709)
		if (Input::post('FORM_SUBMIT') == $strFormId && ($email = Input::post('email')) && ($member = MemberModel::findExpiredRegistrationByEmail($email)))
		{
			$member->delete();
		}

		// Activate account
		if (strncmp(Input::get('token'), 'reg-', 4) === 0)
		{
			$this->activateAcount();

			return;
		}

		if ($this->memberTpl)
		{
			$this->Template = new FrontendTemplate($this->memberTpl);
			$this->Template->setData($this->arrData);
		}

		$this->Template->fields = '';

		$objCaptcha = null;
		$doNotSubmit = false;

		// Predefine the group order (other groups will be appended automatically)
		$arrGroups = array
		(
			'personal' => array(),
			'address'  => array(),
			'contact'  => array(),
			'login'    => array(),
			'profile'  => array()
		);

		// Captcha
		if (!$this->disableCaptcha)
		{
			$arrCaptcha = array
			(
				'id' => 'registration',
				'label' => $GLOBALS['TL_LANG']['MSC']['securityQuestion'],
				'type' => 'captcha',
				'mandatory' => true,
				'required' => true
			);

			$strClass = $GLOBALS['TL_FFL']['captcha'];

			// Fallback to default if the class is not defined
			if (!class_exists($strClass))
			{
				$strClass = 'FormCaptcha';
			}

			/** @var FormCaptcha $objCaptcha */
			$objCaptcha = new $strClass($arrCaptcha);

			if (Input::post('FORM_SUBMIT') == $strFormId)
			{
				$objCaptcha->validate();

				if ($objCaptcha->hasErrors())
				{
					$doNotSubmit = true;
				}
			}
		}

		$objMember = null;

		// Check for a follow-up registration (see #7992)
		if ($this->reg_activate && Input::post('email', true) && ($objMember = MemberModel::findUnactivatedByEmail(Input::post('email', true))) !== null)
		{
			$this->resendActivationMail($objMember);

			return;
		}

		$arrUser = array();
		$arrFields = array();
		$hasUpload = false;
		$i = 0;

		// Build the form
		foreach ($this->editable as $field)
		{
			$arrData = $GLOBALS['TL_DCA']['tl_member']['fields'][$field];

			// Map checkboxWizards to regular checkbox widgets
			if ($arrData['inputType'] == 'checkboxWizard')
			{
				$arrData['inputType'] = 'checkbox';
			}

			// Map fileTrees to upload widgets (see #8091)
			if ($arrData['inputType'] == 'fileTree')
			{
				$arrData['inputType'] = 'upload';
			}

			$strClass = $GLOBALS['TL_FFL'][$arrData['inputType']];

			// Continue if the class is not defined
			if (!class_exists($strClass))
			{
				continue;
			}

			$arrData['eval']['required'] = $arrData['eval']['mandatory'];

			// Unset the unique field check upon follow-up registrations
			if ($objMember !== null && $arrData['eval']['unique'] && Input::post($field) == $objMember->$field)
			{
				$arrData['eval']['unique'] = false;
			}

			$objWidget = new $strClass($strClass::getAttributesFromDca($arrData, $field, $arrData['default'], $field, 'tl_member', $this));

			// Append the module ID to prevent duplicate IDs (see #1493)
			$objWidget->id .= '_' . $this->id;
			$objWidget->storeValues = true;
			$objWidget->rowClass = 'row_' . $i . (($i == 0) ? ' row_first' : '') . ((($i % 2) == 0) ? ' even' : ' odd');

			// Increase the row count if its a password field
			if ($objWidget instanceof FormPassword)
			{
				$objWidget->rowClassConfirm = 'row_' . ++$i . ((($i % 2) == 0) ? ' even' : ' odd');
			}

			// Validate input
			if (Input::post('FORM_SUBMIT') == $strFormId)
			{
				$objWidget->validate();

				$varValue = $objWidget->value;
				$encoder = System::getContainer()->get('security.encoder_factory')->getEncoder(FrontendUser::class);

				// Check whether the password matches the username
				if ($objWidget instanceof FormPassword && ($username = Input::post('username')) && $encoder->isPasswordValid($varValue, $username, null))
				{
					$objWidget->addError($GLOBALS['TL_LANG']['ERR']['passwordName']);
				}

				$rgxp = $arrData['eval']['rgxp'];

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
				if ((string) $varValue !== '' && $arrData['eval']['unique'] && !$this->Database->isUniqueValue('tl_member', $field, $varValue))
				{
					$objWidget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['unique'], $arrData['label'][0] ?: $field));
				}

				// Save callback
				if (\is_array($arrData['save_callback']) && $objWidget->submitInput() && !$objWidget->hasErrors())
				{
					foreach ($arrData['save_callback'] as $callback)
					{
						try
						{
							if (\is_array($callback))
							{
								$this->import($callback[0]);
								$varValue = $this->{$callback[0]}->{$callback[1]}($varValue, null);
							}
							elseif (\is_callable($callback))
							{
								$varValue = $callback($varValue, null);
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

				// Store the current value
				if ($objWidget->hasErrors())
				{
					$doNotSubmit = true;
				}
				elseif ($objWidget->submitInput())
				{
					// Set the correct empty value (see #6284, #6373)
					if ($varValue === '')
					{
						$varValue = $objWidget->getEmptyValue();
					}

					// Encrypt the value (see #7815)
					if ($arrData['eval']['encrypt'])
					{
						$varValue = Encryption::encrypt($varValue);
					}

					// Set the new value
					$arrUser[$field] = $varValue;
				}
			}

			if ($objWidget instanceof \uploadable)
			{
				$hasUpload = true;
			}

			$temp = $objWidget->parse();

			$this->Template->fields .= $temp;
			$arrFields[$arrData['eval']['feGroup']][$field] .= $temp;

			++$i;
		}

		// Captcha
		if (!$this->disableCaptcha)
		{
			$objCaptcha->rowClass = 'row_' . $i . (($i == 0) ? ' row_first' : '') . ((($i % 2) == 0) ? ' even' : ' odd');
			$strCaptcha = $objCaptcha->parse();

			$this->Template->fields .= $strCaptcha;
			$arrFields['captcha']['captcha'] .= $strCaptcha;
		}

		$this->Template->rowLast = 'row_' . ++$i . ((($i % 2) == 0) ? ' even' : ' odd');
		$this->Template->enctype = $hasUpload ? 'multipart/form-data' : 'application/x-www-form-urlencoded';
		$this->Template->hasError = $doNotSubmit;

		// Create new user if there are no errors
		if (!$doNotSubmit && Input::post('FORM_SUBMIT') == $strFormId)
		{
			$this->createNewUser($arrUser);
		}

		$this->Template->loginDetails = $GLOBALS['TL_LANG']['tl_member']['loginDetails'];
		$this->Template->addressDetails = $GLOBALS['TL_LANG']['tl_member']['addressDetails'];
		$this->Template->contactDetails = $GLOBALS['TL_LANG']['tl_member']['contactDetails'];
		$this->Template->personalData = $GLOBALS['TL_LANG']['tl_member']['personalData'];
		$this->Template->captchaDetails = $GLOBALS['TL_LANG']['MSC']['securityQuestion'];

		// Add the groups
		foreach ($arrFields as $k=>$v)
		{
			// Deprecated since Contao 4.0, to be removed in Contao 5.0
			$this->Template->$k = $v;

			$key = $k . (($k == 'personal') ? 'Data' : 'Details');
			$arrGroups[$GLOBALS['TL_LANG']['tl_member'][$key]] = $v;
		}

		$this->Template->categories = array_filter($arrGroups);
		$this->Template->formId = $strFormId;
		$this->Template->slabel = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['register']);

		// Deprecated since Contao 4.0, to be removed in Contao 5.0
		$this->Template->captcha = $arrFields['captcha']['captcha'];
	}

	/**
	 * Create a new user and redirect
	 *
	 * @param array $arrData
	 */
	protected function createNewUser($arrData)
	{
		$arrData['tstamp'] = time();
		$arrData['login'] = $this->reg_allowLogin;
		$arrData['dateAdded'] = $arrData['tstamp'];

		// Set default groups
		if (!\array_key_exists('groups', $arrData))
		{
			$arrData['groups'] = $this->reg_groups;
		}

		// Disable account
		$arrData['disable'] = 1;

		// Make sure newsletter is an array
		if (isset($arrData['newsletter']) && !\is_array($arrData['newsletter']))
		{
			$arrData['newsletter'] = array($arrData['newsletter']);
		}

		// Create the user
		$objNewUser = new MemberModel();
		$objNewUser->setRow($arrData);
		$objNewUser->save();

		// Store the new ID (see https://github.com/contao/contao/pull/196#discussion_r243555399)
		$arrData['id'] = $objNewUser->id;

		// Send activation e-mail
		if ($this->reg_activate)
		{
			$this->sendActivationMail($arrData);
		}

		// Assign home directory
		if ($this->reg_assignDir)
		{
			$objHomeDir = FilesModel::findByUuid($this->reg_homeDir);

			if ($objHomeDir !== null)
			{
				$this->import(Files::class, 'Files');
				$strUserDir = StringUtil::standardize($arrData['username']) ?: 'user_' . $objNewUser->id;

				// Add the user ID if the directory exists
				while (is_dir(System::getContainer()->getParameter('kernel.project_dir') . '/' . $objHomeDir->path . '/' . $strUserDir))
				{
					$strUserDir .= '_' . $objNewUser->id;
				}

				// Create the user folder
				new Folder($objHomeDir->path . '/' . $strUserDir);

				$objUserDir = FilesModel::findByPath($objHomeDir->path . '/' . $strUserDir);

				// Save the folder ID
				$objNewUser->assignDir = 1;
				$objNewUser->homeDir = $objUserDir->uuid;
				$objNewUser->save();
			}
		}

		// HOOK: send insert ID and user data
		if (isset($GLOBALS['TL_HOOKS']['createNewUser']) && \is_array($GLOBALS['TL_HOOKS']['createNewUser']))
		{
			foreach ($GLOBALS['TL_HOOKS']['createNewUser'] as $callback)
			{
				$this->import($callback[0]);
				$this->{$callback[0]}->{$callback[1]}($objNewUser->id, $arrData, $this);
			}
		}

		// Create the initial version (see #7816)
		$objVersions = new Versions('tl_member', $objNewUser->id);
		$objVersions->setUsername($objNewUser->username);
		$objVersions->setUserId(0);
		$objVersions->setEditUrl('contao/main.php?do=member&act=edit&id=%s&rt=1');
		$objVersions->initialize();

		// Inform admin if no activation link is sent
		if (!$this->reg_activate)
		{
			$this->sendAdminNotification($objNewUser->id, $arrData);
		}

		// Check whether there is a jumpTo page
		if (($objJumpTo = $this->objModel->getRelated('jumpTo')) instanceof PageModel)
		{
			$this->jumpToOrReload($objJumpTo->row());
		}

		$this->reload();
	}

	/**
	 * Send the activation mail
	 *
	 * @param array $arrData
	 */
	protected function sendActivationMail($arrData)
	{
		/** @var OptIn $optIn */
		$optIn = System::getContainer()->get('contao.opt-in');
		$optInToken = $optIn->create('reg', $arrData['email'], array('tl_member'=>array($arrData['id'])));

		// Prepare the simple token data
		$arrTokenData = $arrData;
		$arrTokenData['activation'] = $optInToken->getIdentifier();
		$arrTokenData['domain'] = Idna::decode(Environment::get('host'));
		$arrTokenData['link'] = Idna::decode(Environment::get('base')) . Environment::get('request') . ((strpos(Environment::get('request'), '?') !== false) ? '&' : '?') . 'token=' . $optInToken->getIdentifier();
		$arrTokenData['channels'] = '';

		$bundles = System::getContainer()->getParameter('kernel.bundles');

		if (isset($bundles['ContaoNewsletterBundle']))
		{
			// Make sure newsletter is an array
			if (!\is_array($arrData['newsletter']))
			{
				if ($arrData['newsletter'])
				{
					$arrData['newsletter'] = array($arrData['newsletter']);
				}
				else
				{
					$arrData['newsletter'] = array();
				}
			}

			// Replace the wildcard
			if (!empty($arrData['newsletter']))
			{
				$objChannels = NewsletterChannelModel::findByIds($arrData['newsletter']);

				if ($objChannels !== null)
				{
					$arrTokenData['channels'] = implode("\n", $objChannels->fetchEach('title'));
				}
			}
		}

		// Deprecated since Contao 4.0, to be removed in Contao 5.0
		$arrTokenData['channel'] = $arrTokenData['channels'];

		// Send the token
		$optInToken->send(sprintf($GLOBALS['TL_LANG']['MSC']['emailSubject'], Idna::decode(Environment::get('host'))), StringUtil::parseSimpleTokens($this->reg_text, $arrTokenData));
	}

	/**
	 * Activate an account
	 */
	protected function activateAcount()
	{
		$this->strTemplate = 'mod_message';
		$this->Template = new FrontendTemplate($this->strTemplate);

		/** @var OptIn $optIn */
		$optIn = System::getContainer()->get('contao.opt-in');

		// Find an unconfirmed token with only one related record
		if ((!$optInToken = $optIn->find(Input::get('token'))) || !$optInToken->isValid() || \count($arrRelated = $optInToken->getRelatedRecords()) != 1 || key($arrRelated) != 'tl_member' || \count($arrIds = current($arrRelated)) != 1 || (!$objMember = MemberModel::findByPk($arrIds[0])))
		{
			$this->Template->type = 'error';
			$this->Template->message = $GLOBALS['TL_LANG']['MSC']['invalidToken'];

			return;
		}

		if ($optInToken->isConfirmed())
		{
			$this->Template->type = 'error';
			$this->Template->message = $GLOBALS['TL_LANG']['MSC']['tokenConfirmed'];

			return;
		}

		if ($optInToken->getEmail() != $objMember->email)
		{
			$this->Template->type = 'error';
			$this->Template->message = $GLOBALS['TL_LANG']['MSC']['tokenEmailMismatch'];

			return;
		}

		$objMember->disable = '';
		$objMember->save();

		$optInToken->confirm();

		// HOOK: post activation callback
		if (isset($GLOBALS['TL_HOOKS']['activateAccount']) && \is_array($GLOBALS['TL_HOOKS']['activateAccount']))
		{
			foreach ($GLOBALS['TL_HOOKS']['activateAccount'] as $callback)
			{
				$this->import($callback[0]);
				$this->{$callback[0]}->{$callback[1]}($objMember, $this);
			}
		}

		// Log activity
		$this->log('User account ID ' . $objMember->id . ' (' . Idna::decodeEmail($objMember->email) . ') has been activated', __METHOD__, TL_ACCESS);

		// Redirect to the jumpTo page
		if (($objTarget = $this->objModel->getRelated('reg_jumpTo')) instanceof PageModel)
		{
			/** @var PageModel $objTarget */
			$this->redirect($objTarget->getFrontendUrl());
		}

		// Confirm activation
		$this->Template->type = 'confirm';
		$this->Template->message = $GLOBALS['TL_LANG']['MSC']['accountActivated'];
	}

	/**
	 * Re-send the activation mail
	 *
	 * @param MemberModel $objMember
	 */
	protected function resendActivationMail(MemberModel $objMember)
	{
		if (!$objMember->disable)
		{
			return;
		}

		$this->strTemplate = 'mod_message';
		$this->Template = new FrontendTemplate($this->strTemplate);

		/** @var OptIn $optIn */
		$optIn = System::getContainer()->get('contao.opt-in');
		$optInToken = null;
		$models = OptInModel::findByRelatedTableAndIds('tl_member', array($objMember->id));

		foreach ($models as $model)
		{
			// Look for a valid, unconfirmed token
			if (($token = $optIn->find($model->token)) && $token->isValid() && !$token->isConfirmed())
			{
				$optInToken = $token;
				break;
			}
		}

		if ($optInToken === null)
		{
			return;
		}

		$optInToken->send();

		// Confirm activation
		$this->Template->type = 'confirm';
		$this->Template->message = $GLOBALS['TL_LANG']['MSC']['resendActivation'];
	}

	/**
	 * Send an admin notification e-mail
	 *
	 * @param integer $intId
	 * @param array   $arrData
	 */
	protected function sendAdminNotification($intId, $arrData)
	{
		$objEmail = new Email();
		$objEmail->from = $GLOBALS['TL_ADMIN_EMAIL'];
		$objEmail->fromName = $GLOBALS['TL_ADMIN_NAME'];
		$objEmail->subject = sprintf($GLOBALS['TL_LANG']['MSC']['adminSubject'], Idna::decode(Environment::get('host')));

		$strData = "\n\n";

		// Add user details
		foreach ($arrData as $k=>$v)
		{
			if ($k == 'id' || $k == 'password' || $k == 'tstamp' || $k == 'dateAdded')
			{
				continue;
			}

			$v = StringUtil::deserialize($v);

			if ($k == 'dateOfBirth' && \strlen($v))
			{
				$v = Date::parse(Config::get('dateFormat'), $v);
			}

			$strData .= ($GLOBALS['TL_LANG']['tl_member'][$k][0] ?? $k) . ': ' . (\is_array($v) ? implode(', ', $v) : $v) . "\n";
		}

		$objEmail->text = sprintf($GLOBALS['TL_LANG']['MSC']['adminText'], $intId, $strData . "\n") . "\n";
		$objEmail->sendTo($GLOBALS['TL_ADMIN_EMAIL']);

		$this->log('A new user (ID ' . $intId . ') has registered on the website', __METHOD__, TL_ACCESS);
	}
}

class_alias(ModuleRegistration::class, 'ModuleRegistration');
