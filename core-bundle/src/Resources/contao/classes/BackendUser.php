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
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
	 * Edit page flag
	 * @deprecated Deprecated since Contao 4.13. Use Symfony security and ContaoCorePermissions::USER_CAN_EDIT_PAGE.
	 */
	const CAN_EDIT_PAGE = 1;

	/**
	 * Edit page hierarchy flag
	 * @deprecated Deprecated since Contao 4.13. Use Symfony security and ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY.
	 */
	const CAN_EDIT_PAGE_HIERARCHY = 2;

	/**
	 * Delete page flag
	 * @deprecated Deprecated since Contao 4.13. Use Symfony security and ContaoCorePermissions::USER_CAN_DELETE_PAGE.
	 */
	const CAN_DELETE_PAGE = 3;

	/**
	 * Edit articles flag
	 * @deprecated Deprecated since Contao 4.13. Use Symfony security and ContaoCorePermissions::USER_CAN_EDIT_ARTICLES.
	 */
	const CAN_EDIT_ARTICLES = 4;

	/**
	 * Edit article hierarchy flag
	 * @deprecated Deprecated since Contao 4.13. Use Symfony security and ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY.
	 */
	const CAN_EDIT_ARTICLE_HIERARCHY = 5;

	/**
	 * Delete articles flag
	 * @deprecated Deprecated since Contao 4.13. Use Symfony security and ContaoCorePermissions::USER_CAN_DELETE_ARTICLES.
	 */
	const CAN_DELETE_ARTICLES = 6;

	/**
	 * Symfony Security session key
	 * @deprecated Deprecated since Contao 4.8, to be removed in Contao 5.0
	 */
	const SECURITY_SESSION_KEY = '_security_contao_backend';

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
	 * Redirect to the login screen if authentication fails
	 *
	 * @return boolean True if the user could be authenticated
	 *
	 * @deprecated Deprecated since Contao 4.5, to be removed in Contao 5.0.
	 *             Use Symfony security instead.
	 */
	public function authenticate()
	{
		trigger_deprecation('contao/core-bundle', '4.5', 'Using "Contao\BackendUser::authenticate()" has been deprecated and will no longer work in Contao 5.0. Use Symfony security instead.');

		// Do not redirect if authentication is successful
		if (System::getContainer()->get('contao.security.token_checker')->hasBackendUser())
		{
			return true;
		}

		if (!$request = System::getContainer()->get('request_stack')->getCurrentRequest())
		{
			return false;
		}

		$route = $request->attributes->get('_route');

		if ($route == 'contao_backend_login')
		{
			return false;
		}

		$url = System::getContainer()->get('router')->generate('contao_backend_login', array('redirect' => $request->getUri()), UrlGeneratorInterface::ABSOLUTE_URL);

		throw new RedirectResponseException(System::getContainer()->get('uri_signer')->sign($url));
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
		trigger_deprecation('contao/core-bundle', '4.5', 'Using "Contao\BackendUser::login()" has been deprecated and will no longer work in Contao 5.0. Use Symfony security instead.');

		return System::getContainer()->get('contao.security.token_checker')->hasBackendUser();
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
		elseif ($array == 'pagemounts')
		{
			// Check the mounted pages
			foreach ($this->pagemounts as $page)
			{
				$childIds = $this->Database->getChildRecords($page, 'tl_page');

				if (!empty($childIds) && array_intersect($field, $childIds))
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Return true if the current user is allowed to do the current operation on the current page
	 *
	 * @param integer $int
	 * @param array   $row
	 *
	 * @return boolean
	 *
	 * @deprecated Deprecated since Contao 4.13, to be removed in Contao 5.0.
	 *             Use the "security.helper" service with the ContaoCorePermissions
	 *             constants instead.
	 */
	public function isAllowed($int, $row)
	{
		trigger_deprecation('contao/core-bundle', '4.13', 'Using "Contao\BackendUser::isAllowed()" has been deprecated and will no longer work in Contao 5. Use the "security.helper" service with the ContaoCorePermissions constants instead.');

		if ($this->isAdmin)
		{
			return true;
		}

		// Inherit CHMOD settings
		if (!$row['includeChmod'])
		{
			$pid = $row['pid'];

			$row['chmod'] = false;
			$row['cuser'] = false;
			$row['cgroup'] = false;

			$objParentPage = PageModel::findById($pid);

			while ($objParentPage !== null && $row['chmod'] === false && $pid > 0)
			{
				$pid = $objParentPage->pid;

				$row['chmod'] = $objParentPage->includeChmod ? $objParentPage->chmod : false;
				$row['cuser'] = $objParentPage->includeChmod ? $objParentPage->cuser : false;
				$row['cgroup'] = $objParentPage->includeChmod ? $objParentPage->cgroup : false;

				$objParentPage = PageModel::findById($pid);
			}

			// Set default values
			if ($row['chmod'] === false)
			{
				$row['chmod'] = Config::get('defaultChmod');
			}

			if ($row['cuser'] === false)
			{
				$row['cuser'] = (int) Config::get('defaultUser');
			}

			if ($row['cgroup'] === false)
			{
				$row['cgroup'] = (int) Config::get('defaultGroup');
			}
		}

		// Set permissions
		$chmod = StringUtil::deserialize($row['chmod']);
		$chmod = \is_array($chmod) ? $chmod : array($chmod);
		$permission = array('w' . $int);

		if (\in_array($row['cgroup'], $this->groups))
		{
			$permission[] = 'g' . $int;
		}

		if ($row['cuser'] == $this->id)
		{
			$permission[] = 'u' . $int;
		}

		return \count(array_intersect($permission, $chmod)) > 0;
	}

	/**
	 * Return true if there is at least one allowed excluded field
	 *
	 * @param string $table
	 *
	 * @return boolean
	 *
	 * @deprecated Deprecated since Contao 4.13, to be removed in Contao 5.0.
	 *             Use the "security.helper" service with the ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE
	 *             constant instead.
	 */
	public function canEditFieldsOf($table)
	{
		trigger_deprecation('contao/core-bundle', '4.13', 'Using "Contao\BackendUser::canEditFieldsOfTable()" has been deprecated and will no longer work in Contao 5. Use the "security.helper" service with the ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE constant instead.');

		if ($this->isAdmin)
		{
			return true;
		}

		return \count(preg_grep('/^' . preg_quote($table, '/') . '::/', $this->alexf)) > 0;
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
		Config::set('fullscreen', $this->fullscreen);

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

		foreach ((array) $this->groups as $id)
		{
			$objGroup = $this->Database->prepare("SELECT * FROM tl_user_group WHERE id=? AND disable!='1' AND (start='' OR start<='$time') AND (stop='' OR stop>'$time')")
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
				$arrModules[$strGroupName]['icon'] = 'modPlus.gif'; // backwards compatibility with e.g. EasyThemes

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
	public function getRoles()
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

	/**
	 * @deprecated Deprecated since Contao 4.9, to be removed in Contao 5.0.
	 */
	public function serialize()
	{
		$data = $this->__serialize();
		$data['parent'] = serialize($data['parent']);

		return serialize($data);
	}

	public function __serialize(): array
	{
		return array('admin' => $this->admin, 'amg' => $this->amg, 'parent' => parent::__serialize());
	}

	/**
	 * @deprecated Deprecated since Contao 4.9 to be removed in Contao 5.0.
	 */
	public function unserialize($data)
	{
		$unserialized = unserialize($data, array('allowed_classes'=>false));

		if (!isset($unserialized['parent']))
		{
			return;
		}

		$unserialized['parent'] = unserialize($unserialized['parent'], array('allowed_classes'=>false));

		$this->__unserialize($unserialized);
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
	public function isEqualTo(UserInterface $user)
	{
		if (!$user instanceof self)
		{
			return false;
		}

		if ((bool) $this->admin !== (bool) $user->admin)
		{
			return false;
		}

		return parent::isEqualTo($user);
	}
}

class_alias(BackendUser::class, 'BackendUser');
