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
 * Front end module "newsletter unsubscribe".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ModuleUnsubscribe extends \Module
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

			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['unsubscribe'][0]) . ' ###';
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

		$objWidget = null;

		// Set up the captcha widget
		if (!$this->disableCaptcha)
		{
			$arrField = array
			(
				'name' => 'unsubscribe',
				'label' => $GLOBALS['TL_LANG']['MSC']['securityQuestion'],
				'inputType' => 'captcha',
				'eval' => array('mandatory'=>true)
			);

			/** @var \Widget $objWidget */
			$objWidget = new \FormCaptcha(\FormCaptcha::getAttributesFromDca($arrField, $arrField['name']));
		}

		$strFormId = 'tl_unsubscribe_' . $this->id;

		// Unsubscribe
		if (\Input::post('FORM_SUBMIT') == $strFormId)
		{
			$varSubmitted = $this->validateForm($objWidget);

			if ($varSubmitted !== false)
			{
				call_user_func_array(array($this, 'removeRecipient'), $varSubmitted);
			}
		}

		// Add the captcha widget to the template
		if ($objWidget !== null)
		{
			$this->Template->captcha = $objWidget->parse();
		}

		$flashBag = \System::getContainer()->get('session')->getFlashBag();

		// Confirmation message
		if ($flashBag->has('nl_removed'))
		{
			$arrMessages = $flashBag->get('nl_removed');

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
		$this->Template->submit = specialchars($GLOBALS['TL_LANG']['MSC']['unsubscribe']);
		$this->Template->channelsLabel = $GLOBALS['TL_LANG']['MSC']['nl_channels'];
		$this->Template->emailLabel = $GLOBALS['TL_LANG']['MSC']['emailAddress'];
		$this->Template->action = \Environment::get('indexFreeRequest');
		$this->Template->formId = $strFormId;
		$this->Template->id = $this->id;
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

		$arrRemove = array_intersect($arrChannels, $arrSubscriptions);

		if (!is_array($arrRemove) || empty($arrRemove))
		{
			$this->Template->mclass = 'error';
			$this->Template->message = $GLOBALS['TL_LANG']['ERR']['unsubscribed'];

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

		return array($varInput, $arrRemove);
	}


	/**
	 * Remove the recipient
	 *
	 * @param string $strEmail
	 * @param array  $arrRemove
	 */
	protected function removeRecipient($strEmail, $arrRemove)
	{
		// Remove the subscriptions
		if (($objRemove = \NewsletterRecipientsModel::findByEmailAndPids($strEmail, $arrRemove)) !== null)
		{
			while ($objRemove->next())
			{
				$objRemove->delete();
			}
		}

		// Get the channels
		$objChannels = \NewsletterChannelModel::findByIds($arrRemove);
		$arrChannels = $objChannels->fetchEach('title');

		// Log activity
		$this->log($strEmail . ' unsubscribed from ' . implode(', ', $arrChannels), __METHOD__, TL_NEWSLETTER);

		// HOOK: post unsubscribe callback
		if (isset($GLOBALS['TL_HOOKS']['removeRecipient']) && is_array($GLOBALS['TL_HOOKS']['removeRecipient']))
		{
			foreach ($GLOBALS['TL_HOOKS']['removeRecipient'] as $callback)
			{
				$this->import($callback[0]);
				$this->$callback[0]->$callback[1]($strEmail, $arrRemove);
			}
		}

		// Prepare the simple token data
		$arrData = array();
		$arrData['domain'] = \Idna::decode(\Environment::get('host'));
		$arrData['channel'] = $arrData['channels'] = implode("\n", $arrChannels);

		// Confirmation e-mail
		$objEmail = new \Email();
		$objEmail->from = $GLOBALS['TL_ADMIN_EMAIL'];
		$objEmail->fromName = $GLOBALS['TL_ADMIN_NAME'];
		$objEmail->subject = sprintf($GLOBALS['TL_LANG']['MSC']['nl_subject'], \Idna::decode(\Environment::get('host')));
		$objEmail->text = \StringUtil::parseSimpleTokens($this->nl_unsubscribe, $arrData);
		$objEmail->sendTo($strEmail);

		// Redirect to the jumpTo page
		if ($this->jumpTo && ($objTarget = $this->objModel->getRelated('jumpTo')) !== null)
		{
			$this->redirect($this->generateFrontendUrl($objTarget->row()));
		}

		\System::getContainer()->get('session')->getFlashBag()->set('nl_removed', $GLOBALS['TL_LANG']['MSC']['nl_removed']);

		$this->reload();
	}
}
