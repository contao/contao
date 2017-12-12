<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;


/**
 * Provide methods to manage front end users.
 *
 * @property array   $allGroups
 * @property string  $loginPage
 * @property boolean $blnRecordExists
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class FrontendUser extends User
{

	/**
	 * Symfony Security session key
	 * @var string
	 */
	const SECURITY_SESSION_KEY = '_security_contao_frontend';

	/**
	 * Current object instance (do not remove)
	 * @var FrontendUser
	 */
	protected static $objInstance;

	/**
	 * Name of the corresponding table
	 * @var string
	 */
	protected $strTable = 'tl_member';

	/**
	 * Name of the current cookie
	 * @var string
	 */
	protected $strCookie = 'FE_USER_AUTH';

	/**
	 * Group login page
	 * @var string
	 */
	protected $strLoginPage;

	/**
	 * Groups
	 * @var array
	 */
	protected $arrGroups;

	/**
	 * Symfony security roles
	 * @var array
	 */
	protected $roles = array('ROLE_MEMBER');


	/**
	 * Initialize the object
	 */
	protected function __construct()
	{
		parent::__construct();

		$this->strIp = \Environment::get('ip');
		$this->strHash = \Input::cookie($this->strCookie);
	}


	/**
	 * Instantiate a new user object
	 *
	 * @return static|User The object instance
	 */
	public static function getInstance()
	{
		if (static::$objInstance !== null)
		{
			return static::$objInstance;
		}

		/** @var TokenInterface $token */
		$token = \System::getContainer()->get('security.token_storage')->getToken();

		// Try to load user from security storage
		if ($token !== null && is_a($token->getUser(), static::class))
		{
			return static::loadUserByUsername($token->getUser()->getUsername());
		}

		/** @var SessionInterface $session */
		$session = \System::getContainer()->get('session');

		if (!$session->has(self::SECURITY_SESSION_KEY))
		{
			return parent::getInstance();
		}

		// Try to load possibly authenticated FrontendUser from session
		if (!($token = unserialize($session->get(self::SECURITY_SESSION_KEY))) instanceof TokenInterface)
		{
			return parent::getInstance();
		}

		if ($token->isAuthenticated())
		{
			return static::loadUserByUsername($token->getUser()->getUsername());
		}

		return parent::getInstance();
	}


	/**
	 * Extend parent setter class and modify some parameters
	 *
	 * @param string $strKey
	 * @param mixed  $varValue
	 */
	public function __set($strKey, $varValue)
	{
		switch ($strKey)
		{
			case 'allGroups':
				$this->arrGroups = $varValue;
				break;

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}


	/**
	 * Extend parent getter class and modify some parameters
	 *
	 * @param string $strKey
	 *
	 * @return mixed
	 */
	public function __get($strKey)
	{
		switch ($strKey)
		{
			case 'allGroups':
				return $this->arrGroups;
				break;

			case 'loginPage':
				return $this->strLoginPage;
				break;
		}

		return parent::__get($strKey);
	}


	/**
	 * Authenticate a user
	 *
	 * @return boolean
	 *
	 * @deprecated Deprecated since Contao 4.5, to be removed in Contao 5.0.
	 *             Use the security.authentication.success event instead.
	 */
	public function authenticate()
	{
		@trigger_error('Using FrontendUser::authenticate() has been deprecated and will no longer work in Contao 5.0. Use the security.authentication.success event instead.', E_USER_DEPRECATED);

		return false;
	}


	/**
	 * Add the auto login resources
	 *
	 * @return boolean
	 *
	 * @deprecated Deprecated since Contao 4.5, to be removed in Contao 5.0.
	 *             Use the security.interactive_login event instead.
	 */
	public function login()
	{
		@trigger_error('Using FrontendUser::login() has been deprecated and will no longer work in Contao 5.0. Use the security.interactive_login event instead.', E_USER_DEPRECATED);

		return parent::login();
	}


	/**
	 * Remove the auto login resources
	 *
	 * @return boolean
	 *
	 * @deprecated Deprecated since Contao 4.5, to be removed in Contao 5.0.
	 */
	public function logout()
	{
		@trigger_error('Using FrontendUser::logout() has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);

		return parent::logout();
	}


	/**
	 * Save the original group membership
	 *
	 * @param string $strColumn
	 * @param mixed  $varValue
	 *
	 * @return boolean
	 */
	public function findBy($strColumn, $varValue)
	{
		if (parent::findBy($strColumn, $varValue) === false)
		{
			return false;
		}

		$this->arrGroups = $this->groups;

		return true;
	}


	/**
	 * Restore the original group membership
	 */
	public function save()
	{
		$groups = $this->groups;
		$this->arrData['groups'] = $this->arrGroups;
		parent::save();
		$this->groups = $groups;
	}


	/**
	 * Set all user properties from a database record
	 */
	protected function setUserFromDb()
	{
		$this->intId = $this->id;

		// Unserialize values
		foreach ($this->arrData as $k=>$v)
		{
			if (!is_numeric($v))
			{
				$this->$k = \StringUtil::deserialize($v);
			}
		}

		// Set the language
		if ($this->language)
		{
			if (\System::getContainer()->has('session'))
			{
				$session = \System::getContainer()->get('session');

				if ($session->isStarted())
				{
					$session->set('_locale', $this->language);
				}
			}

			\System::getContainer()->get('request_stack')->getCurrentRequest()->setLocale($this->language);
			\System::getContainer()->get('translator')->setLocale($this->language);

			// Deprecated since Contao 4.0, to be removed in Contao 5.0
			$GLOBALS['TL_LANGUAGE'] = str_replace('_', '-', $this->language);
		}

		$GLOBALS['TL_USERNAME'] = $this->username;

		// Make sure that groups is an array
		if (!\is_array($this->groups))
		{
			$this->groups = ($this->groups != '') ? array($this->groups) : array();
		}

		// Skip inactive groups
		if (($objGroups = \MemberGroupModel::findAllActive()) !== null)
		{
			$this->groups = array_intersect($this->groups, $objGroups->fetchEach('id'));
		}

		// Get the group login page
		if ($this->groups[0] > 0)
		{
			$objGroup = \MemberGroupModel::findPublishedById($this->groups[0]);

			if ($objGroup !== null && $objGroup->redirect && $objGroup->jumpTo)
			{
				$this->strLoginPage = $objGroup->jumpTo;
			}
		}
	}


	/**
	 * {@inheritdoc}
	 */
	public function getRoles()
	{
		return $this->roles;
	}
}
