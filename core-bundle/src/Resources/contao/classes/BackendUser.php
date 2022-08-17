<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Provide methods to manage back end users.
 *
 * @property boolean $isAdmin
 * @property array   $groups
 * @property array   $elements
 * @property array   $fields
 * @property array   $pagemounts
 * @property array   $filemounts
 * @property array   $filemountIds
 * @property string  $fop
 * @property array   $alexf
 * @property array   $imageSizes
 */
class BackendUser extends User
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
	 * Name of the current cookie
	 * @var string
	 */
	protected $strCookie = 'BE_USER_AUTH';

	/**
	 * Allowed excluded fields
	 * @var array
	 */
	protected $alexf = array();

	/**
	 * File mount IDs
	 * @var array
	 */
	protected $arrFilemountIds;

	/**
	 * Symfony security roles
	 * @var array
	 */
	protected $roles = array('ROLE_USER');

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
		$strUser = System::getContainer()->get('contao.security.token_checker')->getBackendUsername();

		if ($strUser !== null)
		{
			static::$objInstance = static::loadUserByIdentifier($strUser);

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
				return $this->arrData['admin'] ? true : false;

			case 'groups':
				return \is_array($this->arrData['groups']) ? $this->arrData['groups'] : ($this->arrData['groups'] ? array($this->arrData['groups']) : array());

			case 'pagemounts':
				return \is_array($this->arrData['pagemounts']) ? $this->arrData['pagemounts'] : ($this->arrData['pagemounts'] ? array($this->arrData['pagemounts']) : false);

			case 'filemounts':
				return \is_array($this->arrData['filemounts']) ? $this->arrData['filemounts'] : ($this->arrData['filemounts'] ? array($this->arrData['filemounts']) : false);

			case 'filemountIds':
				return $this->arrFilemountIds;

			case 'fop':
				return \is_array($this->arrData['fop']) ? $this->arrData['fop'] : ($this->arrData['fop'] ? array($this->arrData['fop']) : false);

			case 'alexf':
				return $this->alexf;
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
	 */
	public function hasAccess($field, $array)
	{
		if ($this->isAdmin)
		{
			return true;
		}

		if (!\is_array($field))
		{
			$field = array($field);
		}

		if (\is_array($this->$array) && array_intersect($field, $this->$array))
		{
			return true;
		}

		if ($array == 'filemounts')
		{
			// Check the subfolders (filemounts)
			foreach ($this->filemounts as $folder)
			{
				if (preg_match('/^' . preg_quote($folder, '/') . '(\/|$)/i', $field[0]))
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Restore the original numeric file mounts (see #5083)
	 */
	public function save()
	{
		$filemounts = $this->filemounts;

		if (!empty($this->arrFilemountIds))
		{
			$this->arrData['filemounts'] = $this->arrFilemountIds;
		}

		parent::save();
		$this->filemounts = $filemounts;
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

		Config::set('showHelp', $this->showHelp);
		Config::set('useRTE', $this->useRTE);
		Config::set('useCE', $this->useCE);
		Config::set('thumbnails', $this->thumbnails);
		Config::set('backendTheme', $this->backendTheme);

		// Inherit permissions
		$always = array('alexf');
		$depends = array('modules', 'themes', 'elements', 'fields', 'pagemounts', 'alpty', 'filemounts', 'fop', 'forms', 'formp', 'imageSizes', 'amg');

		// HOOK: Take custom permissions
		if (!empty($GLOBALS['TL_PERMISSIONS']) && \is_array($GLOBALS['TL_PERMISSIONS']))
		{
			$depends = array_merge($depends, $GLOBALS['TL_PERMISSIONS']);
		}

		// Overwrite user permissions if only group permissions shall be inherited
		if ($this->inherit == 'group')
		{
			foreach ($depends as $field)
			{
				$this->$field = array();
			}
		}

		// Merge permissions
		$inherit = \in_array($this->inherit, array('group', 'extend')) ? array_merge($always, $depends) : $always;
		$time = Date::floorToMinute();

		foreach ($this->groups as $id)
		{
			$objGroup = $this->Database->prepare("SELECT * FROM tl_user_group WHERE id=? AND disable=0 AND (start='' OR start<='$time') AND (stop='' OR stop>'$time')")
									   ->limit(1)
									   ->execute($id);

			if ($objGroup->numRows > 0)
			{
				foreach ($inherit as $field)
				{
					$value = StringUtil::deserialize($objGroup->$field, true);

					// The new page/file picker can return integers instead of arrays, so use empty() instead of is_array() and StringUtil::deserialize(true) here
					if (!empty($value))
					{
						$this->$field = array_merge((\is_array($this->$field) ? $this->$field : ($this->$field ? array($this->$field) : array())), $value);
						$this->$field = array_unique($this->$field);
					}
				}
			}
		}

		// Make sure pagemounts and filemounts are set!
		if (!\is_array($this->pagemounts))
		{
			$this->pagemounts = array();
		}
		else
		{
			$this->pagemounts = array_filter($this->pagemounts);
		}

		if (!\is_array($this->filemounts))
		{
			$this->filemounts = array();
		}
		else
		{
			$this->filemounts = array_filter($this->filemounts);
		}

		// Store the numeric file mounts
		$this->arrFilemountIds = $this->filemounts;

		// Convert the file mounts into paths
		if (!$this->isAdmin && !empty($this->filemounts))
		{
			$objFiles = FilesModel::findMultipleByUuids($this->filemounts);

			if ($objFiles !== null)
			{
				$this->filemounts = $objFiles->fetchEach('path');
			}
		}

		// Hide the "admin" field if the user is not an admin (see #184)
		if (!$this->isAdmin && ($index = array_search('tl_user::admin', $this->alexf)) !== false)
		{
			unset($this->alexf[$index]);
		}
	}

	/**
	 * Generate the navigation menu and return it as array
	 *
	 * @param boolean $blnShowAll
	 *
	 * @return array
	 */
	public function navigation($blnShowAll=false)
	{
		$arrModules = array();
		$arrStatus = System::getContainer()->get('session')->getBag('contao_backend')->get('backend_modules');
		$strRefererId = System::getContainer()->get('request_stack')->getCurrentRequest()->attributes->get('_contao_referer_id');
		$router = System::getContainer()->get('router');
		$security = System::getContainer()->get('security.helper');

		foreach ($GLOBALS['BE_MOD'] as $strGroupName=>$arrGroupModules)
		{
			if (!empty($arrGroupModules) && ($strGroupName == 'system' || $this->hasAccess(array_keys($arrGroupModules), 'modules')))
			{
				$arrModules[$strGroupName]['class'] = 'group-' . $strGroupName . ' node-expanded';
				$arrModules[$strGroupName]['title'] = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['collapseNode']);
				$arrModules[$strGroupName]['label'] = ($label = \is_array($GLOBALS['TL_LANG']['MOD'][$strGroupName] ?? null) ? ($GLOBALS['TL_LANG']['MOD'][$strGroupName][0] ?? null) : ($GLOBALS['TL_LANG']['MOD'][$strGroupName] ?? null)) ? $label : $strGroupName;
				$arrModules[$strGroupName]['href'] = $router->generate('contao_backend', array('do'=>Input::get('do'), 'mtg'=>$strGroupName, 'ref'=>$strRefererId));
				$arrModules[$strGroupName]['ajaxUrl'] = $router->generate('contao_backend');

				foreach ($arrGroupModules as $strModuleName=>$arrModuleConfig)
				{
					// Check access
					$blnAccess = (isset($arrModuleConfig['disablePermissionChecks']) && $arrModuleConfig['disablePermissionChecks'] === true) || $security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, $strModuleName);
					$blnHide = isset($arrModuleConfig['hideInNavigation']) && $arrModuleConfig['hideInNavigation'] === true;

					if ($blnAccess && !$blnHide)
					{
						$arrModules[$strGroupName]['modules'][$strModuleName] = $arrModuleConfig;
						$arrModules[$strGroupName]['modules'][$strModuleName]['title'] = StringUtil::specialchars($GLOBALS['TL_LANG']['MOD'][$strModuleName][1] ?? '');
						$arrModules[$strGroupName]['modules'][$strModuleName]['label'] = ($label = \is_array($GLOBALS['TL_LANG']['MOD'][$strModuleName] ?? null) ? ($GLOBALS['TL_LANG']['MOD'][$strModuleName][0] ?? null) : ($GLOBALS['TL_LANG']['MOD'][$strModuleName] ?? null)) ? $label : $strModuleName;
						$arrModules[$strGroupName]['modules'][$strModuleName]['class'] = 'navigation ' . $strModuleName;
						$arrModules[$strGroupName]['modules'][$strModuleName]['href'] = $router->generate('contao_backend', array('do'=>$strModuleName, 'ref'=>$strRefererId));
						$arrModules[$strGroupName]['modules'][$strModuleName]['isActive'] = false;
					}
				}
			}
		}

		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['getUserNavigation']) && \is_array($GLOBALS['TL_HOOKS']['getUserNavigation']))
		{
			foreach ($GLOBALS['TL_HOOKS']['getUserNavigation'] as $callback)
			{
				$this->import($callback[0]);
				$arrModules = $this->{$callback[0]}->{$callback[1]}($arrModules, true);
			}
		}

		foreach ($arrModules as $strGroupName => $arrGroupModules)
		{
			$arrModules[$strGroupName]['isClosed'] = false;

			// Do not show the modules if the group is closed
			if (!$blnShowAll && isset($arrStatus[$strGroupName]) && $arrStatus[$strGroupName] < 1)
			{
				$arrModules[$strGroupName]['class'] = str_replace('node-expanded', '', $arrModules[$strGroupName]['class']) . ' node-collapsed';
				$arrModules[$strGroupName]['title'] = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['expandNode']);
				$arrModules[$strGroupName]['isClosed'] = true;
			}

			if (isset($arrGroupModules['modules']) && \is_array($arrGroupModules['modules']))
			{
				foreach ($arrGroupModules['modules'] as $strModuleName => $arrModuleConfig)
				{
					// Mark the active module and its group
					if (Input::get('do') == $strModuleName)
					{
						$arrModules[$strGroupName]['class'] .= ' trail';
						$arrModules[$strGroupName]['modules'][$strModuleName]['isActive'] = true;
					}
				}
			}
		}

		return $arrModules;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRoles(): array
	{
		if ($this->isAdmin)
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
}
