<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Exception\RedirectResponseException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategy;

/**
 * Authenticates and initializes user objects
 *
 * The class supports user authentication, login and logout, persisting the
 * session data and initializing the user object from a database row. It
 * functions as abstract parent class for the "BackendUser" and "FrontendUser"
 * classes of the core.
 *
 * Usage:
 *
 *     $user = BackendUser::getInstance();
 *
 *     if ($user->findBy('username', 'leo'))
 *     {
 *         echo $user->name;
 *     }
 *
 * @property integer $id
 * @property integer $tstamp
 * @property string  $username
 * @property string  $name
 * @property string  $email
 * @property string  $language
 * @property string  $backendTheme
 * @property string  $fullscreen
 * @property string  $uploader
 * @property string  $showHelp
 * @property string  $thumbnails
 * @property string  $useRTE
 * @property string  $useCE
 * @property string  $password
 * @property string  $pwChange
 * @property string  $admin
 * @property array   $groups
 * @property string  $inherit
 * @property string  $modules
 * @property string  $themes
 * @property array   $pagemounts
 * @property string  $alpty
 * @property array   $filemounts
 * @property string  $fop
 * @property string  $forms
 * @property string  $formp
 * @property array   $amg
 * @property string  $disable
 * @property string  $start
 * @property string  $stop
 * @property array   $session
 * @property integer $dateAdded
 * @property integer $lastLogin
 * @property integer $currentLogin
 * @property integer $loginCount
 * @property integer $locked
 * @property string  $firstname
 * @property string  $lastname
 * @property string  $dateOfBirth
 * @property string  $gender
 * @property string  $company
 * @property string  $street
 * @property string  $postal
 * @property string  $city
 * @property string  $state
 * @property string  $country
 * @property string  $phone
 * @property string  $mobile
 * @property string  $fax
 * @property string  $website
 * @property string  $login
 * @property string  $assignDir
 * @property string  $homeDir
 * @property integer $createdOn
 * @property string  $loginPage
 * @property object  $objImport
 * @property object  $objAuth
 * @property object  $objLogin
 * @property object  $objLogout
 * @property string  $useTwoFactor
 * @property string  $secret
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
abstract class User extends System implements UserInterface, EquatableInterface, \Serializable
{

	/**
	 * Object instance (Singleton)
	 * @var User
	 */
	protected static $objInstance;

	/**
	 * User ID
	 * @var integer
	 */
	protected $intId;

	/**
	 * IP address
	 * @var string
	 */
	protected $strIp;

	/**
	 * Authentication hash
	 * @var string
	 */
	protected $strHash;

	/**
	 * Table
	 * @var string
	 */
	protected $strTable;

	/**
	 * Cookie name
	 * @var string
	 */
	protected $strCookie;

	/**
	 * Data
	 * @var array
	 */
	protected $arrData = array();

	/**
	 * Symfony authentication roles
	 * @var array
	 */
	protected $roles = array();

	/**
	 * Salt
	 * @var string
	 */
	protected $salt;

	/**
	 * Import the database object
	 */
	protected function __construct()
	{
		parent::__construct();
		$this->import(Database::class, 'Database');
	}

	/**
	 * Prevent cloning of the object (Singleton)
	 */
	final public function __clone() {}

	/**
	 * Set an object property
	 *
	 * @param string $strKey   The property name
	 * @param mixed  $varValue The property value
	 */
	public function __set($strKey, $varValue)
	{
		$this->arrData[$strKey] = $varValue;
	}

	/**
	 * Return an object property
	 *
	 * @param string $strKey The property name
	 *
	 * @return mixed The property value
	 */
	public function __get($strKey)
	{
		if (isset($this->arrData[$strKey]))
		{
			return $this->arrData[$strKey];
		}

		return parent::__get($strKey);
	}

	/**
	 * Check whether a property is set
	 *
	 * @param string $strKey The property name
	 *
	 * @return boolean True if the property is set
	 */
	public function __isset($strKey)
	{
		return isset($this->arrData[$strKey]);
	}

	/**
	 * Get a string representation of the user
	 *
	 * @return string The string representation
	 */
	public function __toString()
	{
		if (!$this->intId)
		{
			return 'anon.';
		}

		return $this->username ?: ($this->getTable() . '.' . $this->intId);
	}

	/**
	 * Instantiate a new user object (Factory)
	 *
	 * @return static The object instance
	 */
	public static function getInstance()
	{
		if (static::$objInstance === null)
		{
			static::$objInstance = new static();
		}

		return static::$objInstance;
	}

	/**
	 * Return the table name
	 *
	 * @return string
	 */
	public function getTable()
	{
		return $this->strTable;
	}

	/**
	 * Return the current record as associative array
	 *
	 * @return array
	 */
	public function getData()
	{
		return $this->arrData;
	}

	/**
	 * Authenticate a user
	 *
	 * @return boolean True if the user could be authenticated
	 *
	 * @deprecated Deprecated since Contao 4.5, to be removed in Contao 5.0.
	 *             Use Symfony security instead.
	 */
	public function authenticate()
	{
		@trigger_error('Using User::authenticate() has been deprecated and will no longer work in Contao 5.0. Use Symfony security instead.', E_USER_DEPRECATED);

		return false;
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
		@trigger_error('Using User::login() has been deprecated and will no longer work in Contao 5.0. Use Symfony security instead.', E_USER_DEPRECATED);

		return false;
	}

	/**
	 * Check the account status and return true if it is active
	 *
	 * @return boolean True if the account is active
	 *
	 * @deprecated Deprecated since Contao 4.5, to be removed in Contao 5.0.
	 *             Use Symfony security instead.
	 */
	protected function checkAccountStatus()
	{
		@trigger_error('Using User::checkAccountStatus() has been deprecated and will no longer work in Contao 5.0. Use Symfony security instead.', E_USER_DEPRECATED);

		try
		{
			$userChecker = System::getContainer()->get('contao.security.user_checker');
			$userChecker->checkPreAuth($this);
			$userChecker->checkPostAuth($this);
		}
		catch (AuthenticationException $exception)
		{
			return false;
		}

		return true;
	}

	/**
	 * Find a user in the database
	 *
	 * @param string $strColumn The field name
	 * @param mixed  $varValue  The field value
	 *
	 * @return boolean True if the user was found
	 */
	public function findBy($strColumn, $varValue)
	{
		$objResult = $this->Database->prepare("SELECT * FROM " . $this->strTable . " WHERE " . Database::quoteIdentifier($strColumn) . "=?")
									->limit(1)
									->execute($varValue);

		if ($objResult->numRows > 0)
		{
			$this->arrData = $objResult->row();

			return true;
		}

		return false;
	}

	/**
	 * Update the current record
	 */
	public function save()
	{
		$arrFields = $this->Database->getFieldNames($this->strTable);
		$arrSet = array_intersect_key($this->arrData, array_flip($arrFields));

		$this->Database->prepare("UPDATE " . $this->strTable . " %s WHERE id=?")
					   ->set($arrSet)
					   ->execute($this->id);
	}

	/**
	 * Regenerate the session ID
	 *
	 * @deprecated Deprecated since Contao 4.5, to be removed in Contao 5.0.
	 *             Use Symfony authentication instead.
	 */
	protected function regenerateSessionId()
	{
		@trigger_error('Using User::regenerateSessionId() has been deprecated and will no longer work in Contao 5.0. Use Symfony authentication instead.', E_USER_DEPRECATED);

		$container = System::getContainer();
		$strategy = $container->getParameter('security.authentication.session_strategy.strategy');

		// Regenerate the session ID to harden against session fixation attacks
		switch ($strategy)
		{
			case SessionAuthenticationStrategy::NONE:
				break;

			case SessionAuthenticationStrategy::MIGRATE:
				$container->get('session')->migrate(); // do not destroy the old session
				break;

			case SessionAuthenticationStrategy::INVALIDATE:
				$container->get('session')->invalidate();
				break;

			default:
				throw new \RuntimeException(sprintf('Invalid session authentication strategy "%s"', $strategy));
		}
	}

	/**
	 * Generate a session
	 *
	 * @deprecated Deprecated since Contao 4.5, to be removed in Contao 5.0.
	 *             Use Symfony authentication instead.
	 */
	protected function generateSession()
	{
		@trigger_error('Using User::generateSession() has been deprecated and will no longer work in Contao 5.0. Use Symfony authentication instead.', E_USER_DEPRECATED);
	}

	/**
	 * Remove the authentication cookie and destroy the current session
	 *
	 * @throws RedirectResponseException
	 *
	 * @deprecated Deprecated since Contao 4.5, to be removed in Contao 5.0.
	 *             Use Symfony authentication instead.
	 */
	public function logout()
	{
		@trigger_error('Using User::logout() has been deprecated and will no longer work in Contao 5.0. Use Symfony authentication instead.', E_USER_DEPRECATED);

		throw new RedirectResponseException(System::getContainer()->get('security.logout_url_generator')->getLogoutUrl());
	}

	/**
	 * Return true if the user is member of a particular group
	 *
	 * @param integer $id The group ID
	 *
	 * @return boolean True if the user is a member of the group
	 */
	public function isMemberOf($id)
	{
		// ID not numeric
		if (!is_numeric($id))
		{
			return false;
		}

		$groups = StringUtil::deserialize($this->arrData['groups']);

		// No groups assigned
		if (empty($groups) || !\is_array($groups))
		{
			return false;
		}

		// Group ID found
		if (\in_array($id, $groups))
		{
			return true;
		}

		return false;
	}

	/**
	 * Set all user properties from a database record
	 */
	abstract protected function setUserFromDb();

	/**
	 * {@inheritdoc}
	 */
	public function getRoles()
	{
		return array();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return User
	 */
	public static function loadUserByUsername($username)
	{
		/** @var Request $request */
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request === null)
		{
			return null;
		}

		$user = new static();
		$isLogin = $request->request->has('password') && $request->isMethod(Request::METHOD_POST);

		// Load the user object
		if ($user->findBy('username', $username) === false)
		{
			// Return if its not a real login attempt
			if (!$isLogin)
			{
				return null;
			}

			$password = $request->request->get('password');

			if (self::triggerImportUserHook($username, $password, $user->strTable) === false)
			{
				return null;
			}

			if ($user->findBy('username', Input::post('username')) === false)
			{
				return null;
			}
		}

		// Check if a passwords needs rehashing (see contao/core#8820)
		if ($isLogin)
		{
			$blnAuthenticated = password_verify($request->request->get('password'), $user->password);
			$blnNeedsRehash = password_needs_rehash($user->password, PASSWORD_DEFAULT);

			// Re-hash the password if the algorithm has changed
			if ($blnAuthenticated && $blnNeedsRehash)
			{
				$user->password = password_hash($request->request->get('password'), PASSWORD_DEFAULT);
				$user->save();
			}
		}

		$user->setUserFromDb();

		return $user;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getUsername()
	{
		return $this->arrData['username'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function setUsername($username)
	{
		$this->arrData['username'] = $username;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPassword()
	{
		return $this->arrData['password'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function setPassword($password)
	{
		$this->arrData['password'] = $password;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getSalt()
	{
		return $this->salt;
	}

	/**
	 * {@inheritdoc}
	 */
	public function setSalt($salt)
	{
		$this->salt = $salt;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function serialize()
	{
		return serialize(array($this->id, $this->username, $this->disable, $this->admin, $this->groups));
	}

	/**
	 * {@inheritdoc}
	 */
	public function unserialize($serialized)
	{
		list($this->id, $this->username, $this->disable, $this->admin, $this->groups) = unserialize($serialized, array('allowed_classes'=>false));
	}

	/**
	 * {@inheritdoc}
	 */
	public function eraseCredentials() {}

	/**
	 * {@inheritdoc}
	 */
	public function isEqualTo(UserInterface $user)
	{
		if (!$user instanceof self)
		{
			return false;
		}

		if ($this->getRoles() !== $user->getRoles())
		{
			return false;
		}

		if ((bool) $this->admin !== (bool) $user->admin)
		{
			return false;
		}

		if ($this->groups !== $user->groups)
		{
			return false;
		}

		if ((bool) $this->disable !== (bool) $user->disable)
		{
			return false;
		}

		return true;
	}

	/**
	 * Trigger the importUser hook
	 *
	 * @param $username
	 * @param $password
	 * @param $strTable
	 *
	 * @return bool|static
	 */
	public static function triggerImportUserHook($username, $password, $strTable)
	{
		$self = new static();

		if (empty($GLOBALS['TL_HOOKS']['importUser']) || !\is_array($GLOBALS['TL_HOOKS']['importUser']))
		{
			return false;
		}

		@trigger_error('Using the "importUser" hook has been deprecated and will no longer work in Contao 5.0. Use the contao.import_user event instead.', E_USER_DEPRECATED);

		foreach ($GLOBALS['TL_HOOKS']['importUser'] as $callback)
		{
			$self->import($callback[0], 'objImport', true);
			$blnLoaded = $self->objImport->{$callback[1]}($username, $password, $strTable);

			// Load successfull
			if ($blnLoaded === true)
			{
				return true;
			}
		}

		return false;
	}
}

class_alias(User::class, 'User');
