<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Maintenance module "rebuild index".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class RebuildIndex extends Backend implements \executable
{

	/**
	 * Return true if the module is active
	 *
	 * @return boolean
	 */
	public function isActive()
	{
		return Config::get('enableSearch') && Input::get('act') == 'index';
	}

	/**
	 * Generate the module
	 *
	 * @return string
	 */
	public function run()
	{
		if (!Config::get('enableSearch'))
		{
			return '';
		}

		$this->import(BackendUser::class, 'User');

		$time = time();
		$arrUser = array(''=>'-');
		$objUser = null;

		$objTemplate = new BackendTemplate('be_rebuild_index');
		$objTemplate->action = ampersand(Environment::get('request'));
		$objTemplate->indexHeadline = $GLOBALS['TL_LANG']['tl_maintenance']['searchIndex'];
		$objTemplate->isActive = $this->isActive();
		$objTemplate->message = Message::generateUnwrapped(__CLASS__);

		// Get the active front end users
		if ($this->User->isAdmin)
		{
			$objUser = $this->Database->execute("SELECT id, username FROM tl_member WHERE login='1' AND disable!='1' AND (start='' OR start<='$time') AND (stop='' OR stop>'" . ($time + 60) . "') ORDER BY username");
		}
		else
		{
			$amg = StringUtil::deserialize($this->User->amg);

			if (!empty($amg) && \is_array($amg))
			{
				$objUser = $this->Database->execute("SELECT id, username FROM tl_member WHERE (groups LIKE '%\"" . implode('"%\' OR \'%"', array_map('\intval', $amg)) . "\"%') AND login='1' AND disable!='1' AND (start='' OR start<='$time') AND (stop='' OR stop>'" . ($time + 60) . "') ORDER BY username");
			}
		}

		if ($objUser !== null)
		{
			while ($objUser->next())
			{
				$arrUser[$objUser->id] = $objUser->username . ' (' . $objUser->id . ')';
			}
		}

		// Rebuild the index
		if (Input::get('act') == 'index')
		{
			// Check the request token (see #4007)
			if (!isset($_GET['rt']) || !RequestToken::validate(Input::get('rt')))
			{
				/** @var Session $objSession */
				$objSession = System::getContainer()->get('session');

				$objSession->set('INVALID_TOKEN_URL', Environment::get('request'));
				$this->redirect('contao/confirm.php');
			}

			$arrPages = $this->findSearchablePages();

			// HOOK: take additional pages
			if (isset($GLOBALS['TL_HOOKS']['getSearchablePages']) && \is_array($GLOBALS['TL_HOOKS']['getSearchablePages']))
			{
				foreach ($GLOBALS['TL_HOOKS']['getSearchablePages'] as $callback)
				{
					$this->import($callback[0]);
					$arrPages = $this->{$callback[0]}->{$callback[1]}($arrPages);
				}
			}

			// Return if there are no pages
			if (empty($arrPages))
			{
				Message::addError($GLOBALS['TL_LANG']['tl_maintenance']['noSearchable'], __CLASS__);
				$this->redirect($this->getReferer());
			}

			// Truncate the search tables
			$this->import(Automator::class, 'Automator');
			$this->Automator->purgeSearchTables();

			$objAuthenticator = System::getContainer()->get('contao.security.frontend_preview_authenticator');
			$strUser = Input::get('user');

			// Log in the front end user
			if (is_numeric($strUser) && $strUser > 0 && isset($arrUser[$strUser]))
			{
				$objUser = $this->Database->prepare("SELECT username FROM tl_member WHERE id=?")
										  ->execute($strUser);

				if (!$objUser->numRows || !$objAuthenticator->authenticateFrontendUser($objUser->username, false))
				{
					$objAuthenticator->removeFrontendAuthentication();
				}
			}

			// Log out the front end user
			else
			{
				$objAuthenticator->removeFrontendAuthentication();
			}

			$strBuffer = '';
			$rand = mt_rand();

			// Display the pages
			for ($i=0, $c=\count($arrPages); $i<$c; $i++)
			{
				$strBuffer .= '<span class="page_url" data-url="' . $arrPages[$i] . '#' . $rand . $i . '">' . StringUtil::specialchars(StringUtil::substr(rawurldecode($arrPages[$i]), 100)) . '</span><br>';
				unset($arrPages[$i]); // see #5681
			}

			$objTemplate->content = $strBuffer;
			$objTemplate->note = $GLOBALS['TL_LANG']['tl_maintenance']['indexNote'];
			$objTemplate->loading = $GLOBALS['TL_LANG']['tl_maintenance']['indexLoading'];
			$objTemplate->complete = $GLOBALS['TL_LANG']['tl_maintenance']['indexComplete'];
			$objTemplate->indexContinue = $GLOBALS['TL_LANG']['MSC']['continue'];
			$objTemplate->theme = Backend::getTheme();
			$objTemplate->isRunning = true;

			return $objTemplate->parse();
		}

		// Default variables
		$objTemplate->user = $arrUser;
		$objTemplate->indexLabel = $GLOBALS['TL_LANG']['tl_maintenance']['frontendUser'][0];
		$objTemplate->indexHelp = (Config::get('showHelp') && \strlen($GLOBALS['TL_LANG']['tl_maintenance']['frontendUser'][1])) ? $GLOBALS['TL_LANG']['tl_maintenance']['frontendUser'][1] : '';
		$objTemplate->indexSubmit = $GLOBALS['TL_LANG']['tl_maintenance']['indexSubmit'];

		return $objTemplate->parse();
	}
}

class_alias(RebuildIndex::class, 'RebuildIndex');
