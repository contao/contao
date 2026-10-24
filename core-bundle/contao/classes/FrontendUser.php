<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Security\User\ContaoUser;
use Contao\CoreBundle\Security\User\FrontendUserFactory;

/**
 * Provide methods to manage front end users.
 *
 * @property array  $allGroups
 * @property string $loginPage
 */
class FrontendUser extends ContaoUser
{
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
	 * Instantiate a new user object
	 *
	 * @return static|User The object instance
	 *
	 * @deprecated Deprecated since Contao 6.1, to be removed in Contao 7;
	 *             get the user from the Symfony security services instead.
	 */
	public static function getInstance()
	{
		trigger_deprecation('contao/core-bundle', '6.1', 'Calling "%s()" is deprecated and will no longer work in Contao 7. Get the user from the Symfony security services instead.', __METHOD__);

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
			static::$objInstance = static::loadUserByIdentifier($strUser);

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
		if ($strKey == 'allGroups')
		{
			$this->arrGroups = $varValue;
		}
		else
		{
			parent::__set($strKey, $varValue);
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

			case 'loginPage':
				return $this->strLoginPage;
		}

		return parent::__get($strKey);
	}

	/**
	 * Save the original group membership
	 *
	 * @param string $strColumn
	 * @param mixed  $varValue
	 *
	 * @return boolean
	 *
	 * @deprecated Deprecated since Contao 6.1, to be removed in Contao 7;
	 *             use the FrontendUserFactory instead.
	 */
	public function findBy($strColumn, $varValue)
	{
		trigger_deprecation('contao/core-bundle', '6.1', 'Using %s is deprecated in Contao 6.1 and will be removed in Contao 7. Use the %s instead.', __METHOD__, FrontendUserFactory::class);

		if (parent::findBy($strColumn, $varValue) === false)
		{
			return false;
		}

		$this->arrGroups = $this->groups;

		return true;
	}

	/**
	 * Restore the original group membership
	 *
	 * @deprecated Deprecated since Contao 6.1, to be removed in Contao 7.
	 */
	public function save()
	{
		trigger_deprecation('contao/core-bundle', '6.1', 'Saving the BackendUser object is deprecated in Contao 6.1 and will be removed in Contao 7. Update the database directly instead.');

		$groups = $this->groups;
		$this->arrData['groups'] = $this->arrGroups;
		parent::save();
		$this->groups = $groups;
	}

	/**
	 * Set all user properties from a database record
	 *
	 * @deprecated Deprecated since Contao 6.1, to be removed in Contao 7;
	 *             use the FrontendUserFactory instead.
	 */
	protected function setUserFromDb()
	{
		trigger_deprecation('contao/core-bundle', '6.1', 'Using %s is deprecated in Contao 6.1 and will be removed in Contao 7. Use the %s instead.', __METHOD__, FrontendUserFactory::class);

		$user = System::getContainer()->get('contao.security.frontend_user_factory')->create($this->arrData);
		$this->arrData = $user->arrData;
		$this->strLoginPage = $user->strLoginPage;
		$this->arrGroups = $user->arrGroups;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRoles(): array
	{
		return $this->roles;
	}

	public function getDisplayName(): string
	{
		return trim("$this->firstname $this->lastname");
	}

	public function getPasskeyUserHandle(): string
	{
		return 'frontend.' . $this->id;
	}
}
