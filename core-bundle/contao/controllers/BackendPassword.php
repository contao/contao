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
		$objTemplate = new BackendTemplate('be_password');

		if (Input::post('FORM_SUBMIT') == 'tl_password')
		{
			$pw = System::getContainer()->get('request_stack')->getCurrentRequest()->request->get('password');

			// Password too short
			if (mb_strlen($pw) < Config::get('minPasswordLength'))
			{
				Message::addError(sprintf($GLOBALS['TL_LANG']['ERR']['passwordLength'], Config::get('minPasswordLength')));
			}
			// Password and username are the same
			elseif ($pw == $this->User->username)
			{
				Message::addError($GLOBALS['TL_LANG']['ERR']['passwordName']);
			}
			// Save the data
			else
			{
				$passwordHasher = System::getContainer()->get('security.password_hasher_factory')->getPasswordHasher(BackendUser::class);

				// Make sure the password has been changed
				if ($passwordHasher->verify($this->User->password, $pw))
				{
					Message::addError($GLOBALS['TL_LANG']['MSC']['pw_change']);
				}
				else
				{
					$this->loadDataContainer('tl_user');

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
					$objUser->pwChange = '';
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
		$objTemplate->wizard = Backend::getTogglePasswordWizard('password');
		$objTemplate->submitButton = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['continue']);
		$objTemplate->password = $GLOBALS['TL_LANG']['MSC']['password'][0];

		return $objTemplate->getResponse();
	}
}
