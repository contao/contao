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
 * Front end module "newsletter unsubscribe".
 *
 * @property bool   $nl_hideChannels
 * @property string $nl_unsubscribe
 * @property array  $nl_channels
 * @property string $nl_template
 */
class ModuleUnsubscribe extends Module
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
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['unsubscribe'][0] . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id)));

			return $objTemplate->parse();
		}

		$this->nl_channels = StringUtil::deserialize($this->nl_channels);

		// Return if there are no channels
		if (empty($this->nl_channels) || !\is_array($this->nl_channels))
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
			$this->Template = new FrontendTemplate($this->nl_template);
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
				'name' => 'unsubscribe_' . $this->id,
				'label' => $GLOBALS['TL_LANG']['MSC']['securityQuestion'],
				'inputType' => 'captcha',
				'eval' => array('mandatory'=>true)
			);

			$objWidget = new FormCaptcha(FormCaptcha::getAttributesFromDca($arrField, $arrField['name']));
		}

		$strFormId = 'tl_unsubscribe_' . $this->id;

		// Unsubscribe
		if (Input::post('FORM_SUBMIT') == $strFormId)
		{
			$varSubmitted = $this->validateForm($objWidget);

			if ($varSubmitted !== false)
			{
				$this->removeRecipient(...$varSubmitted);
			}
		}

		// Add the captcha widget to the template
		if ($objWidget !== null)
		{
			$this->Template->captcha = $objWidget->parse();
		}

		$session = System::getContainer()->get('session');

		// Confirmation message
		if ($session->isStarted())
		{
			$flashBag = $session->getFlashBag();

			if ($flashBag->has('nl_removed'))
			{
				$arrMessages = $flashBag->get('nl_removed');

				$this->Template->mclass = 'confirm';
				$this->Template->message = $arrMessages[0];
			}
		}

		$arrChannels = array();
		$objChannel = NewsletterChannelModel::findByIds($this->nl_channels);

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
		$this->Template->email = Input::get('email');
		$this->Template->submit = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['unsubscribe']);
		$this->Template->channelsLabel = $GLOBALS['TL_LANG']['MSC']['nl_channels'];
		$this->Template->emailLabel = $GLOBALS['TL_LANG']['MSC']['emailAddress'];
		$this->Template->formId = $strFormId;
		$this->Template->id = $this->id;
		$this->Template->requestToken = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
	}

	/**
	 * Validate the subscription form
	 *
	 * @param Widget $objWidget
	 *
	 * @return array|bool
	 */
	protected function validateForm(Widget $objWidget=null)
	{
		// Validate the e-mail address
		$varInput = Idna::encodeEmail(Input::post('email', true));

		if (!Validator::isEmail($varInput))
		{
			$this->Template->mclass = 'error';
			$this->Template->message = $GLOBALS['TL_LANG']['ERR']['email'];

			return false;
		}

		$this->Template->email = $varInput;

		// Validate the channel selection
		$arrChannels = Input::post('channels');

		if (!\is_array($arrChannels))
		{
			$this->Template->mclass = 'error';
			$this->Template->message = $GLOBALS['TL_LANG']['ERR']['noChannels'];

			return false;
		}

		$arrChannels = array_intersect($arrChannels, $this->nl_channels); // see #3240

		if (empty($arrChannels) || !\is_array($arrChannels))
		{
			$this->Template->mclass = 'error';
			$this->Template->message = $GLOBALS['TL_LANG']['ERR']['noChannels'];

			return false;
		}

		$this->Template->selectedChannels = $arrChannels;

		// Check if there are any new subscriptions
		$arrSubscriptions = array();

		if (($objSubscription = NewsletterRecipientsModel::findBy(array("email=? AND active='1'"), $varInput)) !== null)
		{
			$arrSubscriptions = $objSubscription->fetchEach('pid');
		}

		$arrChannels = array_intersect($arrChannels, $arrSubscriptions);

		if (empty($arrChannels))
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

		return array($varInput, $arrChannels);
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
		if (($objRemove = NewsletterRecipientsModel::findByEmailAndPids($strEmail, $arrRemove)) !== null)
		{
			while ($objRemove->next())
			{
				$strHash = md5($objRemove->email);

				// Add a deny list entry (see #4999)
				if (NewsletterDenyListModel::findByHashAndPid($strHash, $objRemove->pid) === null)
				{
					$objDenyList = new NewsletterDenyListModel();
					$objDenyList->pid = $objRemove->pid;
					$objDenyList->hash = $strHash;
					$objDenyList->save();
				}

				$objRemove->delete();
			}
		}

		// Get the channels
		$objChannels = NewsletterChannelModel::findByIds($arrRemove);
		$arrChannels = $objChannels->fetchEach('title');

		// HOOK: post unsubscribe callback
		if (isset($GLOBALS['TL_HOOKS']['removeRecipient']) && \is_array($GLOBALS['TL_HOOKS']['removeRecipient']))
		{
			foreach ($GLOBALS['TL_HOOKS']['removeRecipient'] as $callback)
			{
				$this->import($callback[0]);
				$this->{$callback[0]}->{$callback[1]}($strEmail, $arrRemove, $this);
			}
		}

		// Prepare the simple token data
		$arrData = array();
		$arrData['domain'] = Idna::decode(Environment::get('host'));
		$arrData['channel'] = $arrData['channels'] = implode("\n", $arrChannels);

		// Confirmation e-mail
		$objEmail = new Email();
		$objEmail->from = $GLOBALS['TL_ADMIN_EMAIL'] ?? null;
		$objEmail->fromName = $GLOBALS['TL_ADMIN_NAME'] ?? null;
		$objEmail->subject = sprintf($GLOBALS['TL_LANG']['MSC']['nl_subject'], Idna::decode(Environment::get('host')));
		$objEmail->text = System::getContainer()->get('contao.string.simple_token_parser')->parse($this->nl_unsubscribe, $arrData);
		$objEmail->sendTo($strEmail);

		// Redirect to the jumpTo page
		if (($objTarget = $this->objModel->getRelated('jumpTo')) instanceof PageModel)
		{
			/** @var PageModel $objTarget */
			$this->redirect($objTarget->getFrontendUrl());
		}

		System::getContainer()->get('session')->getFlashBag()->set('nl_removed', $GLOBALS['TL_LANG']['MSC']['nl_removed']);

		$this->reload();
	}
}

class_alias(ModuleUnsubscribe::class, 'ModuleUnsubscribe');
