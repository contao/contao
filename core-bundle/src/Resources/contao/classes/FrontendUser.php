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
 * Provide methods to manage front end users.
 *
 * @property array   $allGroups
 * @property string  $loginPage
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

		$this->strIp = Environment::get('ip');
		$this->strHash = Input::cookie($this->strCookie);
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

		$objToken = System::getContainer()->get('security.token_storage')->getToken();

		// Load the user from the security storage
		if ($objToken !== null && is_a($objToken->getUser(), static::class))
		{
			return $objToken->getUser();
		}

		// Check for an authenticated user in the session
		$strUser = System::getContainer()->get('contao.security.token_checker')->getFrontendUsername();

		if ($strUser !== null)
		{
			static::$objInstance = static::loadUserByUsername($strUser);

			return static::$objInstance;
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
	 *             Use Symfony security instead.
	 */
	public function authenticate()
	{
		@trigger_error('Using FrontendUser::authenticate() has been deprecated and will no longer work in Contao 5.0. Use Symfony security instead.', E_USER_DEPRECATED);

		return System::getContainer()->get('contao.security.token_checker')->hasFrontendUser();
	}

	/**
	 * Try to login the current user
	 *
	 * @return boolean True if the user could be logged in
	 *
	 * @deprecated Deprecated since Contao 4.5, to be removed in Contao 5.0.
	 *             Use Symfony security instead.
	 */
	public function login()
	{
		@trigger_error('Using FrontendUser::login() has been deprecated and will no longer work in Contao 5.0. Use Symfony security instead.', E_USER_DEPRECATED);

		return System::getContainer()->get('contao.security.token_checker')->hasFrontendUser();
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
				$this->$k = StringUtil::deserialize($v);
			}
		}

		$GLOBALS['TL_USERNAME'] = $this->username;

		// Make sure that groups is an array
		if (!\is_array($this->groups))
		{
			$this->groups = ($this->groups != '') ? array($this->groups) : array();
		}

		// Skip inactive groups
		if (($objGroups = MemberGroupModel::findAllActive()) !== null)
		{
			$this->groups = array_intersect($this->groups, $objGroups->fetchEach('id'));
		}

		// Get the group login page
		if ($this->groups[0] > 0)
		{
			$objGroup = MemberGroupModel::findPublishedById($this->groups[0]);

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

class_alias(FrontendUser::class, 'FrontendUser');
