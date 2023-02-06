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
use Symfony\Component\HttpFoundation\Response;

/**
 * Back end help wizard.
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
		$widget->wizard = Backend::getTogglePasswordWizard('password');
		$widget->currentRecord = $this->User->id;

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
				$passwordHasher = $container->get('security.password_hasher_factory')->getPasswordHasher(BackendUser::class);

				// Make sure the password has been changed
				if ($passwordHasher->verify($this->User->password, $pw))
				{
					Message::addError($GLOBALS['TL_LANG']['MSC']['pw_change']);
				}
				else
				{
					// Trigger the save_callback
					if (\is_array($GLOBALS['TL_DCA']['tl_user']['fields']['password']['save_callback'] ?? null))
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
					$objUser->pwChange = false;
					$objUser->password = $passwordHasher->hash($pw);
					$objUser->save();

					Message::addConfirmation($GLOBALS['TL_LANG']['MSC']['pw_changed']);
					$this->redirect(System::getContainer()->get('router')->generate('contao_backend'));
				}
			}

			$this->reload();
		}

		$objTemplate->theme = Backend::getTheme();
		$objTemplate->messages = Message::generate();
		$objTemplate->language = $GLOBALS['TL_LANGUAGE'];
		$objTemplate->title = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['pw_new']);
		$objTemplate->host = Backend::getDecodedHostname();
		$objTemplate->charset = System::getContainer()->getParameter('kernel.charset');
		$objTemplate->headline = $GLOBALS['TL_LANG']['MSC']['pw_new'];
		$objTemplate->explain = $GLOBALS['TL_LANG']['MSC']['pw_change'];
		$objTemplate->submitButton = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['continue']);

		return $objTemplate->getResponse();
	}
}
