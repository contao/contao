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
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
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
 * @property string|integer    $id
 * @property string|integer    $tstamp
 * @property string|null       $username
 * @property string            $name
 * @property string            $email
 * @property string            $language
 * @property string            $backendTheme
 * @property string|boolean    $fullscreen
 * @property string            $uploader
 * @property string|boolean    $showHelp
 * @property string|boolean    $thumbnails
 * @property string|boolean    $useRTE
 * @property string|boolean    $useCE
 * @property string            $password
 * @property string|boolean    $pwChange
 * @property string|boolean    $admin
 * @property string|array|null $groups
 * @property string            $inherit
 * @property string|array|null $modules
 * @property string|array|null $themes
 * @property string|array|null $elements
 * @property string|array|null $fields
 * @property string|array|null $pagemounts
 * @property string|array|null $alpty
 * @property string|array|null $filemounts
 * @property string|array|null $fop
 * @property string|array|null $imageSizes
 * @property string|array|null $forms
 * @property string|array|null $formp
 * @property string|array|null $amg
 * @property string|boolean    $disable
 * @property string|integer    $start
 * @property string|integer    $stop
 * @property string|array|null $session
 * @property string|integer    $dateAdded
 * @property string|null       $secret
 * @property string|boolean    $useTwoFactor
 * @property string|integer    $lastLogin
 * @property string|integer    $currentLogin
 * @property string|integer    $loginAttempts
 * @property string|integer    $locked
 * @property string|null       $backupCodes
 * @property string|integer    $trustedTokenVersion
 * @property string            $firstname
 * @property string            $lastname
 * @property string|integer    $dateOfBirth
 * @property string            $gender
 * @property string            $company
 * @property string            $street
 * @property string            $postal
 * @property string            $city
 * @property string            $state
 * @property string            $country
 * @property string            $phone
 * @property string            $mobile
 * @property string            $fax
 * @property string            $website
 * @property string|boolean    $login
 * @property string|boolean    $assignDir
 * @property string            $homeDir
 *
 * @property object $objImport
 * @property object $objAuth
 * @property object $objLogin
 * @property object $objLogout
 */
abstract class User extends System implements UserInterface, EquatableInterface, PasswordAuthenticatedUserInterface, \Serializable
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
	final public function __clone()
	{
	}

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
		return $this->arrData[$strKey] ?? parent::__get($strKey);
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
		trigger_deprecation('contao/core-bundle', '4.5', 'Using "Contao\User::authenticate()" has been deprecated and will no longer work in Contao 5.0. Use Symfony security instead.');

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
		trigger_deprecation('contao/core-bundle', '4.5', 'Using "Contao\User::login()" has been deprecated and will no longer work in Contao 5.0. Use Symfony security instead.');

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
		trigger_deprecation('contao/core-bundle', '4.5', 'Using "Contao\User::checkAccountStatus()" has been deprecated and will no longer work in Contao 5.0. Use Symfony security instead.');

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
		trigger_deprecation('contao/core-bundle', '4.5', 'Using "Contao\User::regenerateSessionId()" has been deprecated and will no longer work in Contao 5.0. Use Symfony authentication instead.');

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
		trigger_deprecation('contao/core-bundle', '4.5', 'Using "Contao\User::generateSession()" has been deprecated and will no longer work in Contao 5.0. Use Symfony authentication instead.');
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
		trigger_deprecation('contao/core-bundle', '4.5', 'Using "Contao\User::logout()" has been deprecated and will no longer work in Contao 5.0. Use Symfony authentication instead.');

		throw new RedirectResponseException(System::getContainer()->get('security.logout_url_generator')->getLogoutUrl());
	}

	/**
	 * Return true if the user is member of a particular group
	 *
	 * @param mixed $ids A single group ID or an array of group IDs
	 *
	 * @return boolean True if the user is a member of the group
	 */
	public function isMemberOf($ids)
	{
		// Filter non-numeric values
		$ids = array_filter((array) $ids, static function ($val) { return (string) (int) $val === (string) $val; });

		if (empty($ids))
		{
			return false;
		}

		$groups = StringUtil::deserialize($this->groups, true);

		// No groups assigned
		if (empty($groups))
		{
			return false;
		}

		return \count(array_intersect($ids, $groups)) > 0;
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
	 * @return User
	 *
	 * @deprecated Deprecated since Contao 4.13, to be removed in Contao 5.0.
	 *             Use Contao\User::loadUserByIdentifier() instead.
	 */
	public static function loadUserByUsername($username)
	{
		trigger_deprecation('contao/core-bundle', '4.13', 'Using "Contao\User::loadUserByUsername()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\User::loadUserByIdentifier()" instead.');

		return self::loadUserByIdentifier($username);
	}

	public static function loadUserByIdentifier(string $identifier): ?self
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request === null)
		{
			return null;
		}

		$user = new static();
		$isLogin = $request->request->has('password') && $request->isMethod(Request::METHOD_POST);

		// Load the user object
		if ($user->findBy('username', $identifier) === false)
		{
			// Return if its not a real login attempt
			if (!$isLogin)
			{
				return null;
			}

			$password = $request->request->get('password');

			if (self::triggerImportUserHook($identifier, $password, $user->strTable) === false)
			{
				return null;
			}

			if ($user->findBy('username', Input::post('username')) === false)
			{
				return null;
			}
		}

		$user->setUserFromDb();

		return $user;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @deprecated Deprecated since Contao 4.13, to be removed in Contao 5.0.
	 *             Use Contao\User::getUserIdentifier() instead.
	 */
	public function getUsername()
	{
		trigger_deprecation('contao/core-bundle', '4.13', 'Using "Contao\User::getUsername()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\User::getUserIdentifier()" instead.');

		return $this->getUserIdentifier();
	}

	public function setUsername($username)
	{
		$this->username = $username;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getUserIdentifier(): string
	{
		if (null === $this->username)
		{
			throw new \RuntimeException('Missing username in User object');
		}

		if (!\is_string($this->username))
		{
			throw new \RuntimeException(sprintf('Invalid type "%s" for username', \gettype($this->username)));
		}

		return $this->username;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPassword(): ?string
	{
		return $this->password;
	}

	public function setPassword($password)
	{
		$this->password = $password;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getSalt()
	{
		return $this->salt;
	}

	public function setSalt($salt)
	{
		$this->salt = $salt;

		return $this;
	}

	/**
	 * @deprecated Deprecated since Contao 4.9, to be removed in Contao 5.0.
	 */
	public function serialize()
	{
		return serialize($this->__serialize());
	}

	public function __serialize(): array
	{
		return array
		(
			'id' => $this->id,
			'username' => $this->username,
			'password' => $this->password,
			'disable' => $this->disable,
			'start' => $this->start,
			'stop' => $this->stop
		);
	}

	/**
	 * @deprecated Deprecated since Contao 4.9, to be removed in Contao 5.0.
	 */
	public function unserialize($data)
	{
		$this->__unserialize(unserialize($data, array('allowed_classes'=>false)));
	}

	public function __unserialize(array $data): void
	{
		if (array_keys($data) != array('id', 'username', 'password', 'disable', 'start', 'stop'))
		{
			return;
		}

		list($this->id, $this->username, $this->password, $this->disable, $this->start, $this->stop) = array_values($data);
	}

	/**
	 * {@inheritdoc}
	 */
	public function eraseCredentials()
	{
	}

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

		if ($this->password !== $user->password)
		{
			return false;
		}

		if ((bool) $this->disable !== (bool) $user->disable)
		{
			return false;
		}

		if ($this->start !== '' && $this->start > time())
		{
			return false;
		}

		if ($this->stop !== '' && $this->stop <= time())
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
	 *
	 * @deprecated Deprecated since Contao 4.13, to be removed in Contao 5.0.
	 */
	public static function triggerImportUserHook($username, $password, $strTable)
	{
		$self = new static();

		if (empty($GLOBALS['TL_HOOKS']['importUser']) || !\is_array($GLOBALS['TL_HOOKS']['importUser']))
		{
			return false;
		}

		trigger_deprecation('contao/core-bundle', '4.13', 'Using the "importUser" hook has been deprecated and will no longer work in Contao 5.0.');

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
