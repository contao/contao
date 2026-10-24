<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Security\User\BackendUserFactory;
use Contao\CoreBundle\Security\User\ContaoUser;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Provide methods to manage back end users.
 *
 * @property boolean $isAdmin
 * @property array   $groups
 * @property array   $elements
 * @property array   $fields
 * @property array   $frontendModules
 * @property array   $pagemounts
 * @property array   $filemounts
 * @property string  $fop
 * @property array   $alexf
 * @property array   $cud
 * @property array   $imageSizes
 * @property string  $doNotHideMessages
 */
class BackendUser extends ContaoUser
{
	/**
	 * Current object instance (do not remove)
	 * @var BackendUser
	 */
	protected static $objInstance;

	/**
	 * Name of the corresponding table
	 * @var string
	 */
	protected $strTable = 'tl_user';

	/**
	 * Symfony security roles
	 * @var array
	 */
	protected $roles = array('ROLE_USER');

	/**
	 * Instantiate a new user object
	 *
	 * @return static|User The object instance
	 */
	public static function getInstance()
	{
		trigger_deprecation('contao/core-bundle', '6.1', 'Calling BackendUser::getInstance is deprecated in Contao 6.1 and will be removed in Contao 7. Get the user from the Symfony security services instead.');

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
		$strUser = System::getContainer()->get('contao.security.token_checker')->getBackendUsername();

		if ($strUser !== null)
		{
			static::$objInstance = System::getContainer()->get('contao.security.backend_user_provider')->loadUserByIdentifier($strUser);

			return static::$objInstance;
		}

		return parent::getInstance();
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
			case 'isAdmin':
				trigger_deprecation('contao/core-bundle', '6.1', 'Using BackendUser::isAdmin is deprecated in Contao 6.1 and will be removed in Contao 7. Use the ROLE_ADMIN permission instead.');

				return (bool) $this->arrData['admin'];

			case 'groups':
			case 'alexf':
			case 'cud':
				return \is_array($this->arrData[$strKey] ?? null) ? $this->arrData[$strKey] : (($this->arrData[$strKey] ?? null) ? array($this->arrData[$strKey]) : array());

			case 'pagemounts':
			case 'filemounts':
			case 'fop':
				return \is_array($this->arrData[$strKey] ?? null) ? $this->arrData[$strKey] : (($this->arrData[$strKey] ?? null) ? array($this->arrData[$strKey]) : false);

			case 'filemountIds':
				trigger_deprecation('contao/core-bundle', '6.1', 'Getting BackendUser::fileMountIds is deprecated in Contao 6.1 and will be removed in Contao 7.');

				return FilesModel::findMultipleByPaths($this->filemounts)?->fetchEach('uuid') ?? array();
		}

		return parent::__get($strKey);
	}

	/**
	 * Check whether the current user has a certain access right
	 *
	 * @param array|string $field
	 * @param string       $array
	 *
	 * @return boolean
	 *
	 * @deprecated Deprecated since Contao 5.2, to be removed in Contao 7;
	 *             use the "ContaoCorePermissions::USER_CAN_ACCESS_*" permissions instead.
	 */
	public function hasAccess($field, $array)
	{
		trigger_deprecation('contao/core-bundle', '5.2', 'Using "%s()" is deprecated and will no longer work in Contao 7. Use the "ContaoCorePermissions::USER_CAN_ACCESS_*" permissions instead.', __METHOD__);

		return System::getContainer()->get('security.authorization_checker')->isGrantedForUser($this, 'contao_user.' . $array, $field);
	}

	/**
	 * Exclude permission fields while saving
	 *
	 * @deprecated Deprecated since Contao 6.1, to be removed in Contao 7;
	 *             Update the database directly instead.
	 */
	public function save()
	{
		trigger_deprecation('contao/core-bundle', '6.1', 'Saving the BackendUser object is deprecated in Contao 6.1 and will be removed in Contao 7. Update the database directly instead.');

		$arrData = $this->arrData;
		$permissionFields = BackendUserFactory::getPermissionFields();

		$this->arrData = array_diff_key($this->arrData, array_flip($permissionFields));

		try
		{
			parent::save();
		}
		finally
		{
			$this->arrData = $arrData;
		}
	}

	/**
	 * Set all user properties from a database record
	 *
	 * @deprecated Deprecated since Contao 6.1, to be removed in Contao 7.
	 *             Use the BackendUserFactory instead.
	 */
	protected function setUserFromDb()
	{
		trigger_deprecation('contao/core-bundle', '6.1', 'Using %s is deprecated in Contao 6.1 and will be removed in Contao 7. Use the %s instead.', __METHOD__, BackendUserFactory::class);

		$user = System::getContainer()->get('contao.security.backend_user_factory')->create($this->arrData);
		$this->arrData = $user->arrData;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRoles(): array
	{
		if ($this->admin)
		{
			return array('ROLE_USER', 'ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH', 'ROLE_ALLOWED_TO_SWITCH_MEMBER');
		}

		if (!empty($this->amg) && \is_array($this->amg))
		{
			return array('ROLE_USER', 'ROLE_ALLOWED_TO_SWITCH_MEMBER');
		}

		return $this->roles;
	}

	public function __serialize(): array
	{
		return array('admin' => $this->admin, 'amg' => $this->amg, 'parent' => parent::__serialize());
	}

	public function __unserialize(array $data): void
	{
		if (array_keys($data) != array('admin', 'amg', 'parent'))
		{
			return;
		}

		list($this->admin, $this->amg, $parent) = array_values($data);

		parent::__unserialize($parent);
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

		if ($this->admin !== $user->admin)
		{
			return false;
		}

		return parent::isEqualTo($user);
	}

	public function getDisplayName(): string
	{
		return $this->name;
	}

	public function getPasskeyUserHandle(): string
	{
		return 'backend.' . $this->id;
	}
}
