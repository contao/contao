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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Back end help wizard.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class BackendPassword extends Backend
{
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
		System::loadLanguageFile('modules');
	}

	/**
	 * Run the controller and parse the password template
	 *
	 * @return Response
	 */
	public function run()
	{
		$container = System::getContainer();

		/** @var Request $request */
		$request = $container->get('request_stack')->getCurrentRequest();

		Controller::loadDataContainer('tl_user');

		$dc = new DC_Table('tl_user');
		$dc->id = $this->User->id;
		$dc->activeRecord = $this->User;

		$widget = new Password(Password::getAttributesFromDca($GLOBALS['TL_DCA']['tl_user']['fields']['password'], 'password'));
		$widget->template = 'be_widget_chpw';
		$widget->dataContainer = $dc;
		$widget->password = $GLOBALS['TL_LANG']['MSC']['password'][0];
		$widget->confirm = $GLOBALS['TL_LANG']['MSC']['confirm'][0];

		$objTemplate = new BackendTemplate('be_password');
		$objTemplate->widget = $widget->parse();

		if (Input::post('FORM_SUBMIT') == 'tl_password')
		{
			$widget->validate();

			// $widget->value returns the password hash, so get the value from the request object
			$pw = $request->request->get('password');

			if ($widget->hasErrors())
			{
				Message::addError($widget->getErrorAsString());
			}
			// Password and username are the same
			elseif ($pw == $this->User->username)
			{
				Message::addError($GLOBALS['TL_LANG']['ERR']['passwordName']);
			}
			// Save the data
			else
			{
				$encoder = $container->get('security.encoder_factory')->getEncoder(BackendUser::class);

				// Make sure the password has been changed
				if ($encoder->isPasswordValid($this->User->password, $pw, null))
				{
					Message::addError($GLOBALS['TL_LANG']['MSC']['pw_change']);
				}
				else
				{
					// Trigger the save_callback
					if (\is_array($GLOBALS['TL_DCA']['tl_user']['fields']['password']['save_callback']))
					{
						$dc = new DC_Table('tl_user');
						$dc->id = $this->User->id;

						foreach ($GLOBALS['TL_DCA']['tl_user']['fields']['password']['save_callback'] as $callback)
						{
							if (\is_array($callback))
							{
								$this->import($callback[0]);
								$pw = $this->{$callback[0]}->{$callback[1]}($pw, $dc);
							}
							elseif (\is_callable($callback))
							{
								$pw = $callback($pw, $dc);
							}
						}
					}

					$objUser = UserModel::findByPk($this->User->id);
					$objUser->pwChange = '';
					$objUser->password = $encoder->encodePassword($pw, null);
					$objUser->save();

					Message::addConfirmation($GLOBALS['TL_LANG']['MSC']['pw_changed']);
					$this->redirect('contao/main.php');
				}
			}

			$this->reload();
		}

		$objTemplate->theme = Backend::getTheme();
		$objTemplate->messages = Message::generate();
		$objTemplate->base = Environment::get('base');
		$objTemplate->language = $GLOBALS['TL_LANGUAGE'];
		$objTemplate->title = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['pw_new']);
		$objTemplate->host = Backend::getDecodedHostname();
		$objTemplate->charset = Config::get('characterSet');
		$objTemplate->headline = $GLOBALS['TL_LANG']['MSC']['pw_new'];
		$objTemplate->explain = $GLOBALS['TL_LANG']['MSC']['pw_change'];
		$objTemplate->submitButton = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['continue']);

		return $objTemplate->getResponse();
	}
}

class_alias(BackendPassword::class, 'BackendPassword');
