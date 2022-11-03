<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

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
 * @property integer           $id
 * @property integer           $tstamp
 * @property string|null       $username
 * @property string            $name
 * @property string            $email
 * @property string            $language
 * @property string            $backendTheme
 * @property string            $uploader
 * @property boolean           $showHelp
 * @property boolean           $thumbnails
 * @property boolean           $useRTE
 * @property boolean           $useCE
 * @property boolean           $doNotCollapse
 * @property string            $password
 * @property boolean           $pwChange
 * @property boolean           $admin
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
 * @property boolean           $disable
 * @property string|integer    $start
 * @property string|integer    $stop
 * @property string|array|null $session
 * @property integer           $dateAdded
 * @property string|null       $secret
 * @property boolean           $useTwoFactor
 * @property integer           $lastLogin
 * @property integer           $currentLogin
 * @property integer           $loginAttempts
 * @property integer           $locked
 * @property string|null       $backupCodes
 * @property integer           $trustedTokenVersion
 * @property string            $firstname
 * @property string            $lastname
 * @property integer           $dateOfBirth
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
 * @property boolean           $login
 * @property boolean           $assignDir
 * @property string            $homeDir
 *
 * @property object $objImport
 * @property object $objAuth
 * @property object $objLogin
 * @property object $objLogout
 */
abstract class User extends System implements UserInterface, EquatableInterface, PasswordAuthenticatedUserInterface
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
			$strModelClass = Model::getClassFromTable($this->strTable);
			$this->arrData = array();

			foreach ($objResult->row() as $strKey => $varData)
			{
				$this->arrData[$strKey] = $strModelClass::convertToPhpValue($strKey, $varData);
			}

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
	 * Return true if the user is member of a particular group
	 *
	 * @param mixed $ids A single group ID or an array of group IDs
	 *
	 * @return boolean True if the user is a member of the group
	 *
	 * @deprecated Deprecated since Contao 5.0, to be removed in Contao 6.
	 */
	public function isMemberOf($ids)
	{
		trigger_deprecation('contao/core-bundle', '5.0', 'Using "%s()" has been deprecated and will no longer work in Contao 6. Use the "ContaoCorePermissions::MEMBER_IN_GROUPS" permission instead.', __METHOD__);

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
	public function getRoles(): array
	{
		return array();
	}

	public static function loadUserByIdentifier(string $identifier): self|null
	{
		if (!System::getContainer()->get('request_stack')->getCurrentRequest())
		{
			return null;
		}

		$user = new static();

		// Load the user object
		if ($user->findBy('username', $identifier) === false)
		{
			return null;
		}

		$user->setUserFromDb();

		return $user;
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
	 * @see PasswordAuthenticatedUserInterface
	 */
	public function getPassword(): string|null
	{
		return $this->password;
	}

	public function setPassword(?string $password): self
	{
		$this->password = $password;

		return $this;
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
	public function isEqualTo(UserInterface $user): bool
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

		if ($this->disable !== $user->disable)
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
}
