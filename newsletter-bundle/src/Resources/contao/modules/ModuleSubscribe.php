<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Patchwork\Utf8;


/**
 * Front end module "newsletter subscribe".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ModuleSubscribe extends \Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'nl_default';


	/**
	 * Display a wildcard in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			/** @var \BackendTemplate|object $objTemplate */
			$objTemplate = new \BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['subscribe'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		$this->nl_channels = deserialize($this->nl_channels);

		// Return if there are no channels
		if (!is_array($this->nl_channels) || empty($this->nl_channels))
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
		// Overwrite default template
		if ($this->nl_template)
		{
			/** @var \FrontendTemplate|object $objTemplate */
			$objTemplate = new \FrontendTemplate($this->nl_template);

			$this->Template = $objTemplate;
			$this->Template->setData($this->arrData);
		}

		$this->Template->email = '';
		$this->Template->captcha = '';

		// Activate e-mail address
		if (\Input::get('token'))
		{
			$this->activateRecipient();

			return;
		}

		$objWidget = null;

		// Set up the captcha widget
		if (!$this->disableCaptcha)
		{
			$arrField = array
			(
				'name' => 'subscribe',
				'label' => $GLOBALS['TL_LANG']['MSC']['securityQuestion'],
				'inputType' => 'captcha',
				'eval' => array('mandatory'=>true)
			);

			/** @var \Widget $objWidget */
			$objWidget = new \FormCaptcha(\FormCaptcha::getAttributesFromDca($arrField, $arrField['name']));
		}

		$strFormId = 'tl_subscribe_' . $this->id;

		// Validate the form
		if (\Input::post('FORM_SUBMIT') == $strFormId)
		{
			$varSubmitted = $this->validateForm($objWidget);

			if ($varSubmitted !== false)
			{
				call_user_func_array(array($this, 'addRecipient'), $varSubmitted);
			}
		}

		// Add the captcha widget to the template
		if ($objWidget !== null)
		{
			$this->Template->captcha = $objWidget->parse();
		}

		$flashBag = \System::getContainer()->get('session')->getFlashBag();

		// Confirmation message
		if ($flashBag->has('nl_confirm'))
		{
			$arrMessages = $flashBag->get('nl_confirm');

			$this->Template->mclass = 'confirm';
			$this->Template->message = $arrMessages[0];
		}

		$arrChannels = array();
		$objChannel = \NewsletterChannelModel::findByIds($this->nl_channels);

		// Get the titles
		if ($objChannel !== null)
		{
			while ($objChannel->next())
			{
				$arrChannels[$objChannel->id] = $objChannel->title;
			}
		}

		// Default template variables
		$this->Template->channels = $arrChannels;
		$this->Template->showChannels = !$this->nl_hideChannels;
		$this->Template->submit = specialchars($GLOBALS['TL_LANG']['MSC']['subscribe']);
		$this->Template->channelsLabel = $GLOBALS['TL_LANG']['MSC']['nl_channels'];
		$this->Template->emailLabel = $GLOBALS['TL_LANG']['MSC']['emailAddress'];
		$this->Template->action = \Environment::get('indexFreeRequest');
		$this->Template->formId = $strFormId;
		$this->Template->id = $this->id;
	}


	/**
	 * Activate a recipient
	 */
	protected function activateRecipient()
	{
		/** @var \FrontendTemplate|object $objTemplate */
		$objTemplate = new \FrontendTemplate('mod_newsletter');

		$this->Template = $objTemplate;

		// Check the token
		$objRecipient = \NewsletterRecipientsModel::findByToken(\Input::get('token'));

		if ($objRecipient === null)
		{
			$this->Template->mclass = 'error';
			$this->Template->message = $GLOBALS['TL_LANG']['ERR']['invalidToken'];

			return;
		}

		$time = time();
		$arrAdd = array();
		$arrChannels = array();
		$arrCids = array();

		// Update the subscriptions
		while ($objRecipient->next())
		{
			/** @var \NewsletterChannelModel $objChannel */
			$objChannel = $objRecipient->getRelated('pid');

			$arrAdd[] = $objRecipient->id;
			$arrChannels[] = $objChannel->title;
			$arrCids[] = $objChannel->id;

			$objRecipient->active = 1;
			$objRecipient->token = '';
			$objRecipient->pid = $objChannel->id;
			$objRecipient->confirmed = $time;
			$objRecipient->save();
		}

		// Log activity
		$this->log($objRecipient->email . ' has subscribed to the following channels: ' . implode(', ', $arrChannels), __METHOD__, TL_NEWSLETTER);

		// HOOK: post activation callback
		if (isset($GLOBALS['TL_HOOKS']['activateRecipient']) && is_array($GLOBALS['TL_HOOKS']['activateRecipient']))
		{
			foreach ($GLOBALS['TL_HOOKS']['activateRecipient'] as $callback)
			{
				$this->import($callback[0]);
				$this->$callback[0]->$callback[1]($objRecipient->email, $arrAdd, $arrCids);
			}
		}

		// Confirm activation
		$this->Template->mclass = 'confirm';
		$this->Template->message = $GLOBALS['TL_LANG']['MSC']['nl_activate'];
	}


	/**
	 * Validate the subscription form
	 *
	 * @param \Widget $objWidget
	 *
	 * @return array|bool
	 */
	protected function validateForm(\Widget $objWidget=null)
	{
		// Validate the e-mail address
		$varInput = \Idna::encodeEmail(\Input::post('email', true));

		if (!\Validator::isEmail($varInput))
		{
			$this->Template->mclass = 'error';
			$this->Template->message = $GLOBALS['TL_LANG']['ERR']['email'];

			return false;
		}

		$this->Template->email = $varInput;

		// Validate the channel selection
		$arrChannels = \Input::post('channels');

		if (!is_array($arrChannels))
		{
			$this->Template->mclass = 'error';
			$this->Template->message = $GLOBALS['TL_LANG']['ERR']['noChannels'];

			return false;
		}

		$arrChannels = array_intersect($arrChannels, $this->nl_channels); // see #3240

		if (!is_array($arrChannels) || empty($arrChannels))
		{
			$this->Template->mclass = 'error';
			$this->Template->message = $GLOBALS['TL_LANG']['ERR']['noChannels'];

			return false;
		}

		$this->Template->selectedChannels = $arrChannels;

		// Check if there are any new subscriptions
		$arrSubscriptions = array();

		if (($objSubscription = \NewsletterRecipientsModel::findBy(array("email=? AND active=1"), $varInput)) !== null)
		{
			$arrSubscriptions = $objSubscription->fetchEach('pid');
		}

		$arrNew = array_diff($arrChannels, $arrSubscriptions);

		if (!is_array($arrNew) || empty($arrNew))
		{
			$this->Template->mclass = 'error';
			$this->Template->message = $GLOBALS['TL_LANG']['ERR']['subscribed'];

			return false;
		}

		// Validate the captcha
		if ($objWidget !== null)
		{
			$objWidget->validate();

			if ($objWidget->hasErrors())
			{
				return false;
			}
		}

		return array($varInput, $arrNew);
	}


	/**
	 * Add a new recipient
	 *
	 * @param string $strEmail
	 * @param array  $arrNew
	 */
	protected function addRecipient($strEmail, $arrNew)
	{
		// Remove old subscriptions that have not been activated yet
		if (($objOld = \NewsletterRecipientsModel::findBy(array("email=? AND active=''"), $strEmail)) !== null)
		{
			while ($objOld->next())
			{
				$objOld->delete();
			}
		}

		$time = time();
		$strToken = md5(uniqid(mt_rand(), true));

		// Add the new subscriptions
		foreach ($arrNew as $id)
		{
			$objRecipient = new \NewsletterRecipientsModel();

			$objRecipient->pid = $id;
			$objRecipient->tstamp = $time;
			$objRecipient->email = $strEmail;
			$objRecipient->active = '';
			$objRecipient->addedOn = $time;
			$objRecipient->ip = $this->anonymizeIp(\Environment::get('ip'));
			$objRecipient->token = $strToken;
			$objRecipient->confirmed = '';

			$objRecipient->save();
		}

		// Get the channels
		$objChannel = \NewsletterChannelModel::findByIds($arrNew);

		// Prepare the simple token data
		$arrData = array();
		$arrData['token'] = $strToken;
		$arrData['domain'] = \Idna::decode(\Environment::get('host'));
		$arrData['link'] = \Idna::decode(\Environment::get('base')) . \Environment::get('request') . ((strpos(\Environment::get('request'), '?') !== false) ? '&' : '?') . 'token=' . $strToken;
		$arrData['channel'] = $arrData['channels'] = implode("\n", $objChannel->fetchEach('title'));

		// Activation e-mail
		$objEmail = new \Email();
		$objEmail->from = $GLOBALS['TL_ADMIN_EMAIL'];
		$objEmail->fromName = $GLOBALS['TL_ADMIN_NAME'];
		$objEmail->subject = sprintf($GLOBALS['TL_LANG']['MSC']['nl_subject'], \Idna::decode(\Environment::get('host')));
		$objEmail->text = \StringUtil::parseSimpleTokens($this->nl_subscribe, $arrData);
		$objEmail->sendTo($strEmail);

		// Redirect to the jumpTo page
		if ($this->jumpTo && ($objTarget = $this->objModel->getRelated('jumpTo')) !== null)
		{
			$this->redirect($this->generateFrontendUrl($objTarget->row()));
		}

		\System::getContainer()->get('session')->getFlashBag()->set('nl_confirm', $GLOBALS['TL_LANG']['MSC']['nl_confirm']);

		$this->reload();
	}
}
