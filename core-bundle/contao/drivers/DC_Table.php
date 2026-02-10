<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\DataContainer\ClipboardManager;
use Contao\CoreBundle\DataContainer\DataContainerOperationsBuilder;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\NotFoundException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Pagination\PaginationConfig;
use Contao\CoreBundle\Picker\PickerInterface;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\CoreBundle\Util\ArrayTree;
use Contao\Database\Statement;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\String\UnicodeString;

/**
 * Provide methods to modify the database.
 *
 * @property integer $id
 * @property string  $parentTable
 * @property array   $childTable
 * @property boolean $createNewVersion
 */
class DC_Table extends DataContainer implements ListableDataContainerInterface, EditableDataContainerInterface
{
	/**
	 * Name of the parent table
	 * @var string
	 */
	protected $ptable;

	/**
	 * Names of the child tables
	 * @var array
	 */
	protected $ctable;

	/**
	 * Limit (database query)
	 * @var string
	 */
	protected $limit;

	/**
	 * Total (database query)
	 * @var string
	 */
	protected $total;

	/**
	 * First sorting field
	 * @var string
	 */
	protected $firstOrderBy;

	/**
	 * Order by (database query)
	 * @var array
	 */
	protected $orderBy = array();

	/**
	 * Fields of a new or duplicated record
	 * @var array
	 */
	protected $set = array();

	/**
	 * IDs of all records that are currently displayed
	 * @var array
	 */
	protected $current = array();

	/**
	 * Show the current table as tree
	 * @var boolean
	 */
	protected $treeView = false;

	/**
	 * The current back end module
	 * @var array
	 */
	protected $arrModule = array();

	/**
	 * Data of fields to be submitted
	 * @var array
	 */
	protected $arrSubmit = array();

	/**
	 * Cache for the getParentRecords() calls for root trail calculation.
	 * @var array<string, array<<int, array<int>>
	 */
	private $parentPagesCache = array();

	/**
	 * Initialize the object
	 *
	 * @param string $strTable
	 * @param array  $arrModule
	 */
	public function __construct($strTable, $arrModule=array())
	{
		parent::__construct();

		$container = System::getContainer();
		$objSession = $container->get('request_stack')->getSession();
		$request = $container->get('request_stack')->getCurrentRequest();

		// Check the request token (see #4007)
		if ((!$request || $request->isMethodSafe()) && !\in_array(Input::get('act'), array(null, 'edit', 'show', 'select'), true) && (Input::get('rt') === null || !$container->get('contao.csrf.token_manager')->isTokenValid(new CsrfToken($container->getParameter('contao.csrf_token_name'), Input::get('rt')))))
		{
			$objSession->set('INVALID_TOKEN_URL', Environment::get('requestUri'));
			$this->redirect($container->get('router')->generate('contao_backend_confirm'));
		}

		$this->intId = Input::get('id');
		$this->strTable = $strTable;

		// Clear the clipboard
		if (Input::get('clipboard') !== null)
		{
			System::getContainer()->get('contao.data_container.clipboard_manager')->clearAll();
			$this->redirect($this->getReferer());
		}

		// Check whether the table is defined
		if (!$strTable || !isset($GLOBALS['TL_DCA'][$strTable]))
		{
			$container->get('monolog.logger.contao.error')->error('Could not load the data container configuration for "' . $strTable . '"');
			trigger_error('Could not load the data container configuration', E_USER_ERROR);
		}

		$ids = null;
		$arrClipboard = $objSession->get('CLIPBOARD');

		// Set IDs
		if (Input::post('FORM_SUBMIT') == 'tl_select' || (\in_array(Input::post('FORM_SUBMIT'), array($strTable, $strTable . '_all')) && \in_array(Input::get('act'), array('editAll', 'overrideAll'))))
		{
			$ids = Input::post('IDS');

			if (!empty($ids) && \is_array($ids))
			{
				$session = $objSession->all();
				$session['CURRENT']['IDS'] = $ids;
				$objSession->replace($session);
			}
		}

		// Redirect
		if (Input::post('FORM_SUBMIT') == 'tl_select')
		{
			if (empty($ids) || !\is_array($ids))
			{
				$this->reload();
			}

			if (Input::post('edit') !== null)
			{
				$this->redirect(Backend::addToUrl('act=editAll'));
			}
			elseif (Input::post('delete') !== null)
			{
				$this->redirect(Backend::addToUrl('act=deleteAll'));
			}
			elseif (Input::post('override') !== null)
			{
				$this->redirect(Backend::addToUrl('act=overrideAll'));
			}
			elseif (Input::post('cut') !== null || Input::post('copy') !== null || Input::post('copyMultiple') !== null)
			{
				$security = $container->get('security.helper');

				$mode = Input::post('cut') !== null ? 'cutAll' : 'copyAll';
				$ids = array_filter($ids, fn ($id) => $security->isGranted(...$this->getClipboardPermission($mode, (int) $id)));

				if (empty($ids))
				{
					System::getContainer()->get('contao.data_container.clipboard_manager')->clear($this->strTable);
				}
				else
				{
					System::getContainer()->get('contao.data_container.clipboard_manager')->setIds($strTable, $ids, $mode, Input::post('copyMultiple') !== null);

					// Support copyAll in the list view (see #7499)
					if ((Input::post('copy') !== null || Input::post('copyMultiple') !== null) && ($GLOBALS['TL_DCA'][$strTable]['list']['sorting']['mode'] ?? 0) < self::MODE_PARENT)
					{
						$this->redirect(Backend::addToUrl('act=copyAll'));
					}
				}

				$this->redirect($this->getReferer());
			}
		}

		$this->ptable = $GLOBALS['TL_DCA'][$this->strTable]['config']['ptable'] = $this->findPtable();
		$this->ctable = $GLOBALS['TL_DCA'][$this->strTable]['config']['ctable'] ?? null;
		$this->treeView = \in_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null, array(self::MODE_TREE, self::MODE_TREE_EXTENDED));
		$this->arrModule = $arrModule;
		$this->intCurrentPid = $this->findCurrentPid();

		// Call onload_callback (e.g. to check permissions)
		if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['onload_callback'] ?? null))
		{
			foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['onload_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					System::importStatic($callback[0])->{$callback[1]}($this);
				}
				elseif (\is_callable($callback))
				{
					$callback($this);
				}
			}
		}

		$this->initRoots();

		if (!empty($arrClipboard[$this->strTable]) && $arrClipboard[$this->strTable]['mode'] != 'create')
		{
			if (\is_array($arrClipboard[$this->strTable]['id']))
			{
				$arrIds = $arrClipboard[$this->strTable]['id'];
				$arrFilteredIds = array_filter($arrIds, fn ($id) => $this->getCurrentRecord($id) !== null);

				if ($arrFilteredIds !== $arrIds)
				{
					$arrClipboard[$this->strTable]['id'] = $arrFilteredIds;

					if (empty($arrFilteredIds))
					{
						unset($arrClipboard[$this->strTable]);
					}

					$objSession->set('CLIPBOARD', $arrClipboard);
				}
			}
			elseif ($this->getCurrentRecord($arrClipboard[$this->strTable]['id']) === null)
			{
				unset($arrClipboard[$this->strTable]);
				$objSession->set('CLIPBOARD', $arrClipboard);
			}
		}
	}

	public function getCurrentRecord(int|string|null $id = null, string|null $table = null): array|null
	{
		if (!$currentRecord = parent::getCurrentRecord($id, $table))
		{
			return $currentRecord;
		}

		return System::getContainer()->get('contao.data_container.virtual_fields_handler')->expandFields($currentRecord, $table ?: $this->strTable);
	}

	/**
	 * Returns the database record merged with the submitted changes.
	 *
	 * @return array<string, mixed>|null
	 * @throws AccessDeniedException     If the user has no read permission on the current record
	 */
	public function getActiveRecord(): array|null
	{
		$currentRecord = $this->getCurrentRecord();

		if (null === $currentRecord)
		{
			return null;
		}

		return array_merge($currentRecord, array_map(
			static function ($value) {
				/** @see Statement::query() */
				if (\is_string($value) || \is_bool($value) || \is_float($value) || \is_int($value) || $value === null)
				{
					return $value;
				}

				return serialize($value);
			},
			$this->arrSubmit
		));
	}

	/**
	 * With this method, the ID of the current (parent) record can be
	 * determined stateless based on the current request only.
	 *
	 * In older versions, Contao stored the ID of the current (parent) record
	 * in the user session as "CURRENT_ID" to make it known on subsequent
	 * requests. This was unreliable and caused several issues, like for
	 * example if the user used multiple browser tabs at the same time.
	 */
	private function findCurrentPid(): int|null
	{
		if (!$this->ptable)
		{
			return null;
		}

		$id = ((int) Input::get('id')) ?: null;
		$pid = ((int) Input::get('pid')) ?: null;
		$act = Input::get('act');
		$mode = Input::get('mode');

		// For these actions the id parameter refers to the parent record
		if (($act === 'paste' && $mode === 'create') || \in_array($act, array(null, 'select', 'editAll', 'overrideAll', 'deleteAll'), true))
		{
			return $id;
		}

		// For these actions the pid parameter refers to the insert position
		if (\in_array($act, array('create', 'cut', 'copy', 'cutAll', 'copyAll'), true))
		{
			// Mode “paste into”
			if ($mode == self::PASTE_INTO)
			{
				return $pid;
			}

			// Mode “paste after”
			$id = $pid;
		}

		if (!$id)
		{
			return null;
		}

		$currentRecord = $this->getCurrentRecord($id);

		if (!empty($currentRecord['pid']))
		{
			return (int) $currentRecord['pid'];
		}

		return null;
	}

	private function findPtable(): string|null
	{
		if ($GLOBALS['TL_DCA'][$this->strTable]['config']['dynamicPtable'] ?? null)
		{
			$act = Input::get('act');
			$mode = Input::get('mode');

			// For these actions the id parameter refers to the parent record (or the old record for copy and cut), so they need to be excluded
			if ($this->intId && ($act !== 'paste' || $mode !== 'create') && !\in_array($act, array(null, 'copy', 'cut', 'create', 'select', 'copyAll', 'cutAll', 'editAll', 'overrideAll', 'deleteAll'), true))
			{
				$currentRecord = $this->getCurrentRecord($this->intId);

				if (!empty($currentRecord['ptable']))
				{
					return $currentRecord['ptable'];
				}
			}

			// Use the ptable query parameter if it points to itself (nested elements case)
			if (Input::get('ptable') === $this->strTable && \in_array($this->strTable, $GLOBALS['TL_DCA'][$this->strTable]['config']['ctable'] ?? array(), true))
			{
				return $this->strTable;
			}
		}

		return $GLOBALS['TL_DCA'][$this->strTable]['config']['ptable'] ?? null;
	}

	/**
	 * @internal
	 */
	protected function render(string $component, array $parameters): string
	{
		$defaultParameters = array(
			'table' => $this->table,
			'pid' => $this->intCurrentPid,
			'sorting_mode' => (int) ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? 0),
			'display_grid' => (int) ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['renderAsGrid'] ?? false),
			'limit_height' => BackendUser::getInstance()->doNotCollapse ? false : (int) ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['limitHeight'] ?? 0),
			'is_upload_form' => $this->blnUploadable,
			'form_onsubmit' => $this->onsubmit,
			'error' => $this->noReload,
			'as_select' => Input::get('act') === 'select',
			'as_picker' => (bool) $this->strPickerFieldType,
		);

		if ($defaultParameters['as_picker'])
		{
			$defaultParameters['picker'] = array(
				'value' => (new HtmlAttributes($this->getPickerValueAttribute()))['data-picker-value'] ?? '',
				'type' => $this->strPickerFieldType,
			);
		}

		return System::getContainer()
			->get('twig')
			->render(
				"@Contao/backend/data_container/table/$component.html.twig",
				array(...$defaultParameters, ...$parameters),
			)
		;
	}

	/**
	 * Return an object property
	 *
	 * @param string $strKey
	 *
	 * @return mixed
	 */
	public function __get($strKey)
	{
		switch ($strKey)
		{
			case 'parentTable':
				return $this->ptable;

			case 'childTable':
				return $this->ctable;

			case 'rootIds':
				return $this->root;
		}

		return parent::__get($strKey);
	}

	/**
	 * List all records of a particular table
	 *
	 * @return string
	 */
	public function showAll()
	{
		$this->limit = '';

		$this->reviseTable();

		// Add to clipboard
		if (Input::get('act') == 'paste')
		{
			$this->denyAccessUnlessGranted(...$this->getClipboardPermission(Input::get('mode'), (int) Input::get('id')));

			$children = Input::get('children');

			// Backwards compatibility
			if (Input::get('childs') !== null)
			{
				trigger_deprecation('contao/core-bundle', '5.3', 'Using the "childs" query parameter is deprecated and will no longer work in Contao 6. Use the "children" parameter instead.');
				$children = Input::get('childs');
			}

			System::getContainer()->get('contao.data_container.clipboard_manager')->set($this->strTable, Input::get('id'), $children, Input::get('mode'));

			if ($this->currentPid)
			{
				$this->redirect(Backend::addToUrl('id=' . $this->currentPid, false, array('act', 'mode')));
			}
			else
			{
				$this->redirect(Backend::addToUrl('', false, array('act', 'mode', 'id')));
			}
		}

		// Custom filter
		if (!empty($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['filter']) && \is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['filter']))
		{
			foreach ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['filter'] as $filter)
			{
				if (\is_string($filter))
				{
					$this->procedure[] = $filter;
				}
				else
				{
					$this->procedure[] = $filter[0];
					$this->values[] = $filter[1];
				}
			}
		}

		// Render view
		if ($this->treeView)
		{
			$parameters = array(
				'panel' => $this->panel(),
				'view' => $this->treeView(),
			);
		}
		else
		{
			if ($this->ptable && Input::get('table') && Database::getInstance()->fieldExists('pid', $this->strTable))
			{
				$this->procedure[] = 'pid=?';
				$this->values[] = $this->currentPid;
			}

			$parameters = array(
				'panel' => $this->panel(),
				'view' => ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_PARENT ? $this->parentView() : $this->listView(),
			);
		}

		return $this->render('show_all', $parameters);
	}

	/**
	 * Return all non-excluded fields of a record as HTML table
	 *
	 * @return string
	 */
	public function show()
	{
		$currentRecord = $this->getCurrentRecord();

		if (null === $currentRecord)
		{
			return '';
		}

		$data = array();
		$row = $currentRecord;

		// Get all fields
		$fields = array_keys($row);
		$allowedFields = array('id', 'pid', 'sorting', 'tstamp');

		if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'] ?? null))
		{
			$allowedFields = array_unique(array(...$allowedFields, ...array_keys($GLOBALS['TL_DCA'][$this->strTable]['fields'])));
		}

		// Use the field order of the DCA file
		$fields = array_intersect($allowedFields, $fields);
		$db = Database::getInstance();
		$virtualTargets = DcaExtractor::getInstance($this->strTable)->getVirtualTargets();

		// Show all allowed fields
		foreach ($fields as $i)
		{
			if (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['inputType'] ?? null) == 'password' || ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['eval']['doNotShow'] ?? null) || ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['eval']['hideInput'] ?? null) || !\in_array($i, $allowedFields) || \in_array($i, $virtualTargets))
			{
				continue;
			}

			$value = StringUtil::deserialize($row[$i]);

			// Get the field value
			if (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['foreignKey']))
			{
				$temp = array();
				$chunks = explode('.', $GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['foreignKey'], 2);

				foreach ((array) $value as $v)
				{
					$objKey = $db
						->prepare("SELECT " . Database::quoteIdentifier($chunks[1]) . " AS value FROM " . $chunks[0] . " WHERE id=?")
						->limit(1)
						->execute($v);

					if ($objKey->numRows)
					{
						$temp[] = $objKey->value;
					}
				}

				$row[$i] = implode(', ', $temp);
			}
			elseif (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['inputType'] ?? null) == 'fileTree')
			{
				if (\is_array($value))
				{
					foreach ($value as $kk=>$vv)
					{
						if ($objFile = FilesModel::findByUuid($vv))
						{
							$value[$kk] = $objFile->path . ' (' . StringUtil::binToUuid($vv) . ')';
						}
						else
						{
							$value[$kk] = '';
						}
					}

					$row[$i] = implode(', ', $value);
				}
				elseif ($objFile = FilesModel::findByUuid($value))
				{
					$row[$i] = $objFile->path . ' (' . StringUtil::binToUuid($value) . ')';
				}
				else
				{
					$row[$i] = '';
				}
			}
			elseif (\is_array($value))
			{
				if (isset($value['value'], $value['unit']) && \count($value) == 2)
				{
					$row[$i] = trim($value['value'] . ', ' . $value['unit']);
				}
				else
				{
					foreach ($value as $kk=>$vv)
					{
						if (\is_array($vv))
						{
							$vals = array_values($vv);
							$value[$kk] = array_shift($vals) . ' (' . implode(', ', array_filter($vals)) . ')';
						}
					}

					if (ArrayUtil::isAssoc($value))
					{
						foreach ($value as $kk=>$vv)
						{
							$value[$kk] = $kk . ': ' . $vv;
						}
					}

					$row[$i] = implode(', ', $value);
				}
			}
			elseif (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['eval']['rgxp'] ?? null) == 'date')
			{
				$row[$i] = $value ? Date::parse(Config::get('dateFormat'), $value) : '-';
			}
			elseif (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['eval']['rgxp'] ?? null) == 'time')
			{
				$row[$i] = $value ? Date::parse(Config::get('timeFormat'), $value) : '-';
			}
			elseif ($i == 'tstamp' || ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['eval']['rgxp'] ?? null) == 'datim' || \in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['flag'] ?? null, array(self::SORT_DAY_ASC, self::SORT_DAY_DESC, self::SORT_DAY_BOTH, self::SORT_MONTH_ASC, self::SORT_MONTH_DESC, self::SORT_MONTH_BOTH, self::SORT_YEAR_ASC, self::SORT_YEAR_DESC, self::SORT_YEAR_BOTH)))
			{
				$row[$i] = $value ? Date::parse(Config::get('datimFormat'), $value) : '-';
			}
			elseif (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['eval']['isBoolean'] ?? null) || (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['inputType'] ?? null) == 'checkbox' && !($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['eval']['multiple'] ?? null)))
			{
				$row[$i] = $value ? $GLOBALS['TL_LANG']['MSC']['yes'] : $GLOBALS['TL_LANG']['MSC']['no'];
			}
			elseif (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['eval']['rgxp'] ?? null) == 'email')
			{
				$row[$i] = Idna::decodeEmail($value);
			}
			elseif (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['inputType'] ?? null) == 'textarea' && (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['eval']['allowHtml'] ?? null) || ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['eval']['preserveTags'] ?? null)))
			{
				$row[$i] = StringUtil::specialchars($value);
			}
			elseif (\is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['reference'] ?? null))
			{
				$row[$i] = isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['reference'][$row[$i]]) ? (\is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['reference'][$row[$i]]) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['reference'][$row[$i]][0] : $GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['reference'][$row[$i]]) : $row[$i];
			}
			elseif (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['eval']['isAssociative'] ?? null) || ArrayUtil::isAssoc($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['options'] ?? null))
			{
				$row[$i] = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['options'][$row[$i]] ?? null;
			}
			else
			{
				$row[$i] = $value;
			}

			$label = null;

			// Label
			if (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['label']))
			{
				$label = \is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['label']) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['label'][0] : $GLOBALS['TL_DCA'][$this->strTable]['fields'][$i]['label'];
			}
			elseif (isset($GLOBALS['TL_LANG']['MSC'][$i]))
			{
				$label = \is_array($GLOBALS['TL_LANG']['MSC'][$i]) ? $GLOBALS['TL_LANG']['MSC'][$i][0] : $GLOBALS['TL_LANG']['MSC'][$i];
			}

			if (!$label)
			{
				$label = '-';
			}

			$label .= ' <small>' . $i . '</small>';

			$data[$this->strTable][0][$label] = $row[$i];
		}

		// Call onshow_callback
		if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['onshow_callback'] ?? null))
		{
			foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['onshow_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					$data = System::importStatic($callback[0])->{$callback[1]}($data, $currentRecord, $this);
				}
				elseif (\is_callable($callback))
				{
					$data = $callback($data, $currentRecord, $this);
				}
			}
		}

		return $this->render('show', array('data' => $data));
	}

	/**
	 * Insert a new row into a database table
	 *
	 * @param array $set
	 *
	 * @throws AccessDeniedException
	 */
	public function create($set=array())
	{
		if ($GLOBALS['TL_DCA'][$this->strTable]['config']['notCreatable'] ?? null)
		{
			throw new AccessDeniedException('Table "' . $this->strTable . '" is not creatable.');
		}

		$db = Database::getInstance();
		$databaseFields = $db->getFieldNames($this->strTable);
		$dcaExtract = DcaExtractor::getInstance($this->strTable);
		$virtualFields = $dcaExtract->getVirtualFields();

		// Get all default values for the new entry
		foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'] as $k=>$v)
		{
			// Use array_key_exists here (see #5252)
			if (\array_key_exists('default', $v) && (\in_array($k, $databaseFields, true) || \array_key_exists($k, $virtualFields)))
			{
				$default = $v['default'];

				if ($default instanceof \Closure)
				{
					$default = $default($this);
				}

				$this->set[$k] = \is_array($default) ? serialize($default) : $default;
			}
		}

		// Set passed values
		if (!empty($set) && \is_array($set))
		{
			$this->set = array_merge($this->set, $set);
		}

		// Get the new position
		$this->getNewPosition('new', Input::get('pid'), Input::get('mode') == self::PASTE_INTO);

		// Dynamically set the parent table
		if ($GLOBALS['TL_DCA'][$this->strTable]['config']['dynamicPtable'] ?? null)
		{
			$this->set['ptable'] = $this->ptable;
		}

		$container = System::getContainer();
		$objSession = $container->get('request_stack')->getSession();

		// Empty the clipboard
		$container->get('contao.data_container.clipboard_manager')->clear($this->strTable);

		$this->set['tstamp'] = 0;

		$this->denyAccessUnlessGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new CreateAction($this->strTable, $this->set));

		// Insert the record if the table is not closed and switch to edit mode
		if (!($GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] ?? null))
		{
			// Combine virtual fields
			$this->set = $container->get('contao.data_container.virtual_fields_handler')->combineFields($this->set, $this->strTable);

			// Ensure JSON data type for virtual field targets when saving to database
			$arrTypes = array_map(static fn (string $k) => \in_array($k, $dcaExtract->getVirtualTargets()) ? Types::JSON : null, array_keys($this->set));

			$objInsertStmt = $db
				->prepare("INSERT INTO " . $this->strTable . " %s")
				->set($this->set)
				->query('', array_values($this->set), array_values($arrTypes));

			if ($objInsertStmt->affectedRows)
			{
				$s2e = ($GLOBALS['TL_DCA'][$this->strTable]['config']['switchToEdit'] ?? null) ? '&s2e=1' : '';
				$insertID = $objInsertStmt->insertId;

				$objSessionBag = $objSession->getBag('contao_backend');

				// Save new record in the session
				$new_records = $objSessionBag->get('new_records');
				$new_records[$this->strTable][] = $insertID;
				$objSessionBag->set('new_records', $new_records);

				// Call the oncreate_callback
				if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['oncreate_callback'] ?? null))
				{
					foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['oncreate_callback'] as $callback)
					{
						if (\is_array($callback))
						{
							System::importStatic($callback[0])->{$callback[1]}($this->strTable, $insertID, $this->set, $this);
						}
						elseif (\is_callable($callback))
						{
							$callback($this->strTable, $insertID, $this->set, $this);
						}
					}
				}

				$container->get('monolog.logger.contao.general')->info('A new entry "' . $this->strTable . '.id=' . $insertID . '" has been created' . $this->getParentEntries($this->strTable, $insertID));

				$this->redirect($this->switchToEdit($insertID) . $s2e);
			}
		}

		$this->redirect($this->getReferer());
	}

	/**
	 * Assign a new position to an existing record
	 *
	 * @param boolean $blnDoNotRedirect
	 *
	 * @throws AccessDeniedException
	 * @throws UnprocessableEntityHttpException
	 */
	public function cut($blnDoNotRedirect=false)
	{
		if ($GLOBALS['TL_DCA'][$this->strTable]['config']['notSortable'] ?? null)
		{
			throw new AccessDeniedException('Table "' . $this->strTable . '" is not sortable.');
		}

		$cr = array();

		// ID and PID are mandatory (PID can be 0!)
		if (!$this->intId || Input::get('pid') === null)
		{
			throw new NotFoundException('Cannot load record "' . $this->strTable . '.id=' . $this->intId . '".');
		}

		try
		{
			// Load current record before calculating new position etc. in case the user does not have read access
			$currentRecord = $this->getCurrentRecord();
		}
		catch (AccessDeniedException)
		{
			$currentRecord = null;
		}

		if ($currentRecord === null)
		{
			if (!$blnDoNotRedirect)
			{
				$this->redirect($this->getReferer());
			}

			return;
		}

		$db = Database::getInstance();

		// Get the new position
		$this->getNewPosition('cut', Input::get('pid'), Input::get('mode') == self::PASTE_INTO);

		// Avoid circular references when there is no parent table or the table references itself
		if ((!$this->ptable || $this->ptable == $this->strTable) && $db->fieldExists('pid', $this->strTable))
		{
			$cr = $db->getChildRecords($this->intId, $this->strTable);
			$cr[] = $this->intId;
		}

		// Empty clipboard
		System::getContainer()->get('contao.data_container.clipboard_manager')->clear($this->strTable);

		// Check for circular references
		if (\in_array($this->set['pid'], $cr))
		{
			throw new UnprocessableEntityHttpException('Attempt to relate record ' . $this->intId . ' of table "' . $this->strTable . '" to its child record ' . Input::get('pid') . ' (circular reference).');
		}

		// Dynamically set the parent table of tl_content
		if ($GLOBALS['TL_DCA'][$this->strTable]['config']['dynamicPtable'] ?? null)
		{
			$this->set['ptable'] = $this->ptable;
		}

		$this->denyAccessUnlessGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new UpdateAction($this->strTable, $currentRecord, $this->set));

		$db
			->prepare("UPDATE " . $this->strTable . " %s WHERE id=?")
			->set($this->set)
			->execute($this->intId);

		// Call the oncut_callback
		if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['oncut_callback'] ?? null))
		{
			foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['oncut_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					System::importStatic($callback[0])->{$callback[1]}($this);
				}
				elseif (\is_callable($callback))
				{
					$callback($this);
				}
			}
		}

		if (!$blnDoNotRedirect)
		{
			$this->redirect($this->getReferer());
		}
	}

	/**
	 * Move all selected records
	 *
	 * @throws AccessDeniedException
	 */
	public function cutAll()
	{
		if ($GLOBALS['TL_DCA'][$this->strTable]['config']['notSortable'] ?? null)
		{
			throw new AccessDeniedException('Table "' . $this->strTable . '" is not sortable.');
		}

		foreach (System::getContainer()->get('contao.data_container.clipboard_manager')->getIds($this->strTable) as $id)
		{
			$this->intId = $id;

			try
			{
				$this->cut(true);
			}
			catch (AccessDeniedException)
			{
				continue;
			}

			Input::setGet('pid', $id);
			Input::setGet('mode', DataContainer::PASTE_AFTER);
		}

		$this->redirect($this->getReferer());
	}

	/**
	 * Duplicate a particular record of the current table
	 *
	 * @param boolean $blnDoNotRedirect
	 *
	 * @return integer|boolean
	 *
	 * @throws AccessDeniedException
	 */
	public function copy($blnDoNotRedirect=false)
	{
		if ($GLOBALS['TL_DCA'][$this->strTable]['config']['notCopyable'] ?? null)
		{
			throw new AccessDeniedException('Table "' . $this->strTable . '" is not copyable.');
		}

		if (!$this->intId)
		{
			throw new NotFoundException('Cannot load record "' . $this->strTable . '.id=' . $this->intId . '".');
		}

		$objSession = System::getContainer()->get('request_stack')->getSession();
		$objSessionBag = $objSession->getBag('contao_backend');

		$currentRecord = $this->getCurrentRecord();

		// Copy the values if the record contains data
		if (null !== $currentRecord)
		{
			foreach ($currentRecord as $k=>$v)
			{
				if (\array_key_exists($k, $GLOBALS['TL_DCA'][$this->strTable]['fields'] ?? array()))
				{
					// Never copy passwords
					if (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['inputType'] ?? null) == 'password')
					{
						$v = Widget::getEmptyValueByFieldType($GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['sql'] ?? array());
					}

					// Empty unique fields or add a unique identifier in copyAll mode
					elseif ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['eval']['unique'] ?? null)
					{
						$v = (Input::get('act') == 'copyAll' && !($GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['eval']['doNotCopy'] ?? null)) ? $v . '-' . substr(md5(uniqid(mt_rand(), true)), 0, 8) : Widget::getEmptyValueByFieldType($GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['sql'] ?? array());
					}

					// Reset doNotCopy and fallback fields to their default value
					elseif (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['eval']['doNotCopy'] ?? null) || ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['eval']['fallback'] ?? null))
					{
						$v = Widget::getEmptyValueByFieldType($GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['sql'] ?? array());

						// Use array_key_exists to allow NULL (see #5252)
						if (\array_key_exists('default', $GLOBALS['TL_DCA'][$this->strTable]['fields'][$k] ?? array()))
						{
							$default = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$k]['default'];

							if ($default instanceof \Closure)
							{
								$default = $default($this);
							}

							$v = \is_array($default) ? serialize($default) : $default;
						}

						// Cast boolean to integers (see #6473)
						if (\is_bool($v))
						{
							$v = (int) $v;
						}
					}

					$this->set[$k] = $v;
				}
			}
		}

		// Get the new position
		$this->getNewPosition('copy', Input::get('pid'), Input::get('mode') == self::PASTE_INTO);

		// Dynamically set the parent table of tl_content
		if ($GLOBALS['TL_DCA'][$this->strTable]['config']['dynamicPtable'] ?? null)
		{
			$this->set['ptable'] = $this->ptable;
		}

		// Empty clipboard
		System::getContainer()->get('contao.data_container.clipboard_manager')->clearIfNotKeep($this->strTable);

		// Insert the record if the table is not closed and switch to edit mode
		if (!($GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] ?? null))
		{
			$this->set['tstamp'] = $blnDoNotRedirect ? time() : 0;

			// Mark the new record with "copy of" (see #586)
			if (isset($GLOBALS['TL_DCA'][$this->strTable]['config']['markAsCopy']))
			{
				$strKey = $GLOBALS['TL_DCA'][$this->strTable]['config']['markAsCopy'];

				if (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$strKey]['inputType'] ?? null) == 'inputUnit')
				{
					$value = StringUtil::deserialize($this->set[$strKey]);

					if (!empty($value['value']))
					{
						$value['value'] = $this->markAsCopy($GLOBALS['TL_LANG']['MSC']['copyOf'], $value['value']);
						$this->set[$strKey] = serialize($value);
					}
				}
				elseif (!empty($this->set[$strKey]))
				{
					$this->set[$strKey] = $this->markAsCopy($GLOBALS['TL_LANG']['MSC']['copyOf'], $this->set[$strKey]);
				}
			}

			// Remove the ID field from the data array
			unset($this->set['id']);

			$this->denyAccessUnlessGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new CreateAction($this->strTable, $this->set));

			$objInsertStmt = null;

			// Combine virtual fields
			$this->set = System::getContainer()->get('contao.data_container.virtual_fields_handler')->combineFields($this->set, $this->strTable);

			// Ensure JSON data type for virtual field targets when saving to database
			$arrTypes = array_map(fn (string $k) => \in_array($k, DcaExtractor::getInstance($this->strTable)->getVirtualTargets()) ? Types::JSON : null, array_keys($this->set));

			try
			{
				$objInsertStmt = Database::getInstance()
					->prepare("INSERT INTO " . $this->strTable . " %s")
					->set($this->set)
					->query('', array_values($this->set), array_values($arrTypes));
			}
			catch (UniqueConstraintViolationException $e)
			{
				Message::addError(\sprintf(System::getContainer()->get('translator')->trans('ERR.copyUnique', array(), 'contao_default'), (int) $currentRecord['id']));
			}

			if ($objInsertStmt?->affectedRows)
			{
				$insertID = $objInsertStmt->insertId;

				// Save the new record in the session
				$new_records = $objSessionBag->get('new_records');
				$new_records[$this->strTable][] = $insertID;
				$objSessionBag->set('new_records', $new_records);

				// Duplicate the records of the child table
				$this->copyChildren($this->strTable, $insertID, $this->intId, $insertID);

				// Call the oncopy_callback after all new records have been created
				if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['oncopy_callback'] ?? null))
				{
					foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['oncopy_callback'] as $callback)
					{
						if (\is_array($callback))
						{
							System::importStatic($callback[0])->{$callback[1]}($insertID, $this);
						}
						elseif (\is_callable($callback))
						{
							$callback($insertID, $this);
						}
					}
				}

				System::getContainer()->get('monolog.logger.contao.general')->info('A new entry "' . $this->strTable . '.id=' . $insertID . '" has been created by duplicating record "' . $this->strTable . '.id=' . $this->intId . '"' . $this->getParentEntries($this->strTable, $insertID));

				// Switch to edit mode
				if (!$blnDoNotRedirect)
				{
					// User cannot edit record, redirect back to the list view (see #6674)
					if (($GLOBALS['TL_DCA'][$this->strTable]['config']['notEditable'] ?? null) || !System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new UpdateAction($this->strTable, $currentRecord)))
					{
						$this->redirect($this->getReferer());
					}
					else
					{
						$this->redirect($this->switchToEdit($insertID));
					}
				}

				return $insertID;
			}
		}

		if (!$blnDoNotRedirect)
		{
			$this->redirect($this->getReferer());
		}

		return false;
	}

	/**
	 * @deprecated Deprecated since Contao 5.3, to be removed in Contao 6;
	 *             use copyChildren() instead.
	 */
	protected function copyChilds($table, $insertID, $id, $parentId)
	{
		trigger_deprecation('contao/core-bundle', '5.3', 'Using "%s()" is deprecated and will no longer work in Contao 6. Use "copyChildren()" instead.', __METHOD__);
		$this->copyChildren($table, $insertID, $id, $parentId);
	}

	/**
	 * Duplicate all child records of a duplicated record
	 *
	 * @param string  $table
	 * @param integer $insertID
	 * @param integer $id
	 * @param integer $parentId
	 */
	protected function copyChildren($table, $insertID, $id, $parentId)
	{
		$time = time();
		$copy = array();
		$cctable = array();
		$ctable = $GLOBALS['TL_DCA'][$table]['config']['ctable'] ?? array();
		$db = Database::getInstance();
		$children = Input::get('children');

		// Backwards compatibility
		if (Input::get('childs') !== null)
		{
			trigger_deprecation('contao/core-bundle', '5.3', 'Using the "childs" query parameter is deprecated and will no longer work in Contao 6. Use the "children" parameter instead.');
			$children = Input::get('childs');
		}

		if (!($GLOBALS['TL_DCA'][$table]['config']['ptable'] ?? null) && $children && $db->fieldExists('pid', $table) && $db->fieldExists('sorting', $table))
		{
			$ctable[] = $table;
		}

		if (empty($ctable) || !\is_array($ctable))
		{
			return;
		}

		// Walk through each child table
		foreach ($ctable as $v)
		{
			$this->loadDataContainer($v);
			$cctable[$v] = $GLOBALS['TL_DCA'][$v]['config']['ctable'] ?? null;

			if (!($GLOBALS['TL_DCA'][$v]['config']['doNotCopyRecords'] ?? null))
			{
				// Consider the dynamic parent table (see #4867)
				if ($GLOBALS['TL_DCA'][$v]['config']['dynamicPtable'] ?? null)
				{
					$objCTable = $db
						->prepare("SELECT * FROM $v WHERE pid=? AND ptable=?" . ($db->fieldExists('sorting', $v) ? " ORDER BY sorting, id" : ""))
						->execute($id, $table);
				}
				else
				{
					$objCTable = $db
						->prepare("SELECT * FROM $v WHERE pid=?" . ($db->fieldExists('sorting', $v) ? " ORDER BY sorting, id" : ""))
						->execute($id);
				}

				while ($objCTable->next())
				{
					// Exclude the duplicated record itself
					if ($v == $table && $objCTable->id == $parentId)
					{
						continue;
					}

					foreach ($objCTable->row() as $kk=>$vv)
					{
						if ($kk == 'id')
						{
							continue;
						}

						// Never copy passwords
						if (($GLOBALS['TL_DCA'][$v]['fields'][$kk]['inputType'] ?? null) == 'password')
						{
							$vv = Widget::getEmptyValueByFieldType($GLOBALS['TL_DCA'][$v]['fields'][$kk]['sql'] ?? array());
						}

						// Empty unique fields or add a unique identifier in copyAll mode
						elseif ($GLOBALS['TL_DCA'][$v]['fields'][$kk]['eval']['unique'] ?? null)
						{
							$vv = (Input::get('act') == 'copyAll' && !$GLOBALS['TL_DCA'][$v]['fields'][$kk]['eval']['doNotCopy']) ? $vv . '-' . substr(md5(uniqid(mt_rand(), true)), 0, 8) : Widget::getEmptyValueByFieldType($GLOBALS['TL_DCA'][$v]['fields'][$kk]['sql'] ?? array());
						}

						// Reset doNotCopy and fallback fields to their default value
						elseif (($GLOBALS['TL_DCA'][$v]['fields'][$kk]['eval']['doNotCopy'] ?? null) || ($GLOBALS['TL_DCA'][$v]['fields'][$kk]['eval']['fallback'] ?? null))
						{
							$vv = Widget::getEmptyValueByFieldType($GLOBALS['TL_DCA'][$v]['fields'][$kk]['sql'] ?? array());

							// Use array_key_exists to allow NULL (see #5252)
							if (\array_key_exists('default', $GLOBALS['TL_DCA'][$v]['fields'][$kk] ?? array()))
							{
								$default = $GLOBALS['TL_DCA'][$v]['fields'][$kk]['default'];

								if ($default instanceof \Closure)
								{
									$default = $default($this);
								}

								$vv = \is_array($default) ? serialize($default) : $default;
							}
						}

						$copy[$v][$objCTable->id][$kk] = $vv;
					}

					$copy[$v][$objCTable->id]['pid'] = $insertID;
					$copy[$v][$objCTable->id]['tstamp'] = $time;
				}
			}
		}

		// Duplicate the child records
		foreach ($copy as $k=>$v)
		{
			if (!empty($v))
			{
				foreach ($v as $kk=>$vv)
				{
					$objInsertStmt = $db->prepare("INSERT INTO " . $k . " %s")->set($vv)->execute();

					if ($objInsertStmt->affectedRows)
					{
						$insertID = $objInsertStmt->insertId;

						if ($kk != $parentId && (!empty($cctable[$k]) || ($GLOBALS['TL_DCA'][$k]['list']['sorting']['mode'] ?? null) == self::MODE_TREE))
						{
							$this->copyChildren($k, $insertID, $kk, $parentId);
						}

						if (\is_array($GLOBALS['TL_DCA'][$k]['config']['oncopy_callback'] ?? null))
						{
							foreach ($GLOBALS['TL_DCA'][$k]['config']['oncopy_callback'] as $callback)
							{
								$dc = (new \ReflectionClass(self::class))->newInstanceWithoutConstructor();
								$dc->strTable = $k;
								$dc->id = $kk;

								if (\is_array($callback))
								{
									System::importStatic($callback[0])->{$callback[1]}($insertID, $dc);
								}
								elseif (\is_callable($callback))
								{
									$callback($insertID, $dc);
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Move all selected records
	 *
	 * @throws AccessDeniedException
	 */
	public function copyAll()
	{
		if ($GLOBALS['TL_DCA'][$this->strTable]['config']['notCopyable'] ?? null)
		{
			throw new AccessDeniedException('Table "' . $this->strTable . '" is not copyable.');
		}

		foreach (System::getContainer()->get('contao.data_container.clipboard_manager')->getIds($this->strTable) as $id)
		{
			$this->intId = $id;

			try
			{
				$id = $this->copy(true);
			}
			catch (AccessDeniedException)
			{
				continue;
			}

			if ($id)
			{
				Input::setGet('pid', $id);
				Input::setGet('mode', 1);
			}
		}

		$this->redirect($this->getReferer());
	}

	/**
	 * Calculate the new position of a moved or inserted record
	 *
	 * @param string  $mode
	 * @param integer $pid
	 * @param boolean $insertInto
	 */
	protected function getNewPosition($mode, $pid=null, $insertInto=false)
	{
		$db = Database::getInstance();

		// If there is pid and sorting
		if ($db->fieldExists('pid', $this->strTable) && $db->fieldExists('sorting', $this->strTable))
		{
			// PID is not set - only valid for duplicated records, as they get the same parent ID as the original record!
			if ($pid === null && $this->intId && $mode == 'copy')
			{
				$pid = $this->intId;
			}

			// PID is set (insert after or into the parent record)
			if (is_numeric($pid))
			{
				$newPID = null;
				$newSorting = null;
				$filter = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_PARENT ? $this->strTable . '_' . $this->intCurrentPid : $this->strTable;

				$objSession = System::getContainer()->get('request_stack')->getSession();
				$session = $objSession->all();

				// Consider the pagination menu when inserting at the top (see #7895)
				if ($insertInto && isset($session['filter'][$filter]['limit']))
				{
					$limit = substr($session['filter'][$filter]['limit'], 0, strpos($session['filter'][$filter]['limit'], ','));

					if ($limit > 0)
					{
						$objInsertAfter = $db
							->prepare("SELECT id FROM " . $this->strTable . " WHERE " . ($pid ? 'pid=?' : '(pid=? OR pid IS NULL)') . " ORDER BY sorting, id")
							->limit(1, $limit - 1)
							->execute($pid);

						if ($objInsertAfter->numRows)
						{
							$insertInto = false;
							$pid = $objInsertAfter->id;
						}
					}
				}

				// Insert the current record at the beginning when inserting into the parent record
				if ($insertInto)
				{
					$newPID = $pid;

					$objSorting = $db
						->prepare("SELECT MIN(sorting) AS sorting FROM " . $this->strTable . " WHERE " . ($pid ? 'pid=?' : '(pid=? OR pid IS NULL)'))
						->execute($pid);

					// Select sorting value of the first record
					if ($objSorting->numRows)
					{
						$curSorting = $objSorting->sorting;

						// Resort if the new sorting value is not an integer or smaller than 1
						if (($curSorting % 2) != 0 || $curSorting < 1)
						{
							$objNewSorting = $db
								->prepare("SELECT id FROM " . $this->strTable . " WHERE " . ($pid ? 'pid=?' : '(pid=? OR pid IS NULL)') . " ORDER BY sorting, id")
								->execute($pid);

							$count = 2;
							$newSorting = 128;

							while ($objNewSorting->next())
							{
								$db
									->prepare("UPDATE " . $this->strTable . " SET sorting=? WHERE id=?")
									->limit(1)
									->execute($count++ * 128, $objNewSorting->id);
							}
						}

						// Else new sorting = (current sorting / 2)
						else
						{
							$newSorting = $curSorting / 2;
						}
					}

					// Else new sorting = 128
					else
					{
						$newSorting = 128;
					}
				}

				// Else insert the current record after the parent record
				elseif ($pid > 0)
				{
					$objSorting = $db
						->prepare("SELECT pid, sorting FROM " . $this->strTable . " WHERE id=?")
						->limit(1)
						->execute($pid);

					// Set parent ID of the current record as new parent ID
					if ($objSorting->numRows)
					{
						$newPID = $objSorting->pid;
						$curSorting = $objSorting->sorting;

						// Do not proceed without a parent ID
						if (is_numeric($newPID) || $newPID === null)
						{
							$objNextSorting = $db
								->prepare("SELECT MIN(sorting) AS sorting FROM " . $this->strTable . " WHERE " . ($newPID ? 'pid=?' : '(pid=? OR pid IS NULL)') . " AND sorting>?")
								->execute($newPID, $curSorting);

							// Select sorting value of the next record
							if ($objNextSorting->sorting !== null)
							{
								$nxtSorting = $objNextSorting->sorting;

								// Resort if the new sorting value is no integer or bigger than a MySQL integer
								if ((($curSorting + $nxtSorting) % 2) != 0 || $nxtSorting >= 4294967295)
								{
									$count = 1;

									$objNewSorting = $db
										->prepare("SELECT id, sorting FROM " . $this->strTable . " WHERE " . ($newPID ? 'pid=?' : '(pid=? OR pid IS NULL)') . " ORDER BY sorting, id")
										->execute($newPID);

									while ($objNewSorting->next())
									{
										$db
											->prepare("UPDATE " . $this->strTable . " SET sorting=? WHERE id=?")
											->execute($count++ * 128, $objNewSorting->id);

										if ($objNewSorting->sorting == $curSorting)
										{
											$newSorting = $count++ * 128;
										}
									}
								}

								// Else new sorting = (current sorting + next sorting) / 2
								else
								{
									$newSorting = ($curSorting + $nxtSorting) / 2;
								}
							}

							// Else new sorting = (current sorting + 128)
							else
							{
								$newSorting = $curSorting + 128;
							}
						}
					}

					// Use the given parent ID as parent ID
					else
					{
						$newPID = $pid;
						$newSorting = 128;
					}
				}

				if (!$newPID)
				{
					$newPID = Widget::getEmptyValueByFieldType($GLOBALS['TL_DCA'][$this->strTable]['fields']['pid']['sql'] ?? array()) === null ? null : 0;
				}

				// Set new sorting and new parent ID
				$this->set['pid'] = $newPID;
				$this->set['sorting'] = (int) $newSorting;
			}
		}

		// If there is only pid
		elseif ($db->fieldExists('pid', $this->strTable))
		{
			// PID is not set - only valid for duplicated records, as they get the same parent ID as the original record!
			if ($pid === null && $this->intId && $mode == 'copy')
			{
				$pid = $this->intId;
			}

			// PID is set (insert after or into the parent record)
			if (is_numeric($pid))
			{
				// Insert the current record into the parent record
				if ($insertInto)
				{
					$this->set['pid'] = $pid;
				}

				// Else insert the current record after the parent record
				elseif ($pid > 0)
				{
					$objParentRecord = $db
						->prepare("SELECT pid FROM " . $this->strTable . " WHERE id=?")
						->limit(1)
						->execute($pid);

					if ($objParentRecord->numRows)
					{
						$this->set['pid'] = $objParentRecord->pid;
					}
				}
			}
		}

		// If there is only sorting
		elseif ($db->fieldExists('sorting', $this->strTable))
		{
			// ID is set (insert after the current record)
			if ($this->intId)
			{
				try
				{
					$currentRecord = $this->getCurrentRecord();
				}
				catch (AccessDeniedException)
				{
					$currentRecord = null;
				}

				// Select current record
				if (null !== $currentRecord)
				{
					$newSorting = null;
					$curSorting = $currentRecord['sorting'] ?? null;

					$objNextSorting = $db
						->prepare("SELECT MIN(sorting) AS sorting FROM " . $this->strTable . " WHERE sorting>?")
						->execute($curSorting);

					// Select sorting value of the next record
					if ($objNextSorting->numRows)
					{
						$nxtSorting = $objNextSorting->sorting;

						// Resort if the new sorting value is no integer or bigger than a MySQL integer field
						if ((($curSorting + $nxtSorting) % 2) != 0 || $nxtSorting >= 4294967295)
						{
							$count = 1;

							$objNewSorting = $db->execute("SELECT id, sorting FROM " . $this->strTable . " ORDER BY sorting, id");

							while ($objNewSorting->next())
							{
								$db
									->prepare("UPDATE " . $this->strTable . " SET sorting=? WHERE id=?")
									->execute($count++ * 128, $objNewSorting->id);

								if ($objNewSorting->sorting == $curSorting)
								{
									$newSorting = $count++ * 128;
								}
							}
						}

						// Else new sorting = (current sorting + next sorting) / 2
						else
						{
							$newSorting = ($curSorting + $nxtSorting) / 2;
						}
					}

					// Else new sorting = (current sorting + 128)
					else
					{
						$newSorting = $curSorting + 128;
					}

					// Set new sorting
					$this->set['sorting'] = (int) $newSorting;

					return;
				}
			}

			// ID is not set or not found (insert at the end)
			$objNextSorting = $db->execute("SELECT MAX(sorting) AS sorting FROM " . $this->strTable);
			$this->set['sorting'] = (int) $objNextSorting->sorting + 128;
		}
	}

	/**
	 * Delete a record of the current table and save it to tl_undo
	 *
	 * @param boolean $blnDoNotRedirect
	 *
	 * @throws AccessDeniedException
	 */
	public function delete($blnDoNotRedirect=false)
	{
		if ($GLOBALS['TL_DCA'][$this->strTable]['config']['notDeletable'] ?? null)
		{
			throw new AccessDeniedException('Table "' . $this->strTable . '" is not deletable.');
		}

		$currentRecord = $this->getCurrentRecord();

		if (null === $currentRecord)
		{
			if ($blnDoNotRedirect)
			{
				return;
			}

			throw new NotFoundException('Cannot load record "' . $this->strTable . '.id=' . $this->intId . '".');
		}

		$this->denyAccessUnlessGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new DeleteAction($this->strTable, $currentRecord));

		$db = Database::getInstance();
		$delete = array();

		// Do not save records from tl_undo itself
		if ($this->strTable == 'tl_undo')
		{
			$db
				->prepare("DELETE FROM " . $this->strTable . " WHERE id=?")
				->limit(1)
				->execute($this->intId);

			$this->redirect($this->getReferer());
		}

		// If there is a PID field but no parent table
		if (!$this->ptable && self::MODE_TREE === ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) && $db->fieldExists('pid', $this->strTable))
		{
			$delete[$this->strTable] = $db->getChildRecords($this->intId, $this->strTable);
			array_unshift($delete[$this->strTable], $this->intId);
		}
		else
		{
			$delete[$this->strTable] = array($this->intId);
		}

		// Delete all child records if there is a child table
		if (!empty($this->ctable))
		{
			foreach ($delete[$this->strTable] as $id)
			{
				$this->deleteChildren($this->strTable, $id, $delete);
			}
		}

		$affected = 0;
		$data = array();

		// Save each record of each table
		foreach ($delete as $table=>$fields)
		{
			foreach ($fields as $k=>$v)
			{
				$objSave = $db
					->prepare("SELECT * FROM " . $table . " WHERE id=?")
					->limit(1)
					->execute($v);

				if ($objSave->numRows)
				{
					$data[$table][$k] = $objSave->row();

					// Store the active record (backwards compatibility)
					if ($table == $this->strTable && $v == $this->intId)
					{
						$this->objActiveRecord = $objSave;
					}
				}

				$affected++;
			}
		}

		// There is no actual data to be deleted (see #5336)
		if (empty($data))
		{
			if (!$blnDoNotRedirect)
			{
				$this->redirect($this->getReferer());
			}

			return;
		}

		$objUndoStmt = $db
			->prepare("INSERT INTO tl_undo (pid, tstamp, fromTable, query, affectedRows, data) VALUES (?, ?, ?, ?, ?, ?)")
			->execute(BackendUser::getInstance()->id, time(), $this->strTable, 'DELETE FROM ' . $this->strTable . ' WHERE id=' . $this->intId, $affected, serialize($data));

		// Delete the records
		if ($objUndoStmt->affectedRows)
		{
			$undoId = $objUndoStmt->insertId;

			// Call ondelete_callback
			if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['ondelete_callback'] ?? null))
			{
				foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['ondelete_callback'] as $callback)
				{
					if (\is_array($callback))
					{
						System::importStatic($callback[0])->{$callback[1]}($this, $undoId);
					}
					elseif (\is_callable($callback))
					{
						$callback($this, $undoId);
					}
				}
			}

			// Invalidate cache tags (no need to invalidate the parent)
			$this->invalidateCacheTags();

			// Delete the records in the reverse order to start from child records and avoid foreign key errors
			foreach (array_reverse($delete) as $table=>$fields)
			{
				foreach ($fields as $v)
				{
					$db
						->prepare("DELETE FROM " . $table . " WHERE id=?")
						->limit(1)
						->execute($v);
				}
			}

			// Add a log entry unless we are deleting from tl_log itself
			if ($this->strTable != 'tl_log')
			{
				System::getContainer()->get('monolog.logger.contao.general')->info('DELETE FROM ' . $this->strTable . ' WHERE id=' . $data[$this->strTable][0]['id']);
			}
		}

		if (!$blnDoNotRedirect)
		{
			$this->redirect($this->getReferer());
		}
	}

	/**
	 * Delete all selected records
	 *
	 * @throws AccessDeniedException
	 */
	public function deleteAll()
	{
		if ($GLOBALS['TL_DCA'][$this->strTable]['config']['notDeletable'] ?? null)
		{
			throw new AccessDeniedException('Table "' . $this->strTable . '" is not deletable.');
		}

		$objSession = System::getContainer()->get('request_stack')->getSession();
		$session = $objSession->all();

		$ids = $session['CURRENT']['IDS'] ?? array();

		if (\is_array($ids) && array_filter($ids))
		{
			foreach ($ids as $id)
			{
				$this->intId = $id;

				try
				{
					$this->delete(true);
				}
				catch (AccessDeniedException)
				{
					continue;
				}
			}
		}

		$this->redirect($this->getReferer());
	}

	/**
	 * @deprecated Deprecated since Contao 5.3, to be removed in Contao 6;
	 *             use deleteChildren() instead.
	 */
	protected function deleteChilds($table, $id, &$delete)
	{
		trigger_deprecation('contao/core-bundle', '5.3', 'Using "%s()" is deprecated and will no longer work in Contao 6. Use "deleteChildren()" instead.', __METHOD__);
		$this->deleteChildren($table, $id, $delete);
	}

	/**
	 * Recursively get all related table names and records
	 *
	 * @param string  $table
	 * @param integer $id
	 * @param array   $delete
	 */
	public function deleteChildren($table, $id, &$delete)
	{
		$ctable = $GLOBALS['TL_DCA'][$table]['config']['ctable'] ?? array();

		if (empty($ctable) || !\is_array($ctable))
		{
			return;
		}

		$db = Database::getInstance();

		// Walk through each child table
		foreach ($ctable as $v)
		{
			$this->loadDataContainer($v);

			// Consider the dynamic parent table (see #4867)
			if ($GLOBALS['TL_DCA'][$v]['config']['dynamicPtable'] ?? null)
			{
				$objDelete = $db
					->prepare("SELECT id FROM $v WHERE pid=? AND ptable=?")
					->execute($id, $table);
			}
			else
			{
				$objDelete = $db
					->prepare("SELECT id FROM $v WHERE pid=?")
					->execute($id);
			}

			if ($objDelete->numRows && !($GLOBALS['TL_DCA'][$v]['config']['doNotDeleteRecords'] ?? null))
			{
				foreach ($objDelete->fetchEach('id') as $childId)
				{
					$delete[$v][] = $childId;
					$this->deleteChildren($v, $childId, $delete);
				}
			}
		}
	}

	/**
	 * Restore one or more deleted records
	 */
	public function undo()
	{
		$currentRecord = $this->getCurrentRecord();

		// Check whether there is a record
		if (null === $currentRecord)
		{
			throw new NotFoundException('Cannot load record "' . $this->strTable . '.id=' . $this->intId . '".');
		}

		$error = false;
		$query = $currentRecord['query'] ?? null;
		$data = StringUtil::deserialize($currentRecord['data'] ?? null);

		if (!\is_array($data))
		{
			$this->redirect($this->getReferer());
		}

		$db = Database::getInstance();
		$arrFields = array();

		// Restore the data
		foreach ($data as $table=>$fields)
		{
			$this->loadDataContainer($table);

			// Get the currently available fields
			if (!isset($arrFields[$table]))
			{
				$arrFields[$table] = array_flip($db->getFieldNames($table));
			}

			foreach ($fields as $row)
			{
				// Unset fields that no longer exist in the database
				$row = array_intersect_key($row, $arrFields[$table]);

				// Re-insert the data
				$objInsertStmt = $db
					->prepare("INSERT INTO " . $table . " %s")
					->set($row)
					->execute();

				// Do not delete record from tl_undo if there is an error
				if ($objInsertStmt->affectedRows < 1)
				{
					$error = true;
				}

				// Trigger the undo_callback
				if (\is_array($GLOBALS['TL_DCA'][$table]['config']['onundo_callback'] ?? null))
				{
					foreach ($GLOBALS['TL_DCA'][$table]['config']['onundo_callback'] as $callback)
					{
						if (\is_array($callback))
						{
							System::importStatic($callback[0])->{$callback[1]}($table, $row, $this);
						}
						elseif (\is_callable($callback))
						{
							$callback($table, $row, $this);
						}
					}
				}
			}
		}

		// Add log entry and delete record from tl_undo if there was no error
		if (!$error)
		{
			System::getContainer()->get('monolog.logger.contao.general')->info('Undone ' . $query);

			$db
				->prepare("DELETE FROM " . $this->strTable . " WHERE id=?")
				->limit(1)
				->execute($this->intId);
		}

		$this->invalidateCacheTags();

		$this->redirect($this->getReferer());
	}

	/**
	 * Auto-generate a form to edit the current database record
	 *
	 * @param integer $intId
	 * @param integer $ajaxId
	 *
	 * @return string
	 *
	 * @throws AccessDeniedException
	 */
	public function edit($intId=null, $ajaxId=null)
	{
		if ($GLOBALS['TL_DCA'][$this->strTable]['config']['notEditable'] ?? null)
		{
			throw new AccessDeniedException('Table "' . $this->strTable . '" is not editable.');
		}

		if ($intId)
		{
			$this->intId = $intId;
		}

		// Get the current record
		$currentRecord = $this->getCurrentRecord();

		// Redirect if there is no record with the given ID
		if (null === $currentRecord)
		{
			throw new NotFoundException('Cannot load record "' . $this->strTable . '.id=' . $this->intId . '".');
		}

		$this->denyAccessUnlessGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new UpdateAction($this->strTable, $currentRecord));

		// Store the active record (backwards compatibility)
		$this->objActiveRecord = (object) $currentRecord;

		$this->values[] = $this->intId;
		$this->procedure[] = 'id=?';
		$this->arrSubmit = array();
		$this->blnCreateNewVersion = false;
		$objVersions = new Versions($this->strTable, $this->intId);

		if (!($GLOBALS['TL_DCA'][$this->strTable]['config']['hideVersionMenu'] ?? null))
		{
			// Compare versions
			if (Input::get('versions'))
			{
				$objVersions->compare();
			}

			// Restore a version
			if (Input::post('FORM_SUBMIT') == 'tl_version' && Input::post('version'))
			{
				$objVersions->restore(Input::post('version'));

				$this->invalidateCacheTags();

				$this->reload();
			}
		}

		$objVersions->initialize();
		$intLatestVersion = $objVersions->getLatestVersion();

		$this->strPalette = $this->getPalette();
		$boxes = System::getContainer()->get('contao.data_container.palette_builder')->getBoxes($this->strPalette, $this->strTable);

		$parameters = array(
			'version_number' => $intLatestVersion,
			'boxes' => array(),
		);

		if (!empty($boxes))
		{
			foreach ($boxes as $box)
			{
				$thisId = '';

				// Generate the box's tree of widget groups. When edit() was called in
				// AJAX mode, we return early and only render the matching subtree.
				$widgetGroupTree = new ArrayTree();
				$ajaxWidgetGroupTree = null;

				foreach ($box['fields'] as $vv)
				{
					if ($vv == '[EOF]')
					{
						if ($ajaxWidgetGroupTree && $ajaxId == $thisId)
						{
							return $this->render('edit/ajax', array(
								'version_number' => $intLatestVersion,
								'widget_groups_tree' => $ajaxWidgetGroupTree,
							));
						}

						$widgetGroupTree->up();

						continue;
					}

					if (preg_match('/^\[.*]$/', $vv))
					{
						$thisId = 'sub_' . substr($vv, 1, -1);

						$widgetGroupTree->enterChildNode($thisId);

						if ($ajaxId == $thisId && Environment::get('isAjaxRequest'))
						{
							// We only need to output the current subtree in AJAX mode, so we store a reference here
							$ajaxWidgetGroupTree = $widgetGroupTree->current();
						}

						continue;
					}

					$this->strField = $vv;
					$this->strInputName = $vv;
					$this->varValue = $currentRecord[$vv] ?? null;

					// Convert CSV fields (see #2890)
					if (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['multiple'] ?? null) && isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['csv']))
					{
						$this->varValue = StringUtil::trimsplit($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['csv'], $this->varValue);
					}

					// Call load_callback
					if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['load_callback'] ?? null))
					{
						foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['load_callback'] as $callback)
						{
							if (\is_array($callback))
							{
								$this->varValue = System::importStatic($callback[0])->{$callback[1]}($this->varValue, $this);
							}
							elseif (\is_callable($callback))
							{
								$this->varValue = $callback($this->varValue, $this);
							}
						}
					}

					// Re-set the current value
					$this->objActiveRecord->{$this->strField} = $this->varValue;

					$widgetGroupTree->addContentNode($this->row());
				}

				$parameters['boxes'][] = array(
					'id' => $box['key'],
					'class' => $box['class'],
					'label' => $GLOBALS['TL_LANG'][$this->strTable][$box['key']] ??  $box['key'],
					'widget_groups_tree' => $widgetGroupTree,
				);
			}

			$this->submit();
		}

		// Reload the page to prevent _POST variables from being sent twice
		if (!$this->noReload && Input::post('FORM_SUBMIT') == $this->strTable)
		{
			// Show a warning if the record has been saved by another user (see #8412)
			if ($intLatestVersion !== null && Input::post('VERSION_NUMBER') !== null && $intLatestVersion > Input::post('VERSION_NUMBER'))
			{
				$objTemplate = new BackendTemplate('be_conflict');
				$objTemplate->language = $GLOBALS['TL_LANGUAGE'];
				$objTemplate->title = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['versionConflict']);
				$objTemplate->host = Backend::getDecodedHostname();
				$objTemplate->theme = Backend::getTheme();
				$objTemplate->charset = System::getContainer()->getParameter('kernel.charset');
				$objTemplate->h1 = $GLOBALS['TL_LANG']['MSC']['versionConflict'];
				$objTemplate->explain1 = \sprintf($GLOBALS['TL_LANG']['MSC']['versionConflict1'], $intLatestVersion, Input::post('VERSION_NUMBER'));
				$objTemplate->explain2 = \sprintf($GLOBALS['TL_LANG']['MSC']['versionConflict2'], $intLatestVersion + 1, $intLatestVersion);
				$objTemplate->diff = $objVersions->compare(true);
				$objTemplate->href = Environment::get('requestUri');
				$objTemplate->button = $GLOBALS['TL_LANG']['MSC']['continue'];

				// We need to set the status code to either 4xx or 5xx in order for Turbo to render this response.
				$response = $objTemplate->getResponse();
				$response->setStatusCode(Response::HTTP_CONFLICT);

				throw new ResponseException($response);
			}

			// Redirect
			if (Input::post('saveNclose') !== null)
			{
				Message::reset();

				$this->redirect($this->getReferer());
			}
			elseif (Input::post('saveNedit') !== null)
			{
				Message::reset();

				$this->redirect($this->addToUrl($GLOBALS['TL_DCA'][$this->strTable]['list']['operations']['children']['href'] ?? '', false, array('s2e', 'act', 'mode', 'pid')));
			}
			elseif (Input::post('saveNcreate') !== null)
			{
				Message::reset();

				$strUrl = System::getContainer()->get('router')->generate('contao_backend') . '?do=' . Input::get('do');

				if (Input::get('table') !== null)
				{
					$strUrl .= '&amp;table=' . Input::get('table');
				}

				// Tree view
				if ($this->treeView)
				{
					$strUrl .= '&amp;act=create&amp;mode=1&amp;pid=' . $this->intId;
				}

				// Parent view
				elseif (($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_PARENT)
				{
					$strUrl .= Database::getInstance()->fieldExists('sorting', $this->strTable) ? '&amp;act=create&amp;mode=1&amp;pid=' . $this->intId : '&amp;act=create&amp;mode=2&amp;pid=' . ($currentRecord['pid'] ?? null);

					if (($currentRecord['ptable'] ?? null) === $this->strTable)
					{
						$strUrl .= '&amp;ptable=' . $currentRecord['ptable'];
					}
				}

				// List view
				else
				{
					$strUrl .= $this->ptable ? '&amp;act=create&amp;mode=2&amp;pid=' . $this->intCurrentPid : '&amp;act=create';
				}

				$this->redirect($strUrl . '&amp;rt=' . System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue());
			}
			elseif (Input::post('saveNduplicate') !== null)
			{
				Message::reset();

				$strUrl = System::getContainer()->get('router')->generate('contao_backend') . '?do=' . Input::get('do');

				if (Input::get('table') !== null)
				{
					$strUrl .= '&amp;table=' . Input::get('table');
				}

				// Tree view
				if ($this->treeView)
				{
					$strUrl .= '&amp;act=copy&amp;mode=1&amp;id=' . $this->intId . '&amp;pid=' . $this->intId;
				}

				// Parent view
				elseif (($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_PARENT)
				{
					$strUrl .= Database::getInstance()->fieldExists('sorting', $this->strTable) ? '&amp;act=copy&amp;mode=1&amp;pid=' . $this->intId . '&amp;id=' . $this->intId : '&amp;act=copy&amp;mode=2&amp;pid=' . $this->intCurrentPid . '&amp;id=' . $this->intId;

					if (($currentRecord['ptable'] ?? null) === $this->strTable)
					{
						$strUrl .= '&amp;ptable=' . $currentRecord['ptable'];
					}
				}

				// List view
				else
				{
					$strUrl .= $this->ptable ? '&amp;act=copy&amp;mode=2&amp;pid=' . $this->intCurrentPid . '&amp;id=' . $this->intId : '&amp;act=copy&amp;id=' . $this->intId;
				}

				$this->redirect($strUrl . '&amp;rt=' . System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue());
			}

			$this->reload();
		}

		// Versions overview
		if (($GLOBALS['TL_DCA'][$this->strTable]['config']['enableVersioning'] ?? null) && !($GLOBALS['TL_DCA'][$this->strTable]['config']['hideVersionMenu'] ?? null))
		{
			$parameters['version_dropdown'] = $objVersions->renderDropdown();
		}
		else
		{
			$parameters['version_dropdown'] = '';
		}

		$security = System::getContainer()->get('security.helper');

		// Form settings and buttons
		$parameters['form_buttons'] = System::getContainer()
			->get('contao.data_container.buttons_builder')
			->generateEditButtons(
				$this->strTable,
				(bool) $this->ptable,
				$security->isGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new CreateAction($this->strTable, $this->addDynamicPtable(array('pid' => $this->intCurrentPid)))),
				$security->isGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new CreateAction($this->strTable, array_replace($currentRecord, array('id' => null, 'sorting' => null)))),
				$this
			)
		;

		// Back button
		$strBackUrl = $this->getReferer(true);

		if ((string) $currentRecord['tstamp'] === '0')
		{
			$strBackUrl = preg_replace('/&(?:amp;)?revise=[^&]+|$/', '&amp;revise=' . $this->strTable . '.' . ((int) $this->intId), $strBackUrl, 1);
		}

		$parameters['global_operations'] = Input::get('nb')
			? null
			: System::getContainer()
				->get('contao.data_container.global_operations_builder')
				->initialize($this->strTable)
				->addBackButton($strBackUrl)
		;

		// Messages
		$parameters['message'] = Message::generate();

		return $this->render('edit/single', $parameters);
	}

	/**
	 * Auto-generate a form to edit all records that are currently shown
	 *
	 * @param integer $intId
	 * @param integer $ajaxId
	 *
	 * @return string
	 *
	 * @throws AccessDeniedException
	 */
	public function editAll($intId=null, $ajaxId=null)
	{
		if ($GLOBALS['TL_DCA'][$this->strTable]['config']['notEditable'] ?? null)
		{
			throw new AccessDeniedException('Table "' . $this->strTable . '" is not editable.');
		}

		$objSession = System::getContainer()->get('request_stack')->getSession();

		// Get current IDs from session
		$session = $objSession->all();
		$ids = $session['CURRENT']['IDS'] ?? array();

		if ($intId && Environment::get('isAjaxRequest'))
		{
			$ids = array($intId);
		}

		// Save field selection in session
		if (Input::post('FORM_SUBMIT') == $this->strTable . '_all' && Input::get('fields'))
		{
			$session['CURRENT'][$this->strTable] = Input::post('all_fields');
			$objSession->replace($session);
		}

		$db = Database::getInstance();
		$security = System::getContainer()->get('security.helper');
		$user = BackendUser::getInstance();

		$this->configurePidAndSortingFields();

		$parameters = array(
			'ids' => $ids,
			'add_jump_targets' => \count($ids) < min(Config::get('resultsPerPage') ?? 30, 50),
			'global_operations' => System::getContainer()
				->get('contao.data_container.global_operations_builder')
				->initialize($this->strTable)
				->addBackButton(),
		);

		$fields = $session['CURRENT'][$this->strTable] ?? array();

		// Step 2: Show a form to select the fields
		if (!empty($fields) && \is_array($fields) && Input::get('fields'))
		{
			if (Input::post('FORM_SUBMIT') == $this->strTable)
			{
				$db->beginTransaction();
			}

			try
			{
				$blnNoReload = false;

				static::preloadCurrentRecords($ids, $this->strTable);

				// Walk through each record
				$parameters['boxes'] = array();

				foreach ($ids as $id)
				{
					try
					{
						$currentRecord = $this->getCurrentRecord($id);

						if (null === $currentRecord)
						{
							continue;
						}

						$this->denyAccessUnlessGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new UpdateAction($this->strTable, $currentRecord));
					}
					catch (AccessDeniedException)
					{
						continue;
					}

					$this->intId = $id;
					$this->procedure = array('id=?');
					$this->values = array($this->intId);
					$this->arrSubmit = array();
					$this->blnCreateNewVersion = false;
					$this->strPalette = $this->getPalette();

					$boxes = System::getContainer()->get('contao.data_container.palette_builder')->getBoxes($this->strPalette, $this->strTable, true);
					$paletteFields = array_merge(...array_column($boxes, 'fields'));

					// Reset the "noReload" state but remember it for the final handling
					$blnNoReload = $blnNoReload || $this->noReload;
					$this->noReload = false;

					$objVersions = new Versions($this->strTable, $this->intId);
					$objVersions->initialize();

					// Begin current row
					$thisId = '';

					// Store the active record (backwards compatibility)
					$this->objActiveRecord = (object) $currentRecord;

					// Generate the box's tree of widget groups. When edit() was called in
					// AJAX mode, we return early and only render the matching subtree.
					$widgetGroupTree = new ArrayTree();
					$ajaxWidgetGroupTree = null;

					foreach ($paletteFields as $v)
					{
						if ($v == '[EOF]')
						{
							if ($ajaxWidgetGroupTree && $ajaxId == $thisId)
							{
								return $this->render('edit/ajax', array(
									'version_number' => $objVersions->getLatestVersion(),
									'widget_groups_tree' => $ajaxWidgetGroupTree,
								));
							}

							$widgetGroupTree->up();

							continue;
						}

						if (preg_match('/^\[.*]$/', $v))
						{
							$thisId = 'sub_' . substr($v, 1, -1) . '_' . $id;

							$widgetGroupTree->enterChildNode($thisId);

							if ($ajaxId == $thisId && Environment::get('isAjaxRequest'))
							{
								// We only need to output the current subtree in AJAX mode, so we store a reference here
								$ajaxWidgetGroupTree = $widgetGroupTree->current();
							}

							continue;
						}

						if (!\in_array($v, $fields))
						{
							continue;
						}

						$this->strField = $v;
						$this->strInputName = $v . '_' . $this->intId;

						// Set the default value and try to load the current value from DB (see #5252)
						if (\array_key_exists('default', $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField] ?? array()))
						{
							$default = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['default'];

							if ($default instanceof \Closure)
							{
								$default = $default($this);
							}

							$this->varValue = \is_array($default) ? serialize($default) : $default;
						}

						if (($currentRecord[$v] ?? null) !== false)
						{
							$this->varValue = $currentRecord[$v] ?? null;
						}

						// Convert CSV fields (see #2890)
						if (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['multiple'] ?? null) && isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['csv']))
						{
							$this->varValue = StringUtil::trimsplit($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['csv'], $this->varValue);
						}

						// Call load_callback
						if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['load_callback'] ?? null))
						{
							foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['load_callback'] as $callback)
							{
								if (\is_array($callback))
								{
									$this->varValue = System::importStatic($callback[0])->{$callback[1]}($this->varValue, $this);
								}
								elseif (\is_callable($callback))
								{
									$this->varValue = $callback($this->varValue, $this);
								}
							}
						}

						// Re-set the current value
						$this->objActiveRecord->{$this->strField} = $this->varValue;

						$widgetGroupTree->addContentNode($this->row());
					}

					// Save record
					try
					{
						$this->submit();
					}
					catch (AccessDeniedException)
					{
						continue;
					}

					$parameters['boxes'][] = array(
						'class' => 'cf',
						'label' => System::getContainer()
							->get('contao.data_container.record_labeler')
							->getLabel('contao.db.' . $this->strTable . '.' . $currentRecord['id'], $currentRecord),
						'widget_groups_tree' => $widgetGroupTree,
						'message' => Message::generateUnwrapped(),
					);
				}

				$this->noReload = $blnNoReload || $this->noReload;
			}
			catch (\Throwable $e)
			{
				if (Input::post('FORM_SUBMIT') == $this->strTable)
				{
					$db->rollbackTransaction();
				}

				throw $e;
			}

			// Reload the page to prevent _POST variables from being sent twice
			if (Input::post('FORM_SUBMIT') == $this->strTable)
			{
				if ($this->noReload)
				{
					$db->rollbackTransaction();
				}
				else
				{
					$db->commitTransaction();

					if (Input::post('saveNclose') !== null)
					{
						$this->redirect($this->getReferer());
					}

					$this->reload();
				}
			}

			$parameters['form_buttons'] = System::getContainer()
				->get('contao.data_container.buttons_builder')
				->generateEditAllButtons($this->strTable, $this)
			;

			return $this->render('edit/multiple', $parameters);
		}

		// Step 1: Show a form to select the fields of the current table
		$fields = array_keys($GLOBALS['TL_DCA'][$this->strTable]['fields'] ?? array());

		// Add meta fields if the current user is an administrator
		if ($user->isAdmin)
		{
			if ($db->fieldExists('sorting', $this->strTable) && !\in_array('sorting', $fields))
			{
				array_unshift($fields, 'sorting');
			}

			if ($db->fieldExists('pid', $this->strTable) && !\in_array('pid', $fields))
			{
				array_unshift($fields, 'pid');
			}
		}

		// Show all non-excluded fields
		$parameters['fields'] = array();

		foreach ($fields as $field)
		{
			if ((!DataContainer::isFieldExcluded($this->strTable, $field) || $security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, $this->strTable . '::' . $field)) && !($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['doNotShow'] ?? null) && (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['inputType']) || \is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['input_field_callback'] ?? null) || \is_callable($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['input_field_callback'] ?? null)))
			{
				$label = ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label'][0] ?? (\is_array($GLOBALS['TL_LANG']['MSC'][$field] ?? null) ? $GLOBALS['TL_LANG']['MSC'][$field][0] : ($GLOBALS['TL_LANG']['MSC'][$field] ?? null)) ?? $field);
				$parameters['fields'][$field] = $label;
			}
		}

		$parameters['error_no_fields'] = Input::isPost() && !Input::post('all_fields');
		$parameters['show_help'] = !$parameters['error_no_fields'] && Config::get('showHelp') && isset($GLOBALS['TL_LANG']['MSC']['all_fields'][1]);

		return $this->render('edit/select_fields', $parameters);
	}

	/**
	 * Toggle a field (e.g. "published" or "disable")
	 *
	 * @param integer $intId
	 * @param string  $strSelectorField
	 * @param boolean $blnDoNotRedirect
	 *
	 * @throws AccessDeniedException
	 */
	public function toggle($intId=null, $strSelectorField=null, $blnDoNotRedirect=false)
	{
		if ($GLOBALS['TL_DCA'][$this->strTable]['config']['notEditable'] ?? null)
		{
			throw new AccessDeniedException('Table "' . $this->strTable . '" is not editable.');
		}

		if ($intId)
		{
			$this->intId = $intId;
		}

		$this->strField = $strSelectorField ?? Input::get('field');

		// If the selector field is read from the query string, check that toggling it is allowed
		if (null === $strSelectorField && ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['toggle'] ?? false) !== true && ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['reverseToggle'] ?? false) !== true)
		{
			throw new AccessDeniedException('Field "' . $this->strTable . '.' . $this->strField . '" cannot be toggled.');
		}

		// Security check before using field in DB query!
		if (!Database::getInstance()->fieldExists($this->strField, $this->strTable) && !\in_array($this->strField, array_keys(DcaExtractor::getInstance($this->strTable)->getVirtualFields())))
		{
			throw new AccessDeniedException('Field ' . $this->strTable . '.' . $this->strField . ' does not exist.');
		}

		// Check the field access
		if (DataContainer::isFieldExcluded($this->strTable, $this->strField) && !System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, $this->strTable . '::' . $this->strField))
		{
			throw new AccessDeniedException('Not enough permissions to toggle field ' . $this->strTable . '.' . $this->strField . ' of record ID ' . $intId . '.');
		}

		// Get the current record
		$currentRecord = $this->getCurrentRecord();

		// Redirect if there is no record with the given ID
		if (null === $currentRecord)
		{
			throw new AccessDeniedException('Cannot load record "' . $this->strTable . '.id=' . $this->intId . '".');
		}

		// Store the active record (backwards compatibility)
		$this->objActiveRecord = (object) $currentRecord;

		$this->procedure = array('id=?');
		$this->values = array($this->intId);
		$this->blnCreateNewVersion = false;

		$objVersions = new Versions($this->strTable, $this->intId);
		$objVersions->initialize();

		$prevSubmit = Input::post('FORM_SUBMIT', true);
		Input::setPost('FORM_SUBMIT', $this->strTable);

		$this->varValue = $currentRecord[$this->strField] ?? null;
		$this->save(!$this->varValue);

		$this->submit();

		Input::setPost('FORM_SUBMIT', $prevSubmit);

		if (!$blnDoNotRedirect)
		{
			$this->redirect($this->getReferer());
		}
	}

	/**
	 * Auto-generate a form to override all records that are currently shown
	 *
	 * @return string
	 *
	 * @throws AccessDeniedException
	 */
	public function overrideAll()
	{
		if ($GLOBALS['TL_DCA'][$this->strTable]['config']['notEditable'] ?? null)
		{
			throw new AccessDeniedException('Table "' . $this->strTable . '" is not editable.');
		}

		$objSession = System::getContainer()->get('request_stack')->getSession();

		// Get current IDs from session
		$session = $objSession->all();
		$ids = $session['CURRENT']['IDS'] ?? array();

		// Save field selection in session
		if (Input::post('FORM_SUBMIT') == $this->strTable . '_all' && Input::get('fields'))
		{
			$session['CURRENT'][$this->strTable] = Input::post('all_fields');
			$objSession->replace($session);
		}

		$db = Database::getInstance();
		$security = System::getContainer()->get('security.helper');
		$user = BackendUser::getInstance();

		$this->configurePidAndSortingFields();

		$parameters = array(
			'ids' => $ids,
			'global_operations' => System::getContainer()
				->get('contao.data_container.global_operations_builder')
				->initialize($this->strTable)
				->addBackButton(),
		);

		$fields = $session['CURRENT'][$this->strTable] ?? array();

		// Step 2: Show a form to select the fields
		if (!empty($fields) && \is_array($fields) && Input::get('fields'))
		{
			$excludedFields = array();

			// Save record
			if (Input::post('FORM_SUBMIT') == $this->strTable)
			{
				$db->beginTransaction();

				try
				{
					static::preloadCurrentRecords($ids, $this->strTable);

					foreach ($ids as $id)
					{
						try
						{
							$currentRecord = $this->getCurrentRecord($id);

							if ($currentRecord === null)
							{
								continue;
							}
						}
						catch (AccessDeniedException)
						{
							continue;
						}

						$this->intId = $id;
						$this->procedure = array('id=?');
						$this->values = array($this->intId);
						$this->arrSubmit = array();
						$this->blnCreateNewVersion = false;

						// Store the active record (backwards compatibility)
						$this->objActiveRecord = (object) $currentRecord;

						$objVersions = new Versions($this->strTable, $this->intId);
						$objVersions->initialize();

						// Store all fields
						foreach ($fields as $v)
						{
							// Check whether field is excluded
							if (isset($excludedFields[$v]) || (DataContainer::isFieldExcluded($this->strTable, $v) && !$security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, $this->strTable . '::' . $v)))
							{
								$excludedFields[$v] = true;

								continue;
							}

							$this->strField = $v;
							$this->strInputName = $v;
							$this->varValue = '';

							// Make sure the new value is applied
							$GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['eval']['alwaysSave'] = true;

							// Store value
							$this->row();
						}

						try
						{
							$this->submit();
						}
						catch (AccessDeniedException)
						{
							continue;
						}
					}
				}
				catch (\Throwable $e)
				{
					$db->rollbackTransaction();

					throw $e;
				}

				// Reload the page to prevent _POST variables from being sent twice
				if ($this->noReload)
				{
					$db->rollbackTransaction();
				}
				else
				{
					$db->commitTransaction();

					if (Input::post('saveNclose') !== null)
					{
						$this->redirect($this->getReferer());
					}

					$this->reload();
				}
			}

			// Begin current row
			$parameters['widgets'] = array();

			foreach ($fields as $v)
			{
				// Check whether field is excluded
				if (isset($excludedFields[$v]) || (DataContainer::isFieldExcluded($this->strTable, $v) && !$security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, $this->strTable . '::' . $v)))
				{
					continue;
				}

				$this->intId = 0;
				$this->procedure = array('id=?');
				$this->values = array($this->intId);
				$this->strField = $v;
				$this->strInputName = $v;
				$this->varValue = '';

				// Disable auto-submit
				$GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['submitOnChange'] = false;
				$parameters['widgets'][] = $this->row();
			}

			$parameters['form_buttons'] = System::getContainer()
				->get('contao.data_container.buttons_builder')
				->generateEditAllButtons($this->strTable, $this)
			;

			$parameters['message'] = Message::generate();

			return $this->render('edit/override_all', $parameters);
		}

		// Step 1: Show a form to select the fields of the current table
		$fields = array_keys($GLOBALS['TL_DCA'][$this->strTable]['fields'] ?? array());

		// Add meta fields if the current user is an administrator
		if ($user->isAdmin)
		{
			if ($db->fieldExists('sorting', $this->strTable) && !\in_array('sorting', $fields))
			{
				array_unshift($fields, 'sorting');
			}

			if ($db->fieldExists('pid', $this->strTable) && !\in_array('pid', $fields))
			{
				array_unshift($fields, 'pid');
			}
		}

		// Show all non-excluded fields
		$parameters['fields'] = array();

		foreach ($fields as $field)
		{
			if ((!DataContainer::isFieldExcluded($this->strTable, $field) || $security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, $this->strTable . '::' . $field)) && !($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['doNotShow'] ?? null) && (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['inputType']) || \is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['input_field_callback'] ?? null) || \is_callable($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['input_field_callback'] ?? null)))
			{
				$label = ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label'][0] ?? (\is_array($GLOBALS['TL_LANG']['MSC'][$field] ?? null) ? $GLOBALS['TL_LANG']['MSC'][$field][0] : ($GLOBALS['TL_LANG']['MSC'][$field] ?? null)) ?? $field);
				$parameters['fields'][$field] = $label;
			}
		}

		$parameters['error_no_fields'] = Input::isPost() && !Input::post('all_fields');
		$parameters['show_help'] = !$parameters['error_no_fields'] && Config::get('showHelp') && isset($GLOBALS['TL_LANG']['MSC']['all_fields'][1]);

		$parameters['message'] = Message::generate();

		return $this->render('edit/select_fields', $parameters);
	}

	/**
	 * Save the current value
	 *
	 * @param mixed $varValue
	 *
	 * @throws \Exception
	 */
	protected function save($varValue)
	{
		if (Input::post('FORM_SUBMIT') != $this->strTable)
		{
			return;
		}

		$arrData = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField] ?? array();

		// Convert date formats into timestamps
		if ($varValue !== null && $varValue !== '' && \in_array($arrData['eval']['rgxp'] ?? null, array('date', 'time', 'datim')))
		{
			$objDate = new Date($varValue, Date::getFormatFromRgxp($arrData['eval']['rgxp']));
			$varValue = $objDate->tstamp;
		}

		$currentRecord = $this->getCurrentRecord();

		// Handle multi-select fields in "override all" mode
		if ($currentRecord !== null && (($arrData['inputType'] ?? null) == 'checkbox' || ($arrData['inputType'] ?? null) == 'checkboxWizard') && ($arrData['eval']['multiple'] ?? null) && Input::get('act') == 'overrideAll')
		{
			$new = StringUtil::deserialize($varValue, true);
			$old = StringUtil::deserialize($currentRecord[$this->strField] ?? null, true);

			// Call load_callback
			if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['load_callback'] ?? null))
			{
				foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['load_callback'] as $callback)
				{
					if (\is_array($callback))
					{
						$old = System::importStatic($callback[0])->{$callback[1]}($old, $this);
					}
					elseif (\is_callable($callback))
					{
						$old = $callback($old, $this);
					}
				}
			}

			switch (Input::post($this->strInputName . '_update'))
			{
				case 'add':
					$varValue = array_values(array_unique(array_merge($old, $new)));
					break;

				case 'remove':
					$varValue = array_values(array_diff($old, $new));
					break;

				case 'replace':
					$varValue = $new;
					break;
			}

			if (empty($varValue) || !\is_array($varValue))
			{
				$varValue = Widget::getEmptyStringOrNullByFieldType($arrData['sql'] ?? array());
			}
			else
			{
				$varValue = serialize($varValue);
			}
		}

		// Convert arrays (see #2890)
		if (($arrData['eval']['multiple'] ?? null) && isset($arrData['eval']['csv']))
		{
			$varValue = implode($arrData['eval']['csv'], StringUtil::deserialize($varValue, true));
		}

		// Trigger the save_callback
		if (\is_array($arrData['save_callback'] ?? null))
		{
			foreach ($arrData['save_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					$varValue = System::importStatic($callback[0])->{$callback[1]}($varValue, $this);
				}
				elseif (\is_callable($callback))
				{
					$varValue = $callback($varValue, $this);
				}
			}
		}

		// Make sure unique fields are unique
		if (($arrData['eval']['unique'] ?? null) && (\is_array($varValue) || (string) $varValue !== '') && !Database::getInstance()->isUniqueValue($this->strTable, $this->strField, $varValue, $currentRecord['id'] ?? null))
		{
			throw new \Exception(\sprintf($GLOBALS['TL_LANG']['ERR']['unique'], $arrData['label'][0] ?? $this->strField));
		}

		// Save the value if there was no error
		if ((\is_array($varValue) || (string) $varValue !== '' || !($arrData['eval']['doNotSaveEmpty'] ?? null)) && ($this->varValue !== $varValue || ($arrData['eval']['alwaysSave'] ?? null)))
		{
			// Set the correct empty value (see #6284, #6373)
			if (!\is_array($varValue) && (string) $varValue === '')
			{
				$varValue = Widget::getEmptyValueByFieldType($arrData['sql'] ?? array());
			}

			$this->arrSubmit[$this->strField] = $varValue;
			$this->varValue = StringUtil::deserialize($varValue);

			if (\is_object($this->objActiveRecord))
			{
				$this->objActiveRecord->{$this->strField} = $this->varValue;
			}
		}
	}

	protected function submit()
	{
		if (Input::post('FORM_SUBMIT') != $this->strTable)
		{
			return;
		}

		$arrValues = $this->arrSubmit;
		$this->arrSubmit = array();

		if (!$this->noReload && !empty($arrValues))
		{
			$arrValues['tstamp'] = time();

			if ($GLOBALS['TL_DCA'][$this->strTable]['config']['dynamicPtable'] ?? null)
			{
				$arrValues['ptable'] = $this->ptable;
			}

			// Trigger the onbeforesubmit_callback
			if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['onbeforesubmit_callback'] ?? null))
			{
				foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['onbeforesubmit_callback'] as $callback)
				{
					try
					{
						if (\is_array($callback))
						{
							$arrValues = System::importStatic($callback[0])->{$callback[1]}($arrValues, $this);
						}
						elseif (\is_callable($callback))
						{
							$arrValues = $callback($arrValues, $this);
						}
					}
					catch (\Exception $e)
					{
						$this->noReload = true;
						Message::addError($e->getMessage());
						System::getContainer()->get('request_stack')?->getMainRequest()->attributes->set('_contao_widget_error', true);

						break;
					}

					if (!\is_array($arrValues))
					{
						throw new \RuntimeException('The onbeforesubmit_callback must return the values!');
					}
				}
			}
		}

		// Check permissions
		$currentRecord = $this->getCurrentRecord();

		$this->denyAccessUnlessGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new UpdateAction($this->strTable, $currentRecord, $arrValues));

		// Persist values
		if (!$this->noReload && !empty($arrValues))
		{
			$arrTypes = array();
			$blnVersionize = false;

			$db = Database::getInstance();

			// Get all virtual field data from the current record, so that the fields can be combined
			$arrValues = array_merge(array_intersect_key($currentRecord, DcaExtractor::getInstance($this->strTable)->getVirtualFields()), $arrValues);

			// Combine virtual fields
			$arrValues = System::getContainer()->get('contao.data_container.virtual_fields_handler')->combineFields($arrValues, $this->strTable);

			foreach ($arrValues as $strField => $varValue)
			{
				$arrData = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$strField] ?? array();

				// If the field is a fallback field, empty all other columns (see #6498)
				if ($varValue && ($arrData['eval']['fallback'] ?? null))
				{
					$varEmpty = Widget::getEmptyValueByFieldType($GLOBALS['TL_DCA'][$this->strTable]['fields'][$strField]['sql'] ?? array());
					$arrType = array_filter(array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$strField]['sql']['type'] ?? null));

					if (($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_PARENT)
					{
						$db
							->prepare("UPDATE " . $this->strTable . " SET " . Database::quoteIdentifier($strField) . "=? WHERE pid=?")
							->query('', array($varEmpty, $currentRecord['pid'] ?? null), $arrType);
					}
					else
					{
						$db
							->prepare("UPDATE " . $this->strTable . " SET " . Database::quoteIdentifier($strField) . "=?")
							->query('', array($varEmpty), $arrType);
					}
				}

				$arrTypes[] = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$strField]['sql']['type'] ?? null;

				if (!isset($arrData['eval']['versionize']) || $arrData['eval']['versionize'] !== false)
				{
					$blnVersionize = true;
				}

				// Update the active record and current field/value (backwards compatibility)
				if (\is_object($this->objActiveRecord))
				{
					$this->objActiveRecord->{$strField} = StringUtil::deserialize($varValue);
				}
			}

			$objUpdateStmt = $db
				->prepare("UPDATE " . $this->strTable . " %s WHERE " . implode(' AND ', $this->procedure))
				->set($arrValues)
				->query('', array_merge(array_values($arrValues), $this->values), $arrTypes);

			if ($objUpdateStmt->affectedRows)
			{
				// Empty cached data for this record
				self::clearCurrentRecordCache($this->intId, $this->strTable);
				$this->invalidateCacheTags();

				if ($blnVersionize)
				{
					$this->blnCreateNewVersion = true;
				}
			}
		}

		// Trigger the onsubmit_callback
		if (!$this->noReload && \is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['onsubmit_callback'] ?? null))
		{
			foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['onsubmit_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					System::importStatic($callback[0])->{$callback[1]}($this);
				}
				elseif (\is_callable($callback))
				{
					$callback($this);
				}
			}
		}

		// Create a new version
		if ($this->blnCreateNewVersion)
		{
			$objVersions = new Versions($this->strTable, $this->intId);
			$objVersions->create();
		}
	}

	/**
	 * Return the name of the current palette
	 *
	 * @return string
	 */
	public function getPalette()
	{
		return System::getContainer()
			->get('contao.data_container.palette_builder')
			->getPalette($this->strTable, (int) $this->intId, $this)
		;
	}

	/**
	 * Delete all incomplete and unrelated records
	 */
	protected function reviseTable()
	{
		$reload = false;
		$ptable = $GLOBALS['TL_DCA'][$this->strTable]['config']['ptable'] ?? null;
		$ctable = $GLOBALS['TL_DCA'][$this->strTable]['config']['ctable'] ?? null;

		if ($ptable === null && ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_TREE)
		{
			$ptable = $this->strTable;
		}

		$objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');

		$new_records = $objSessionBag->get('new_records');

		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['reviseTable']) && \is_array($GLOBALS['TL_HOOKS']['reviseTable']))
		{
			foreach ($GLOBALS['TL_HOOKS']['reviseTable'] as $callback)
			{
				$status = null;

				if (\is_array($callback))
				{
					$status = System::importStatic($callback[0])->{$callback[1]}($this->strTable, $new_records[$this->strTable] ?? null, $ptable, $ctable);
				}
				elseif (\is_callable($callback))
				{
					$status = $callback($this->strTable, $new_records[$this->strTable] ?? null, $ptable, $ctable);
				}

				if ($status === true)
				{
					$reload = true;
				}
			}
		}

		if (isset($new_records[$this->strTable]))
		{
			unset($new_records[$this->strTable]);
			$objSessionBag->set('new_records', $new_records);
		}

		$db = Database::getInstance();

		// Delete all new but incomplete records (tstamp=0)
		if ($strReviseTable = Input::get('revise'))
		{
			list($strTable, $intId) = explode('.', $strReviseTable, 2);

			if ($intId && $strTable === $this->strTable)
			{
				$origId = $this->id;
				$origActiveRecord = $this->activeRecord;

				try
				{
					// Get the current record
					$currentRecord = $this->getCurrentRecord($intId);
				}
				catch (AccessDeniedException)
				{
					$currentRecord = null;
				}

				if ($currentRecord !== null)
				{
					$this->id = $intId;
					$this->activeRecord = (object) $currentRecord;

					// Invalidate cache tags (no need to invalidate the parent)
					$this->invalidateCacheTags();

					$this->id = $origId;
					$this->activeRecord = $origActiveRecord;

					$objStmt = $db
						->prepare("DELETE FROM " . $this->strTable . " WHERE id=? AND tstamp=0")
						->execute((int) $intId);

					if ($objStmt->affectedRows > 0)
					{
						$reload = true;
					}
				}
			}
		}

		// Delete all records of the current table that are not related to the parent table
		if ($ptable && !($GLOBALS['TL_DCA'][$this->strTable]['config']['doNotDeleteRecords'] ?? null))
		{
			if ($GLOBALS['TL_DCA'][$this->strTable]['config']['dynamicPtable'] ?? null)
			{
				$objIds = $db->execute("SELECT c.id FROM " . $this->strTable . " c LEFT JOIN " . $ptable . " p ON c.pid=p.id WHERE c.ptable='" . $ptable . "' AND p.id IS NULL");
			}
			elseif ($ptable == $this->strTable)
			{
				$objIds = $db->execute('SELECT c.id FROM ' . $this->strTable . ' c LEFT JOIN ' . $ptable . ' p ON c.pid=p.id WHERE p.id IS NULL AND c.pid > 0');
			}
			else
			{
				$objIds = $db->execute("SELECT c.id FROM " . $this->strTable . " c LEFT JOIN " . $ptable . " p ON c.pid=p.id WHERE p.id IS NULL");
			}

			if ($objIds->numRows)
			{
				$objStmt = $db->execute("DELETE FROM " . $this->strTable . " WHERE id IN(" . implode(',', array_map('\intval', $objIds->fetchEach('id'))) . ")");

				if ($objStmt->affectedRows > 0)
				{
					$reload = true;
				}
			}
		}

		// Delete all records of the child table that are not related to the current table
		if (!empty($ctable) && \is_array($ctable))
		{
			foreach ($ctable as $v)
			{
				if ($v)
				{
					// Load the DCA configuration, so we can check for "dynamicPtable" and "doNotDeleteRecords"
					$this->loadDataContainer($v);

					if ($GLOBALS['TL_DCA'][$v]['config']['doNotDeleteRecords'] ?? null)
					{
						continue;
					}

					if ($GLOBALS['TL_DCA'][$v]['config']['dynamicPtable'] ?? null)
					{
						$objIds = $db->execute("SELECT c.id FROM " . $v . " c LEFT JOIN " . $this->strTable . " p ON c.pid=p.id WHERE c.ptable='" . $this->strTable . "' AND p.id IS NULL");
					}
					else
					{
						$objIds = $db->execute("SELECT c.id FROM " . $v . " c LEFT JOIN " . $this->strTable . " p ON c.pid=p.id WHERE p.id IS NULL");
					}

					if ($objIds->numRows)
					{
						$objStmt = $db->execute("DELETE FROM " . $v . " WHERE id IN(" . implode(',', array_map('\intval', $objIds->fetchEach('id'))) . ")");

						if ($objStmt->affectedRows > 0)
						{
							$reload = true;
						}
					}
				}
			}
		}

		// Reload the page
		if ($reload)
		{
			$this->reload();
		}
	}

	/**
	 * List all records of the current table as tree and return them as HTML string
	 *
	 * @return string
	 */
	protected function treeView()
	{
		$table = $this->strTable;
		$blnModeTreeExtended = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_TREE_EXTENDED;

		if ($blnModeTreeExtended)
		{
			$table = $this->ptable;

			System::loadLanguageFile($table);
			$this->loadDataContainer($table);
		}

		$db = Database::getInstance();

		$objSession = System::getContainer()->get('request_stack')->getSession();
		$objSessionBag = $objSession->getBag('contao_backend');
		$session = $objSessionBag->all();

		// Toggle the nodes
		if (Input::get('ptg') == 'all')
		{
			$node = $blnModeTreeExtended ? $this->strTable . '_' . $table . '_tree' : $this->strTable . '_tree';
			$state = Input::get('state');

			// Expand tree
			if ($state || (null === $state && (empty($session[$node]) || !\is_array($session[$node]) || current($session[$node]) != 1)))
			{
				$session[$node] = array();
				$objNodes = $db->execute("SELECT DISTINCT pid FROM " . $table . " WHERE pid>0");

				while ($objNodes->next())
				{
					$session[$node][$objNodes->pid] = 1;
				}
			}

			// Collapse tree
			else
			{
				$session[$node] = array();
			}

			$objSessionBag->replace($session);
			$this->redirect(preg_replace('/(&(amp;)?|\?)ptg=[^& ]*/i', '', Environment::get('requestUri')));
		}

		// Throw if a mandatory field (id, pid, sorting) is missing
		if (($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_TREE && (!$db->fieldExists('id', $table) || !$db->fieldExists('pid', $table) || !$db->fieldExists('sorting', $table)))
		{
			throw new \LogicException(\sprintf('Table "%s" can not be shown as tree, because the "id", "pid" or "sorting" field is missing!', $table));
		}

		// Throw if there is no parent table
		if (!$this->ptable && $blnModeTreeExtended)
		{
			throw new \LogicException(\sprintf('Table "%s" can not be shown as extended tree, because there is no parent table!', $table));
		}

		$arrClipboard = System::getContainer()->get('contao.data_container.clipboard_manager')->get($this->strTable);
		$blnClipboard = null !== $arrClipboard;

		$security = System::getContainer()->get('security.helper');

		// Begin buttons container
		$operations = System::getContainer()->get('contao.data_container.global_operations_builder')->initialize($this->strTable);

		if (Input::get('act') == 'select')
		{
			$operations->addBackButton();
		}
		elseif (isset($GLOBALS['TL_DCA'][$this->strTable]['config']['backlink']))
		{
			$operations->addBackButton($GLOBALS['TL_DCA'][$this->strTable]['config']['backlink']);
		}

		if (Input::get('act') != 'select' && !$blnClipboard && !($GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] ?? null) && !($GLOBALS['TL_DCA'][$this->strTable]['config']['notCreatable'] ?? null) && $security->isGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new CreateAction($this->strTable, array('pid' => $this->intCurrentPid, 'sorting' => 0))))
		{
			$operations->addNewButton($operations::CREATE_PASTE);
		}

		if ($blnClipboard)
		{
			$operations->addClearClipboardButton();
		}
		elseif (null !== ($buttons = $this->generateGlobalButtons($operations)))
		{
			trigger_deprecation('contao/core-bundle', '5.6', 'Overriding DataContainer::generateGlobalButtons() is deprecated and will no longer work in Contao 6.');

			$operations->append(array('html' => $buttons), true);
		}

		$operations->addFilterButton();

		$parameters = array(
			'extended_mode' => $blnModeTreeExtended,
			'message' => Message::generate(),
			'global_operations' => $operations,
			'has_clipboard_content' => $blnClipboard,
			'panel_active' => $this->panelActive,
		);

		$blnHasSorting = $db->fieldExists('sorting', $table);
		$arrFound = array();

		if (!empty($this->procedure))
		{
			if (!$blnModeTreeExtended)
			{
				$objFound = $db
					->prepare("SELECT id FROM " . $this->strTable . " WHERE " . implode(' AND ', $this->procedure) . " ORDER BY sorting, id")
					->execute(...$this->values);
			}
			elseif ($blnHasSorting)
			{
				$objFound = $db
					->prepare("SELECT pid AS id, (SELECT sorting FROM " . $table . " WHERE " . $this->strTable . ".pid=" . $table . ".id) AS psort FROM " . $this->strTable . " WHERE " . implode(' AND ', $this->procedure) . " GROUP BY pid ORDER BY psort, pid")
					->execute(...$this->values);
			}
			else
			{
				$objFound = $db
					->prepare("SELECT pid AS id FROM " . $this->strTable . " WHERE " . implode(' AND ', $this->procedure) . " GROUP BY pid")
					->execute(...$this->values);
			}

			if ($objFound->numRows < 1)
			{
				$this->updateRoot(array());
			}
			// Respect existing limitations (root IDs)
			elseif (!empty($this->root))
			{
				while ($objFound->next())
				{
					if (\count(array_intersect($this->root, $this->getParentRecordIds(array($objFound->id), $table))) > 0)
					{
						$arrFound[] = $objFound->id;
					}
				}

				$this->updateRoot($arrFound, true);
			}
			else
			{
				$arrFound = $objFound->fetchEach('id');
				$this->updateRoot($arrFound, true);
			}
		}

		$topMostRootIds = $this->root;

		if (isset($GLOBALS['TL_DCA'][$table]['list']['sorting']['visibleRoot']))
		{
			$topMostRootIds = array($GLOBALS['TL_DCA'][$table]['list']['sorting']['visibleRoot']);
		}
		elseif (!empty($this->visibleRootTrails))
		{
			// Make sure we use the topmost root IDs only from all the visible root trail ids and also ensure correct sorting
			$topMostRootIds = $db
				->prepare("SELECT id FROM $table WHERE (pid=0 OR pid IS NULL) AND id IN (" . implode(',', array_merge($this->visibleRootTrails, $this->root)) . ")" . ($db->fieldExists('sorting', $table) ? ' ORDER BY sorting, id' : ''))
				->execute()
				->fetchEach('id');
		}
		elseif (!empty($this->root) && $db->fieldExists('sorting', $table))
		{
			// Ensure correct sorting of root IDs, e.g. if user is given access to a limited set of root page.
			$topMostRootIds = $db
				->prepare("SELECT id FROM $table WHERE id IN (" . implode(',', $this->root) . ') ORDER BY sorting, id')
				->execute()
				->fetchEach('id');
		}

		// Call a recursive function that builds the tree
		$records = array();

		if (!empty($topMostRootIds))
		{
			static::preloadCurrentRecords($topMostRootIds, $table);
			$clipboardManager = System::getContainer()->get('contao.data_container.clipboard_manager');

			for ($i=0, $c=\count($topMostRootIds); $i<$c; $i++)
			{
				$records[] = $this->generateTree($table, $topMostRootIds[$i], array('p'=>($topMostRootIds[$i - 1] ?? null), 'n'=>($topMostRootIds[$i + 1] ?? null)), $blnHasSorting, -16, $blnClipboard ? $arrClipboard : false, $clipboardManager->isCircularReference($this->strTable, $topMostRootIds[$i]), false, false, $arrFound);
			}
		}

		$parameters['records'] = $records;
		$parameters['breadcrumb'] = $GLOBALS['TL_DCA'][$table]['list']['sorting']['breadcrumb'] ?? '';

		if ($records || $blnClipboard)
		{
			$operations = System::getContainer()->get('contao.data_container.operations_builder')->initialize($this->strTable);

			// Show paste button only if there are no root records specified
			if ($blnClipboard && ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_TREE && $this->rootPaste && Input::get('act') != 'select')
			{
				// Call paste_button_callback (&$dc, $row, $table, $cr, $children, $previous, $next)
				if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback'] ?? null))
				{
					$strClass = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback'][0];
					$strMethod = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback'][1];

					$operations->append(array('primary' => true, 'html'=>System::importStatic($strClass)->$strMethod($this, array('id'=>0), $table, false, $arrClipboard)), true);
				}
				elseif (\is_callable($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback'] ?? null))
				{
					$operations->append(array('primary' => true, 'html'=>$GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback']($this, array('id'=>0), $table, false, $arrClipboard)), true);
				}
				elseif (!$this->canPasteClipboard($arrClipboard, array('pid'=>0, 'sorting'=>0)))
				{
					$operations->addPasteButton('pasteroot', $this->strTable, null);
				}
				else
				{
					$operations->addPasteButton('pasteroot', $this->strTable, $this->addToUrl('act=' . $arrClipboard['mode'] . '&amp;mode=2&amp;pid=0' . (!\is_array($arrClipboard['id']) ? '&amp;id=' . $arrClipboard['id'] : '')));
				}
			}
			elseif (!$blnModeTreeExtended && Input::get('act') != 'select' && !$blnClipboard && !($GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] ?? null) && !($GLOBALS['TL_DCA'][$this->strTable]['config']['notCreatable'] ?? null) && $security->isGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new CreateAction($this->strTable, array('pid' => 0, 'sorting' => 0))))
			{
				$operations->addNewButton($operations::CREATE_TOP, $this->strTable, 0);
			}

			$parameters['operations'] = $operations;

			if (Input::get('act') == 'select')
			{
				$parameters['buttons'] = System::getContainer()->get('contao.data_container.buttons_builder')->generateSelectButtons($this->strTable, $blnHasSorting, $this);
			}
		}

		return $this->render('view/tree', $parameters);
	}

	/**
	 * Generate a particular subpart of the tree and return it as HTML string
	 *
	 * @param integer $id
	 * @param integer $level
	 *
	 * @return string
	 */
	public function ajaxTreeView($id, $level)
	{
		if (!Environment::get('isAjaxRequest'))
		{
			return '';
		}

		$return = '';
		$table = $this->strTable;
		$blnPtable = false;

		// Load parent table
		if (($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_TREE_EXTENDED)
		{
			$table = $this->ptable;

			System::loadLanguageFile($table);
			$this->loadDataContainer($table);

			$blnPtable = true;
		}

		$blnProtected = false;

		// Check protected pages
		if ($table == 'tl_page')
		{
			$objParent = PageModel::findWithDetails($id);
			$blnProtected = $objParent->protected;
		}

		$margin = $level * 16;
		$arrIds = array();

		$db = Database::getInstance();
		$hasSorting = $db->fieldExists('sorting', $table);

		// Get records
		$objRows = $db
			->prepare("SELECT * FROM " . $table . " WHERE pid=?" . ($hasSorting ? " ORDER BY sorting, id" : ""))
			->execute($id);

		while ($objRows->next())
		{
			// Improve performance for $dc->getCurrentRecord($id);
			static::setCurrentRecordCache($objRows->id, $table, $objRows->row());

			$arrIds[] = $objRows->id;
		}

		$clipboardManager = System::getContainer()->get('contao.data_container.clipboard_manager');
		$arrClipboard = $clipboardManager->get($this->strTable);
		$blnClipboard = null !== $arrClipboard;

		for ($i=0, $c=\count($arrIds); $i<$c; $i++)
		{
			$return .= ' ' . trim($this->generateTree($table, $arrIds[$i], array('p'=>($arrIds[$i - 1] ?? null), 'n'=>($arrIds[$i + 1] ?? null)), $hasSorting, $margin, $blnClipboard ? $arrClipboard : false, $clipboardManager->isCircularReference($this->strTable, $arrIds[$i], !$blnPtable), $blnProtected));
		}

		return $return;
	}

	/**
	 * Recursively generate the tree and return it as HTML string
	 *
	 * @param string  $table
	 * @param integer $id
	 * @param array   $arrPrevNext
	 * @param boolean $blnHasSorting
	 * @param integer $intMargin
	 * @param array   $arrClipboard
	 * @param boolean $blnCircularReference
	 * @param boolean $protectedPage
	 * @param boolean $blnNoRecursion
	 * @param array   $arrFound
	 *
	 * @return string
	 */
	protected function generateTree($table, $id, $arrPrevNext, $blnHasSorting, $intMargin=0, $arrClipboard=null, $blnCircularReference=false, $protectedPage=false, $blnNoRecursion=false, $arrFound=array())
	{
		// Check if the ID is visible in the root trail or allowed by permissions (or their children)
		// in tree mode or if $table differs from $this->strTable. The latter will be false in extended
		// tree mode if both $table and $this->strTable point to the child table.
		$checkIdAllowed = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_TREE || $table !== $this->strTable;

		if ($checkIdAllowed && !\in_array($id, $this->visibleRootTrails) && !\in_array($id, $this->root) && !\in_array($id, $this->rootChildren))
		{
			return '';
		}

		$objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');

		$session = $objSessionBag->all();
		$node = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_TREE_EXTENDED ? $this->strTable . '_' . $table . '_tree' : $this->strTable . '_tree';

		// Toggle nodes
		if (Input::get('ptg'))
		{
			if (isset($session[$node][Input::get('ptg')]) && $session[$node][Input::get('ptg')] == 1)
			{
				unset($session[$node][Input::get('ptg')]);
			}
			else
			{
				$session[$node][Input::get('ptg')] = 1;
			}

			$objSessionBag->replace($session);
			$this->redirect(preg_replace('/(&(amp;)?|\?)ptg=[^& ]*/i', '', Environment::get('requestUri')));
		}

		try
		{
			$currentRecord = $this->getCurrentRecord($id, $table);
		}
		catch (AccessDeniedException)
		{
			$currentRecord = null;
		}

		// Special handling for visible root trails, which are tree nodes the user does not have access to.
		// $this->getCurrentRecord() can deny access, but we still need the record to render a label.
		if (null === $currentRecord && !empty($this->visibleRootTrails))
		{
			$currentRecord = Database::getInstance()
				->prepare("SELECT * FROM " . $table . " WHERE id=?")
				->execute($id)
				->fetchAssoc()
			;

			if (!$currentRecord || $checkIdAllowed ? !\in_array($id, $this->visibleRootTrails) : !\in_array($currentRecord['pid'] ?? null, $this->visibleRootTrails))
			{
				return '';
			}
		}

		// Return if there is no result
		if (null === $currentRecord)
		{
			return '';
		}

		$intSpacing = 16;
		$children = array();

		// Add the ID to the list of current IDs
		if ($this->strTable == $table)
		{
			$this->current[] = $currentRecord['id'] ?? null;
		}

		$db = Database::getInstance();

		// Check whether there are child records
		if (!$blnNoRecursion)
		{
			if ($this->strTable != $table || ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_TREE)
			{
				$objChildren = $db
					->prepare("SELECT id FROM " . $table . " WHERE pid=?" . ($blnHasSorting ? " ORDER BY sorting, id" : ''))
					->execute($id);

				if ($objChildren->numRows)
				{
					$children = array_values(array_intersect($objChildren->fetchEach('id'), array_merge($this->visibleRootTrails, $this->root, $this->rootChildren)));
				}
			}
		}

		$blnProtected = false;

		// Check whether the page is protected
		if ($table == 'tl_page')
		{
			$blnProtected = ($currentRecord['protected'] ?? null) || $protectedPage;
		}

		$session[$node][$id] = (\is_int($session[$node][$id] ?? null)) ? $session[$node][$id] : 0;

		// Calculate label and add a toggle button
		$blnIsOpen = !empty($arrFound) || ($session[$node][$id] ?? null) == 1;

		// Always show selected nodes
		if (!$blnIsOpen && !empty($this->arrPickerValue) && (($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_TREE || $table !== $this->strTable))
		{
			$selected = $this->arrPickerValue;

			if (($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_TREE_EXTENDED)
			{
				$selected = $db
					->execute("SELECT pid FROM $this->strTable WHERE id IN (" . implode(',', array_map('\intval', $this->arrPickerValue)) . ')')
					->fetchEach('pid');
			}

			if (!empty(array_intersect($db->getChildRecords(array($id), $table), $selected)))
			{
				$blnIsOpen = true;
			}
		}

		// Check either the ID (tree mode or parent table) or the parent ID (child table)
		$isVisibleRootTrailPage = $checkIdAllowed ? \in_array($id, $this->visibleRootTrails) : \in_array($currentRecord['pid'] ?? null, $this->visibleRootTrails);

		$previous = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_TREE_EXTENDED ? ($arrPrevNext['pp'] ?? null) : ($arrPrevNext['p'] ?? null);
		$next = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_TREE_EXTENDED ? ($arrPrevNext['nn'] ?? null) : ($arrPrevNext['n'] ?? null);
		$_buttons = '';

		if (!$isVisibleRootTrailPage)
		{
			$blnClipboard = false !== $arrClipboard;
			$security = System::getContainer()->get('security.helper');

			if (Input::get('act') == 'select')
			{
				$operations = $this->strTable == $table ? '<input type="checkbox" name="IDS[]" id="ids_' . $id . '" class="tl_tree_checkbox" data-contao--check-all-target="input" data-action="contao--check-all#toggleInput" value="' . $id . '">' : '';
			}
			// Regular buttons ($row, $table, $root, $blnCircularReference, $children, $previous, $next)
			elseif ($this->strTable == $table)
			{
				$operations = $this->generateButtons($currentRecord, $table, $this->root, $blnCircularReference, $children, $previous, $next);

				if (self::MODE_TREE == ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null))
				{
					$operations->addSeparator();

					if (!$blnClipboard && !($GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] ?? null) && !($GLOBALS['TL_DCA'][$this->strTable]['config']['notCreatable'] ?? null) && $security->isGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new CreateAction($this->strTable, array('pid' => $currentRecord['pid'], 'sorting' => $currentRecord['sorting'] + 1))))
					{
						$operations->addNewButton($operations::CREATE_AFTER, $table, $currentRecord['id']);
					}

					if (!$blnClipboard && !($GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] ?? null) && !($GLOBALS['TL_DCA'][$this->strTable]['config']['notCreatable'] ?? null) && $security->isGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new CreateAction($this->strTable, array('pid' => $currentRecord['id'], 'sorting' => 0))))
					{
						$operations->addNewButton($operations::CREATE_INTO, $table, $currentRecord['id']);
					}
				}
				elseif (!$blnClipboard && !($GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] ?? null) && !($GLOBALS['TL_DCA'][$this->strTable]['config']['notCreatable'] ?? null) && $security->isGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new CreateAction($this->strTable, array('pid' => $currentRecord['pid'], 'sorting' => $currentRecord['sorting'] + 1))))
				{
					$operations->addSeparator();
					$operations->addNewButton($operations::CREATE_AFTER, $table, $currentRecord['id']);
				}
			}
			else
			{
				$operations = System::getContainer()->get('contao.data_container.operations_builder')->initialize($this->strTable, $currentRecord['id']);

				if (!$blnClipboard && !($GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] ?? null) && !($GLOBALS['TL_DCA'][$this->strTable]['config']['notCreatable'] ?? null) && $security->isGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new CreateAction($this->strTable, array('pid' => $currentRecord['id'], 'sorting' => 0))))
				{
					$operations->addNewButton($operations::CREATE_INTO, $this->strTable, $currentRecord['id']);
				}
			}

			// Paste buttons (not for root trails)
			if ($arrClipboard !== false && $operations instanceof DataContainerOperationsBuilder)
			{
				// Call paste_button_callback(&$dc, $row, $table, $blnCircularReference, $arrClipboard, $children, $previous, $next)
				if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback'] ?? null))
				{
					$strClass = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback'][0];
					$strMethod = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback'][1];

					$operations->append(array('primary' => true, 'html'=>System::importStatic($strClass)->$strMethod($this, $currentRecord, $table, $blnCircularReference, $arrClipboard, $children, $previous, $next)), true);
				}
				elseif (\is_callable($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback'] ?? null))
				{
					$operations->append(array('primary' => true, 'html'=>$GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['paste_button_callback']($this, $currentRecord, $table, $blnCircularReference, $arrClipboard, $children, $previous, $next)), true);
				}
				else
				{
					// Regular tree
					if (($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_TREE)
					{
						$clipboardManager = System::getContainer()->get('contao.data_container.clipboard_manager');
						$operations->addSeparator();

						// Disable buttons of the page and all its children on cut to avoid circular references
						if ($clipboardManager->isCutMode($this->strTable) && ($blnCircularReference || !$clipboardManager->canPasteAfterOrInto($this->strTable, $id)))
						{
							$operations->addPasteButton('pasteafter', $table, null);
							$operations->addPasteButton('pasteinto', $table, null);
						}
						else
						{
							if ((!$this->rootPaste && \in_array($id, $this->root)) || !$this->canPasteClipboard($arrClipboard, array('pid' => $currentRecord['pid'], 'sorting' => $currentRecord['sorting'] + 1)))
							{
								$operations->addPasteButton('pasteafter', $table, null);
							}
							else
							{
								$operations->addPasteButton('pasteafter', $table, $this->addToUrl('act=' . $arrClipboard['mode'] . '&amp;mode=1&amp;pid=' . $id . (!\is_array($arrClipboard['id']) ? '&amp;id=' . $arrClipboard['id'] : '')));
							}

							if (!$this->canPasteClipboard($arrClipboard, array('pid' => $id, 'sorting' => 0)))
							{
								$operations->addPasteButton('pasteinto', $table, null);
							}
							else
							{
								$operations->addPasteButton('pasteinto', $table, $this->addToUrl('act=' . $arrClipboard['mode'] . '&amp;mode=2&amp;pid=' . $id . (!\is_array($arrClipboard['id']) ? '&amp;id=' . $arrClipboard['id'] : '')));
							}
						}
					}

					// Extended tree
					else
					{
						$operations->addSeparator();

						// Paste after the selected record (e.g. paste article after article X)
						if ($this->strTable == $table)
						{
							$clipboardManager = System::getContainer()->get('contao.data_container.clipboard_manager');

							if ($clipboardManager->isCutMode($this->strTable) && ($blnCircularReference || !$clipboardManager->canPasteAfterOrInto($this->strTable, $id) || !$this->canPasteClipboard($arrClipboard, array('pid' => $currentRecord['pid'], 'sorting' => $currentRecord['sorting'] + 1))))
							{
								$operations->addPasteButton('pasteafter', $table, null);
							}
							else
							{
								$operations->addPasteButton('pasteafter', $table, $this->addToUrl('act=' . $arrClipboard['mode'] . '&amp;mode=1&amp;pid=' . $id . (!\is_array($arrClipboard['id']) ? '&amp;id=' . $arrClipboard['id'] : '')));
							}
						}

						// Paste into the selected record (e.g. paste article into page X)
						else
						{
							if (!$this->canPasteClipboard($arrClipboard, array('pid' => $id, 'sorting' => 0)))
							{
								$operations->addPasteButton('pasteinto', $table, null);
							}
							else
							{
								$operations->addPasteButton('pasteinto', $table, $this->addToUrl('act=' . $arrClipboard['mode'] . '&amp;mode=2&amp;pid=' . $id . (!\is_array($arrClipboard['id']) ? '&amp;id=' . $arrClipboard['id'] : '')));
							}
						}
					}
				}
			}

			$_buttons .= $operations;

			if ($this->strTable == $table && $this->strPickerFieldType)
			{
				$_buttons .= $this->getPickerInputField($id);
			}
		}

		// Add the records of the table itself
		$isTreeMode = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) === self::MODE_TREE;
		$isCurrentTable = $table === $this->strTable;

		$parameters = array(
			'id' => "{$node}_$id",
			'level' => $intMargin / $intSpacing + 1,
			'is_draft' => (string) ($currentRecord['tstamp'] ?? null) === '0',
			'is_group' => ($isTreeMode && ($currentRecord['type'] ?? null) === 'root') || !$isCurrentTable,
			'is_expanded' => $blnIsOpen,
			'enable_deeplink' => $isCurrentTable,
			'toggler_url' => !empty($children) ? $this->addToUrl('ptg=' . $id) : null,
			'records' => array(),
			'children' => array(),
		);

		if ($table != $this->strTable)
		{
			// Also apply the filter settings to the child table (see #716)
			if (!empty($this->procedure) && ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_TREE_EXTENDED)
			{
				$arrValues = $this->values;
				array_unshift($arrValues, $id);

				$objChildren = $db
					->prepare("SELECT id FROM " . $this->strTable . " WHERE pid=? AND " . implode(' AND ', $this->procedure) . ($blnHasSorting ? " ORDER BY sorting, id" : ''))
					->execute(...$arrValues);
			}
			else
			{
				$objChildren = $db
					->prepare("SELECT id FROM " . $this->strTable . " WHERE pid=?" . ($blnHasSorting ? " ORDER BY sorting, id" : ''))
					->execute($id);
			}

			if ($objChildren->numRows)
			{
				$ids = $objChildren->fetchEach('id');

				static::preloadCurrentRecords($ids, $this->strTable);

				for ($j=0, $c=\count($ids); $j<$c; $j++)
				{
					$parameters['records'][] = $this->generateTree($this->strTable, $ids[$j], array('pp'=>($ids[$j - 1] ?? null), 'nn'=>($ids[$j + 1] ?? null)), $blnHasSorting, $intMargin + $intSpacing, $arrClipboard, false, $j<(\count($ids)-1) || !empty($children), $blnNoRecursion, $arrFound);
				}
			}
		}

		// Begin a new submenu
		if (!$blnNoRecursion && $blnIsOpen && !empty($children))
		{
			static::preloadCurrentRecords($children, $table);
			$clipboardManager = System::getContainer()->get('contao.data_container.clipboard_manager');

			// Add the records of the parent table
			for ($k=0, $c=\count($children); $k<$c; $k++)
			{
				$parameters['children'][] = $this->generateTree($table, $children[$k], array('p'=>($children[$k - 1] ?? null), 'n'=>($children[$k + 1] ?? null)), $blnHasSorting, $intMargin + $intSpacing, $arrClipboard, $blnCircularReference || $clipboardManager->isCircularReference($table, $children[$k]), $blnProtected || $protectedPage, $blnNoRecursion, $arrFound);
			}
		}

		$parameters['label'] = $this->generateRecordLabel($currentRecord, $table, $blnProtected, $isVisibleRootTrailPage);
		$parameters['buttons'] = $_buttons;

		$objSessionBag->replace($session);

		return System::getContainer()
			->get('twig')
			->render(
				'@Contao/backend/data_container/table/view/tree_records.html.twig',
				$parameters
			);
	}

	/**
	 * Show header of the parent table and list all records of the current table
	 *
	 * @return string
	 */
	protected function parentView()
	{
		$table = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_TREE_EXTENDED ? $this->ptable : $this->strTable;
		$blnHasSorting = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['fields'][0] ?? null) == 'sorting';
		$blnIsSortable = $blnHasSorting && !($GLOBALS['TL_DCA'][$this->strTable]['config']['notSortable'] ?? null) && Input::get('act') != 'select';

		$arrClipboard = System::getContainer()->get('contao.data_container.clipboard_manager')->get($this->strTable);
		$blnClipboard = null !== $arrClipboard;
		$blnMultiboard = null !== $arrClipboard && \is_array($arrClipboard['id'] ?? null);

		// Load the language file and data container array of the parent table
		System::loadLanguageFile($this->ptable);
		$this->loadDataContainer($this->ptable);

		$labelCut = $GLOBALS['TL_LANG'][$this->strTable]['cut'] ?? $GLOBALS['TL_LANG']['DCA']['cut'];

		$db = Database::getInstance();
		$security = System::getContainer()->get('security.helper');
		$operations = System::getContainer()->get('contao.data_container.global_operations_builder')->initialize($this->strTable);

		if (!Input::get('nb'))
		{
			if (Input::get('act') == 'select')
			{
				$operations->addBackButton($this->getReferer(true, $this->ptable));
			}
			elseif (isset($GLOBALS['TL_DCA'][$this->strTable]['config']['backlink']))
			{
				$operations->addBackButton($GLOBALS['TL_DCA'][$this->strTable]['config']['backlink']);
			}
			elseif ($this->ptable)
			{
				$operations->addBackButton($this->getReferer(true, $this->ptable));
			}
		}

		if (Input::get('act') != 'select' && !$blnClipboard && !($GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] ?? null) && !($GLOBALS['TL_DCA'][$this->strTable]['config']['notCreatable'] ?? null) && $security->isGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new CreateAction($this->strTable, $this->addDynamicPtable(array('pid' => $this->intCurrentPid)))))
		{
			if ($blnHasSorting)
			{
				$operations->addNewButton($operations::CREATE_PASTE);
			}
			else
			{
				$operations->addNewButton($operations::CREATE_INTO, $this->intId);
			}
		}

		if ($blnClipboard)
		{
			$operations->addClearClipboardButton();
		}
		elseif (null !== ($buttons = $this->generateGlobalButtons($operations)))
		{
			trigger_deprecation('contao/core-bundle', '5.6', 'Overriding DataContainer::generateGlobalButtons() is deprecated and will no longer work in Contao 6.');

			$operations->append(array('html' => $buttons), true);
		}

		$operations->addFilterButton();

		// Get all details of the parent record
		$objParent = $db
			->prepare("SELECT * FROM " . $this->ptable . " WHERE id=?")
			->limit(1)
			->execute($this->intCurrentPid);

		if (!$objParent->numRows)
		{
			throw new ResponseException(new Response(\sprintf('No parent record with ID "%s" exists in "%s".', $this->intCurrentPid, $this->ptable), Response::HTTP_NOT_FOUND));
		}

		$security = System::getContainer()->get('security.helper');

		$parameters = array(
			'is_sortable' => $blnIsSortable,
			'has_clipboard_content' => $blnClipboard,
			'panel_active' => $this->panelActive,
		);

		if (Input::get('act') != 'select' && $this->strPickerFieldType != 'checkbox')
		{
			if ($blnClipboard)
			{
				$headerOperations = System::getContainer()->get('contao.data_container.operations_builder')->initialize($this->strTable);
				$headerOperations->addPasteButton('pastetop', $table, $this->addToUrl('act=' . $arrClipboard['mode'] . '&amp;mode=2&amp;pid=' . $objParent->id . (!$blnMultiboard ? '&amp;id=' . $arrClipboard['id'] : '')));
			}
			else
			{
				$headerOperations = $this->generateHeaderButtons($objParent->row(), $this->ptable);

				if ($blnHasSorting && !($GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] ?? null) && !($GLOBALS['TL_DCA'][$this->strTable]['config']['notCreatable'] ?? null) && $security->isGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new CreateAction($this->strTable, $this->addDynamicPtable(array('pid' => $objParent->id, 'sorting' => 0)))))
				{
					$headerOperations->addSeparator();
					$headerOperations->addNewButton($operations::CREATE_TOP, $table, $objParent->id, $this->intId);
				}
			}

			$parameters['header_operations'] = $headerOperations;
		}

		// Format header fields
		$add = array();
		$headerFields = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['headerFields'];
		$valueFormatter = System::getContainer()->get('contao.data_container.value_formatter');

		foreach ($headerFields as $v)
		{
			$_v = $valueFormatter->format($this->ptable, $v, $objParent->$v, $this);

			// Add the sorting field
			if ($_v)
			{
				if (isset($GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['label']))
				{
					$key = \is_array($GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['label']) ? $GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['label'][0] : $GLOBALS['TL_DCA'][$this->ptable]['fields'][$v]['label'];
				}
				else
				{
					$key = $GLOBALS['TL_LANG'][$this->ptable][$v][0] ?? $v;
				}

				$add[$key] = $_v;
			}
		}

		// Trigger the header_callback (see #3417)
		if (\is_array($GLOBALS['TL_DCA'][$table]['list']['sorting']['header_callback'] ?? null))
		{
			$add = System::importStatic($GLOBALS['TL_DCA'][$table]['list']['sorting']['header_callback'][0])->{$GLOBALS['TL_DCA'][$table]['list']['sorting']['header_callback'][1]}($add, $this);
		}
		elseif (\is_callable($GLOBALS['TL_DCA'][$table]['list']['sorting']['header_callback'] ?? null))
		{
			$add = $GLOBALS['TL_DCA'][$table]['list']['sorting']['header_callback']($add, $this);
		}

		$parameters['table_headers'] = array_map(static fn ($v) => \is_array($v) ? $v[0] : $v, $add);

		$orderBy = array();
		$firstOrderBy = array();

		// Add all records of the current table
		$query = "SELECT * FROM " . $this->strTable;

		if (\is_array($this->orderBy) && isset($this->orderBy[0]))
		{
			$orderBy = $this->orderBy;
			$firstOrderBy = preg_replace('/\s+.*$/', '', $orderBy[0]);

			// Order by the foreign key
			if (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$firstOrderBy]['foreignKey']))
			{
				$key = explode('.', $GLOBALS['TL_DCA'][$this->strTable]['fields'][$firstOrderBy]['foreignKey'], 2);
				$orderBy[0] = "(SELECT " . Database::quoteIdentifier($key[1]) . " FROM " . $key[0] . " WHERE " . $this->strTable . "." . Database::quoteIdentifier($firstOrderBy) . "=" . $key[0] . ".id)";
			}
		}
		elseif (\is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['fields'] ?? null))
		{
			$orderBy = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['fields'];
			$firstOrderBy = preg_replace('/\s+.*$/', '', $orderBy[0]);
		}

		$arrProcedure = $this->procedure;
		$arrValues = $this->values;

		if ($GLOBALS['TL_DCA'][$this->strTable]['config']['dynamicPtable'] ?? null)
		{
			$arrProcedure[] = 'ptable=?';
			$arrValues[] = $this->ptable;
		}

		// WHERE
		if (!empty($arrProcedure))
		{
			$query .= " WHERE " . implode(' AND ', $arrProcedure);
		}

		if (!empty($this->root) && \is_array($this->root))
		{
			$query .= (!empty($arrProcedure) ? " AND " : " WHERE ") . "id IN(" . implode(',', array_map('\intval', $this->root)) . ")";
		}

		// ORDER BY
		if (!empty($orderBy) && \is_array($orderBy))
		{
			foreach ($orderBy as $k=>$v)
			{
				if (\in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$v]['flag'] ?? null, array(self::SORT_INITIAL_LETTER_DESC, self::SORT_INITIAL_LETTERS_DESC, self::SORT_DAY_DESC, self::SORT_MONTH_DESC, self::SORT_YEAR_DESC, self::SORT_DESC)))
				{
					$orderBy[$k] .= ' DESC';
				}
			}

			$query .= " ORDER BY " . implode(', ', $orderBy) . ', id';
		}

		$objOrderByStmt = $db->prepare($query);

		// LIMIT
		if ($this->limit)
		{
			$arrLimit = explode(',', $this->limit) + array(null, null);
			$objOrderByStmt->limit($arrLimit[1], $arrLimit[0]);
		}

		$objOrderBy = $objOrderByStmt->execute(...$arrValues);
		$records = array();

		if ($objOrderBy->numRows)
		{
			// Render the child records
			$strGroup = '';
			$blnIndent = false;
			$intWrapLevel = 0;
			$row = $objOrderBy->fetchAllAssoc();

			for ($i=0, $c=\count($row); $i<$c; $i++)
			{
				// Improve performance
				static::setCurrentRecordCache($row[$i]['id'], $this->strTable, $row[$i]);

				$this->denyAccessUnlessGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new ReadAction($this->strTable, $row[$i]));

				$this->current[] = $row[$i]['id'];

				$record = array(
					'id' => $row[$i]['id'],
					'is_draft' => (string) ($row[$i]['tstamp'] ?? null) === '0',
				);

				if ($this->strPickerFieldType)
				{
					$record['picker_input_field'] = $this->getPickerInputField($row[$i]['id']);
				}

				// Add the group header
				if ($firstOrderBy != 'sorting' && !($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['disableGrouping'] ?? null))
				{
					$sortingMode = (\count($orderBy) == 1 && $firstOrderBy == $orderBy[0] && ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['flag'] ?? null) && !($GLOBALS['TL_DCA'][$this->strTable]['fields'][$firstOrderBy]['flag'] ?? null)) ? $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['flag'] : ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$firstOrderBy]['flag'] ?? null);
					$remoteNew = $this->formatCurrentValue($firstOrderBy, $row[$i][$firstOrderBy], $sortingMode);
					$group = $this->formatGroupHeader($firstOrderBy, $remoteNew, $sortingMode, $row[$i]);

					if ($group != $strGroup)
					{
						$record['group_header'] = $group;
						$strGroup = $group;
					}
				}

				if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['renderAsGrid'] ?? false)
				{
					$blnWrapperStart = isset($row[$i]['type']) && \in_array($row[$i]['type'], $GLOBALS['TL_WRAPPERS']['start']);
					$blnWrapperSeparator = isset($row[$i]['type']) && \in_array($row[$i]['type'], $GLOBALS['TL_WRAPPERS']['separator']);
					$blnWrapperStop = isset($row[$i]['type']) && \in_array($row[$i]['type'], $GLOBALS['TL_WRAPPERS']['stop']);
					$blnIndentFirst = isset($row[$i - 1]['type']) && \in_array($row[$i - 1]['type'], $GLOBALS['TL_WRAPPERS']['start']);
					$blnIndentLast = isset($row[$i + 1]['type']) && \in_array($row[$i + 1]['type'], $GLOBALS['TL_WRAPPERS']['stop']);

					// Closing wrappers
					if ($blnWrapperStop && --$intWrapLevel < 1)
					{
						$blnIndent = false;
					}

					$record['display'] = array(
						'wrapper_start' => $blnWrapperStart,
						'wrapper_separator' => $blnWrapperSeparator,
						'wrapper_stop' => $blnWrapperStop,
						'wrap_level' => $blnIndent ? $intWrapLevel : null,
						'indent_first' => $blnIndentFirst,
						'indent_last' => $blnIndentLast,
					);

					// Opening wrappers
					if ($blnWrapperStart && ++$intWrapLevel > 0)
					{
						$blnIndent = true;
					}
				}

				$record['class'] = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['child_record_class'] ?? '';

				if (Input::get('act') != 'select')
				{
					$recordOperations = $this->generateButtons($row[$i], $this->strTable, $this->root, false, null, $row[$i - 1]['id'] ?? null, $row[$i + 1]['id'] ?? null);

					// Sortable table
					if ($blnHasSorting)
					{
						// Prevent circular references
						if ($blnClipboard && !System::getContainer()->get('contao.data_container.clipboard_manager')->canPasteAfterOrInto($this->strTable, $row[$i]['id']))
						{
							$recordOperations->addSeparator();
							$recordOperations->addPasteButton('pasteafter', $table, null);
						}

						// Copy/move multiple
						elseif ($blnMultiboard)
						{
							$recordOperations->addSeparator();
							$recordOperations->addPasteButton('pasteafter', $table, $this->addToUrl('act=' . $arrClipboard['mode'] . '&amp;mode=1&amp;pid=' . $row[$i]['id']));
						}

						// Paste buttons
						elseif ($blnClipboard)
						{
							$recordOperations->addSeparator();
							$recordOperations->addPasteButton('pasteafter', $table, $this->addToUrl('act=' . $arrClipboard['mode'] . '&amp;mode=1&amp;pid=' . $row[$i]['id'] . '&amp;id=' . $arrClipboard['id']));
						}

						// Create new button
						elseif (!($GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] ?? null) && !($GLOBALS['TL_DCA'][$this->strTable]['config']['notCreatable'] ?? null) && $security->isGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new CreateAction($this->strTable, $this->addDynamicPtable(array('pid' => $row[$i]['pid'], 'sorting' => $row[$i]['sorting'] + 1)))))
						{
							$recordOperations->addSeparator();
							$recordOperations->addNewButton($operations::CREATE_AFTER, $this->strTable, $row[$i]['id'], $objParent->id);
						}

						// Backwards compatibility: Drag handle in case the child_record_callback is used and no leftside handle is output (to be removed in Contao 6)
						if ($blnIsSortable && $security->isGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new UpdateAction($this->strTable, $row[$i])))
						{
							$recordOperations->append(array('primary' => true, 'html'=>'<button type="button" class="drag-handle" data-action="keydown->contao--sortable#move" style="display:none">' . Image::getHtml('drag.svg', \sprintf(\is_array($labelCut) ? $labelCut[1] : $labelCut, $row[$i]['id'])) . '</button>'));
						}
					}

					$record['operations'] = $recordOperations;
				}

				if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['child_record_callback'] ?? null))
				{
					trigger_deprecation('contao/core-bundle', '5.7', 'Using the child_record_callback is deprecated and will no longer work in Contao 6. Use the label_callback instead.');

					$strClass = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['child_record_callback'][0];
					$strMethod = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['child_record_callback'][1];

					$record['legacy_data'] = System::importStatic($strClass)->$strMethod($row[$i]);
				}
				elseif (\is_callable($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['child_record_callback'] ?? null))
				{
					trigger_deprecation('contao/core-bundle', '5.7', 'Using the child_record_callback is deprecated and will no longer work in Contao 6. Use the label_callback instead.');

					$record['legacy_data'] = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['child_record_callback']($row[$i]);
				}
				else
				{
					$label = $this->generateRecordLabel($row[$i]);

					$record['label'] = \is_array($label) ? ($label[0] ?? '') : $label;
					$record['preview'] = \is_array($label) ? ($label[1] ?? '') : '';
					$record['state'] = \is_array($label) ? ($label[2] ?? '') : '';

					$record['allow_dragging'] = $blnIsSortable && System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new UpdateAction($this->strTable, $row[$i]));

					if ($record['allow_dragging'])
					{
						$labelCut = $GLOBALS['TL_LANG'][$this->strTable]['cut'] ?? $GLOBALS['TL_LANG']['DCA']['cut'];
						$record['drag_handle_label'] = \sprintf(\is_array($labelCut) ? $labelCut[1] : $labelCut, $row[$i]['id']);
					}
				}

				$records[] = $record;
			}
		}

		$parameters['records'] = $records;

		// Add pagination
		if (str_contains($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['panelLayout'] ?? '', 'limit'))
		{
			$parameters['pagination'] = $this->paginationMenu();
		}

		// Add select buttons
		if (Input::get('act') == 'select')
		{
			$parameters['buttons'] = System::getContainer()->get('contao.data_container.buttons_builder')->generateSelectButtons($this->strTable, true, $this);
		}

		$parameters['message'] = Message::generate();
		$parameters['global_operations'] = $operations;

		return $this->render('view/parent', $parameters);
	}

	/**
	 * List all records of the current table and return them as HTML string
	 *
	 * @return string
	 */
	protected function listView()
	{
		$table = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_TREE_EXTENDED ? $this->ptable : $this->strTable;
		$orderBy = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['fields'] ?? array('id');
		$firstOrderBy = preg_replace('/\s+.*$/', '', $orderBy[0]);

		if (\is_array($this->orderBy) && !empty($this->orderBy[0]))
		{
			$orderBy = $this->orderBy;
			$firstOrderBy = $this->firstOrderBy;
		}

		$query = "SELECT * FROM " . $this->strTable;

		if (!empty($this->procedure))
		{
			$query .= " WHERE " . implode(' AND ', $this->procedure);
		}

		if (!empty($this->root) && \is_array($this->root))
		{
			$query .= (!empty($this->procedure) ? " AND " : " WHERE ") . "id IN(" . implode(',', array_map('\intval', $this->root)) . ")";
		}

		$db = Database::getInstance();

		if (\is_array($orderBy) && $orderBy[0])
		{
			foreach ($orderBy as $k=>$v)
			{
				list($key, $direction) = explode(' ', $v, 2) + array(null, null);

				$orderBy[$k] = $key;

				// If there is no direction, check the global flag in sorting mode 1 or the field flag in all other sorting modes
				if (!$direction)
				{
					if (($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_SORTED && \in_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['flag'] ?? null, array(self::SORT_INITIAL_LETTER_DESC, self::SORT_INITIAL_LETTERS_DESC, self::SORT_DAY_DESC, self::SORT_MONTH_DESC, self::SORT_YEAR_DESC, self::SORT_DESC)))
					{
						$direction = 'DESC';
					}
					elseif (\in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$key]['flag'] ?? null, array(self::SORT_INITIAL_LETTER_DESC, self::SORT_INITIAL_LETTERS_DESC, self::SORT_DAY_DESC, self::SORT_MONTH_DESC, self::SORT_YEAR_DESC, self::SORT_DESC)))
					{
						$direction = 'DESC';
					}
				}

				if (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$key]['foreignKey']))
				{
					$chunks = explode('.', $GLOBALS['TL_DCA'][$this->strTable]['fields'][$key]['foreignKey'], 2);
					$orderBy[$k] = "(SELECT " . Database::quoteIdentifier($chunks[1]) . " FROM " . $chunks[0] . " WHERE " . $chunks[0] . ".id=" . $this->strTable . "." . $key . ")";
				}

				if (\in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$key]['flag'] ?? null, array(self::SORT_DAY_ASC, self::SORT_DAY_DESC, self::SORT_DAY_BOTH, self::SORT_MONTH_ASC, self::SORT_MONTH_DESC, self::SORT_MONTH_BOTH, self::SORT_YEAR_ASC, self::SORT_YEAR_DESC, self::SORT_YEAR_BOTH)))
				{
					$orderBy[$k] = "CAST(" . $orderBy[$k] . " AS SIGNED)"; // see #5503
				}

				if ($direction)
				{
					$orderBy[$k] .= ' ' . $direction;
				}

				if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$key]['eval']['findInSet'] ?? null)
				{
					if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$key]['options_callback'] ?? null))
					{
						$strClass = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$key]['options_callback'][0];
						$strMethod = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$key]['options_callback'][1];

						$keys = System::importStatic($strClass)->$strMethod($this);
					}
					elseif (\is_callable($GLOBALS['TL_DCA'][$this->strTable]['fields'][$key]['options_callback'] ?? null))
					{
						$keys = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$key]['options_callback']($this);
					}
					else
					{
						$keys = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$key]['options'] ?? array();
					}

					if (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$key]['eval']['isAssociative'] ?? null) || ArrayUtil::isAssoc($keys))
					{
						$keys = array_keys($keys);
					}

					$orderBy[$k] = $db->findInSet($v, $keys);
				}
			}

			if (($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_SORTED_PARENT)
			{
				$firstOrderBy = 'pid';
				$showFields = $GLOBALS['TL_DCA'][$table]['list']['label']['fields'];

				$query .= " ORDER BY (SELECT " . Database::quoteIdentifier($showFields[0]) . " FROM " . $this->ptable . " WHERE " . $this->ptable . ".id=" . $this->strTable . ".pid), " . implode(', ', $orderBy) . ', id';

				// Set the foreignKey so that the label is translated
				if (!($GLOBALS['TL_DCA'][$table]['fields']['pid']['foreignKey'] ?? null))
				{
					$GLOBALS['TL_DCA'][$table]['fields']['pid']['foreignKey'] = $this->ptable . '.' . $showFields[0];
				}

				// Remove the parent field from label fields
				array_shift($showFields);
				$GLOBALS['TL_DCA'][$table]['list']['label']['fields'] = $showFields;
			}
			else
			{
				$query .= " ORDER BY " . implode(', ', $orderBy) . ', id';
			}
		}

		$objRowStmt = $db->prepare($query);

		if ($this->limit)
		{
			$arrLimit = explode(',', $this->limit) + array(null, null);
			$objRowStmt->limit($arrLimit[1], $arrLimit[0]);
		}

		$objRow = $objRowStmt->execute(...$this->values);
		$security = System::getContainer()->get('security.helper');

		// Display buttons
		$operations = System::getContainer()->get('contao.data_container.global_operations_builder')->initialize($this->strTable);

		if (Input::get('act') == 'select')
		{
			$operations->addBackButton($this->getReferer(true, $this->ptable));
		}
		elseif (isset($GLOBALS['TL_DCA'][$this->strTable]['config']['backlink']))
		{
			$operations->addBackButton($GLOBALS['TL_DCA'][$this->strTable]['config']['backlink']);
		}
		elseif ($this->ptable)
		{
			$operations->addBackButton($this->getReferer(true, $this->ptable));
		}

		if (Input::get('act') != 'select' && !($GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] ?? null) && !($GLOBALS['TL_DCA'][$this->strTable]['config']['notCreatable'] ?? null) && $security->isGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new CreateAction($this->strTable)))
		{
			if ($this->ptable)
			{
				$operations->addNewButton(($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) < self::MODE_PARENT ? $operations::CREATE_TOP : $operations::CREATE_NEW, $this->intId);
			}
			else
			{
				$operations->addNewButton($operations::CREATE_NEW);
			}
		}

		if (null !== ($buttons = $this->generateGlobalButtons($operations)))
		{
			trigger_deprecation('contao/core-bundle', '5.6', 'Overriding DataContainer::generateGlobalButtons() is deprecated and will no longer work in Contao 6.');

			$operations->append(array('html' => $buttons), true);
		}

		$operations->addFilterButton();

		$parameters = array();
		$records = array();

		if ($objRow->numRows)
		{
			$result = $objRow->fetchAllAssoc();

			// Automatically add the "order by" field as last column if we do not have group headers
			if (($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['showColumns'] ?? null) && false !== ($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['showFirstOrderBy'] ?? null))
			{
				$blnFound = false;

				// Extract the real key and compare it to $firstOrderBy
				foreach ($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['fields'] as $f)
				{
					if (str_contains($f, ':'))
					{
						list($f) = explode(':', $f, 2);
					}

					if ($firstOrderBy == $f)
					{
						$blnFound = true;
						break;
					}
				}

				if (!$blnFound)
				{
					$GLOBALS['TL_DCA'][$this->strTable]['list']['label']['fields'][] = $firstOrderBy;
				}
			}

			// Generate the table header if the "show columns" option is active
			$parameters['table_headers'] = array();

			if ($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['showColumns'] ?? null)
			{
				foreach ($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['fields'] as $f)
				{
					if (str_contains($f, ':'))
					{
						list($f) = explode(':', $f, 2);
					}

					$parameters['table_headers'][$f] = \is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$f]['label'] ?? null)
						? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$f]['label'][0]
						: ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$f]['label'] ?? $f)
					;
				}
			}

			// Process result and add label and buttons
			$remoteCur = false;

			foreach ($result as $row)
			{
				// Improve performance for $dc->getCurrentRecord($id);
				static::setCurrentRecordCache($row['id'], $this->strTable, $row);

				$this->denyAccessUnlessGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new ReadAction($this->strTable, $row));

				$record = array(
					'id' => $row['id'],
					'is_draft' => (string) ($row['tstamp'] ?? null) === '0',
					'operations' => $this->generateButtons($row, $this->strTable, $this->root),
				);

				if ($this->strPickerFieldType)
				{
					$record['picker_input_field'] = $this->getPickerInputField($row['id']);
				}

				$this->current[] = $row['id'];
				$label = $this->generateRecordLabel($row, $this->strTable);

				// Build the sorting groups
				if (($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) > 0)
				{
					$current = $row[$firstOrderBy];
					$orderBy = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['fields'] ?? array('id');
					$sortingMode = (\count($orderBy) == 1 && $firstOrderBy == $orderBy[0] && ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['flag'] ?? null) && !($GLOBALS['TL_DCA'][$this->strTable]['fields'][$firstOrderBy]['flag'] ?? null)) ? $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['flag'] : ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$firstOrderBy]['flag'] ?? null);
					$remoteNew = $this->formatCurrentValue($firstOrderBy, $current, $sortingMode);

					// Add the group header
					if (($remoteNew != $remoteCur || $remoteCur === false) && !($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['showColumns'] ?? null) && !($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['disableGrouping'] ?? null))
					{
						$group = $this->formatGroupHeader($firstOrderBy, $remoteNew, $sortingMode, $row);
						$remoteCur = $remoteNew;

						$record['group_header'] = $group;
					}
				}

				if ($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['showColumns'] ?? false)
				{
					$colspan = 1;

					if (!\is_array($label))
					{
						$label = array($label);
						$colspan = \count($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['fields'] ?? array());
					}

					$record['columns'] = array();
					$record['columns_colspan'] = $colspan;

					foreach ($label as $j=>$arg)
					{
						$field = $GLOBALS['TL_DCA'][$this->strTable]['list']['label']['fields'][$j] ?? null;
						$name = explode(':', $field, 2)[0];
						$value = (string) $arg !== '' ? $arg : '-';

						$record['columns'][$name] = $value;
					}
				}
				else
				{
					$record['label'] = \is_array($label) ? implode(' ', $label) : $label;
				}

				$records[] = $record;
			}

			// Add pagination
			if (str_contains($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['panelLayout'] ?? '', 'limit'))
			{
				$parameters['pagination'] = $this->paginationMenu();
			}

			// Add select buttons
			if (Input::get('act') == 'select')
			{
				$parameters['buttons'] = System::getContainer()->get('contao.data_container.buttons_builder')->generateSelectButtons($this->strTable, false, $this);
			}
		}

		$parameters['message'] = Message::generate();
		$parameters['global_operations'] = $operations;
		$parameters['panel_active'] = $this->panelActive;
		$parameters['records'] = $records;
		$parameters['order_by'] = $firstOrderBy;
		$parameters['show_columns'] = $GLOBALS['TL_DCA'][$this->strTable]['list']['label']['showColumns'] ?? false;

		return $this->render('view/list', $parameters);
	}

	/**
	 * Return a search form that allows to search results using regular expressions
	 *
	 * @return string
	 */
	protected function searchMenu()
	{
		$searchFields = array('id');

		$objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');
		$session = $objSessionBag->all();

		// Get search fields
		foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'] as $k=>$v)
		{
			if ($v['search'] ?? null)
			{
				$searchFields[] = $k;
			}
		}

		// Return if there are no search fields
		if (empty($searchFields))
		{
			return '';
		}

		// Store search value in the current session
		if (Input::post('FORM_SUBMIT') == 'tl_filters')
		{
			$strField = Input::post('tl_search', true);
			$strKeyword = ltrim(Input::postRaw('tl_value'), '*');

			if ($strField && !\in_array($strField, $searchFields, true))
			{
				$strField = '';
				$strKeyword = '';
			}

			$session['search'][$this->strTable]['field'] = $strField;
			$session['search'][$this->strTable]['value'] = $strKeyword;

			$objSessionBag->replace($session);
		}

		// Set the search value from the session
		elseif (isset($session['search'][$this->strTable]['value']) && (string) $session['search'][$this->strTable]['value'] !== '')
		{
			$searchValue = $session['search'][$this->strTable]['value'];
			$fld = $session['search'][$this->strTable]['field'] ?? null;

			try
			{
				Database::getInstance()->prepare("SELECT '' REGEXP ?")->execute($searchValue);
			}
			catch (DriverException $exception)
			{
				// Quote search string if it is not a valid regular expression
				$searchValue = preg_quote($searchValue, null);
			}

			$strReplacePrefix = '';
			$strReplaceSuffix = '';

			// Decode HTML entities to make them searchable
			if (empty($GLOBALS['TL_DCA'][$this->strTable]['fields'][$fld]['eval']['decodeEntities']))
			{
				$arrReplace = array(
					'&#35;' => '#',
					'&#60;' => '<',
					'&#62;' => '>',
					'&lt;' => '<',
					'&gt;' => '>',
					'&#40;' => '(',
					'&#41;' => ')',
					'&#92;' => '\\\\',
					'&#61;' => '=',
					'&amp;' => '&',
				);

				$strReplacePrefix = str_repeat('REPLACE(', \count($arrReplace));

				foreach ($arrReplace as $strSource => $strTarget)
				{
					$strReplaceSuffix .= ", '$strSource', '$strTarget')";
				}
			}

			$strPattern = "$strReplacePrefix LOWER(CAST(%s AS CHAR)) $strReplaceSuffix REGEXP LOWER(?)";

			if (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$fld]['foreignKey']))
			{
				list($t, $f) = explode('.', $GLOBALS['TL_DCA'][$this->strTable]['fields'][$fld]['foreignKey'], 2);
				$this->procedure[] = "(" . \sprintf($strPattern, Database::quoteIdentifier($fld)) . " OR " . \sprintf($strPattern, "(SELECT " . Database::quoteIdentifier($f) . " FROM $t WHERE $t.id=" . $this->strTable . "." . Database::quoteIdentifier($fld) . ")") . ")";
				$this->values[] = $searchValue;
			}
			else
			{
				$this->procedure[] = \sprintf($strPattern, Database::quoteIdentifier($fld));
			}

			$this->values[] = $searchValue;
		}

		$options_sorter = array();

		foreach ($searchFields as $field)
		{
			$option_label = $field;

			if (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label']))
			{
				$option_label = \is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label']) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label'][0] : $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label'];
			}
			elseif (isset($GLOBALS['TL_LANG']['MSC'][$field]))
			{
				$option_label = \is_array($GLOBALS['TL_LANG']['MSC'][$field]) ? $GLOBALS['TL_LANG']['MSC'][$field][0] : $GLOBALS['TL_LANG']['MSC'][$field];
			}

			$options_sorter[$option_label . '_' . $field] = array(
				'label' => $option_label,
				'value' => $field,
				'selected' => ($session['search'][$this->strTable]['field'] ?? $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['defaultSearchField'] ?? null) == $field,
			);
		}

		// Sort by option values
		uksort($options_sorter, static function ($a, $b) {
			$a = (new UnicodeString($a))->folded();
			$b = (new UnicodeString($b))->folded();

			if ($a->toString() === $b->toString())
			{
				return 0;
			}

			return strnatcmp($a->ascii()->toString(), $b->ascii()->toString());
		});

		$active = isset($session['search'][$this->strTable]['value']) && (string) $session['search'][$this->strTable]['value'] !== '';

		$this->setPanelState($active);

		return System::getContainer()
			->get('twig')
			->render('@Contao/backend/data_container/table/menu/search.html.twig', array(
				'options' => array_values($options_sorter),
				'active' => $active,
				'value' => $session['search'][$this->strTable]['value'] ?? '',
			))
		;
	}

	/**
	 * Return a select menu that allows to sort results by a particular field
	 *
	 * @return string
	 */
	protected function sortMenu()
	{
		if (($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) != self::MODE_SORTABLE && ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) != self::MODE_PARENT)
		{
			return '';
		}

		$sortingFields = array();

		// Get sorting fields
		foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'] as $k=>$v)
		{
			if ($v['sorting'] ?? null)
			{
				if (\in_array($v['flag'] ?? null, array(self::SORT_INITIAL_LETTER_BOTH, self::SORT_INITIAL_LETTERS_BOTH, self::SORT_DAY_BOTH, self::SORT_MONTH_BOTH, self::SORT_YEAR_BOTH, self::SORT_BOTH)))
				{
					$sortingFields[] = $k . ' DESC';
					$sortingFields[] = $k . ' ASC';
				}
				else
				{
					$sortingFields[] = $k;
				}
			}
		}

		// Return if there are no sorting fields
		if (empty($sortingFields))
		{
			return '';
		}

		$objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');
		$session = $objSessionBag->all();

		$orderBy = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['fields'] ?? array('id');
		$firstOrderBy = preg_replace('/\s+.*$/', '', $orderBy[0]);

		// Add PID to order fields
		if (($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_SORTED_PARENT && Database::getInstance()->fieldExists('pid', $this->strTable))
		{
			array_unshift($orderBy, 'pid');
		}

		// Set sorting from user input
		if (Input::post('FORM_SUBMIT') == 'tl_filters')
		{
			$strSort = Input::post('tl_sort');

			// Validate the user input (thanks to aulmn) (see #4971)
			if (\in_array($strSort, $sortingFields, true))
			{
				$session['sorting'][$this->strTable] = \in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$strSort]['flag'] ?? null, array(self::SORT_INITIAL_LETTER_DESC, self::SORT_INITIAL_LETTERS_DESC, self::SORT_DAY_DESC, self::SORT_MONTH_DESC, self::SORT_YEAR_DESC, self::SORT_DESC)) ? "$strSort DESC" : $strSort;
				$objSessionBag->replace($session);
			}
		}

		// Overwrite the "orderBy" value with the session value
		elseif (isset($session['sorting'][$this->strTable]))
		{
			$overwrite = preg_quote(preg_replace('/\s+.*$/', '', $session['sorting'][$this->strTable]), '/');
			$orderBy = array_diff($orderBy, preg_grep('/^' . $overwrite . '/i', $orderBy));

			array_unshift($orderBy, $session['sorting'][$this->strTable]);

			$this->firstOrderBy = $overwrite;
			$this->orderBy = $orderBy;
		}

		$options_sorter = array();

		// Sorting fields
		foreach ($sortingFields as $value)
		{
			$field = str_replace(array(' ASC', ' DESC'), '', $value);
			$options_label = ($lbl = \is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label'] ?? null) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label'][0] : ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label'] ?? null)) ? $lbl : $GLOBALS['TL_LANG']['MSC'][$field] ?? $field;

			if (\is_array($options_label))
			{
				$options_label = $options_label[0];
			}

			if (str_ends_with($value, ' ASC'))
			{
				$sortKey = $options_label . '|ASC';
				$sessionValue = $session['sorting'][$this->strTable] ?? '';
				$order = 'ASC';
			}
			elseif (str_ends_with($value, ' DESC'))
			{
				$sortKey = $options_label . '|DESC';
				$sessionValue = $session['sorting'][$this->strTable] ?? '';
				$order = 'DESC';
			}
			else
			{
				$sortKey = $options_label;
				$sessionValue = str_replace(' DESC', '', $session['sorting'][$this->strTable] ?? '');
				$order = null;
			}

			$options_sorter[$sortKey] = array(
				'label' => $options_label,
				'value' => $value,
				'selected' => (!isset($session['sorting'][$this->strTable]) && $field == $firstOrderBy) || $value == $sessionValue,
				'order' => $order,
			);
		}

		// Sort by option values
		uksort($options_sorter, static function ($a, $b) {
			$a = (new UnicodeString($a))->folded();
			$b = (new UnicodeString($b))->folded();

			if ($a->toString() === $b->toString())
			{
				return 0;
			}

			return strnatcmp($a->ascii()->toString(), $b->ascii()->toString());
		});

		return System::getContainer()
			->get('twig')
			->render('@Contao/backend/data_container/table/menu/sort.html.twig', array(
				'options' => array_values($options_sorter),
			))
		;
	}

	/**
	 * Return a select menu to limit results
	 *
	 * @param boolean $blnOptional
	 *
	 * @return string
	 */
	protected function limitMenu($blnOptional=false)
	{
		$objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');
		$session = $objSessionBag->all();

		$filter = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_PARENT ? $this->strTable . '_' . $this->intCurrentPid : $this->strTable;

		$active = false;
		$options = array();

		// Set limit from user input
		if (\in_array(Input::post('FORM_SUBMIT'), array('tl_filters', 'tl_filters_limit')))
		{
			$strLimit = Input::post('tl_limit');

			if ($strLimit == 'tl_limit')
			{
				unset($session['filter'][$filter]['limit']);
			}
			// Validate the user input (thanks to aulmn) (see #4971)
			elseif ($strLimit == 'all' || preg_match('/^[0-9]+,[0-9]+$/', $strLimit))
			{
				$session['filter'][$filter]['limit'] = $strLimit;
			}

			$objSessionBag->replace($session);

			if (Input::post('FORM_SUBMIT') == 'tl_filters_limit')
			{
				$this->reload();
			}
		}

		// Set limit from table configuration
		else
		{
			$this->limit = isset($session['filter'][$filter]['limit']) ? (($session['filter'][$filter]['limit'] == 'all') ? null : $session['filter'][$filter]['limit']) : '0,' . Config::get('resultsPerPage');

			$arrProcedure = $this->procedure;
			$arrValues = $this->values;
			$query = "SELECT COUNT(*) AS count FROM " . $this->strTable;

			if (!empty($this->root) && \is_array($this->root))
			{
				$arrProcedure[] = 'id IN(' . implode(',', $this->root) . ')';
			}

			if ($GLOBALS['TL_DCA'][$this->strTable]['config']['dynamicPtable'] ?? null)
			{
				$arrProcedure[] = 'ptable=?';
				$arrValues[] = $this->ptable;
			}

			if (!empty($arrProcedure))
			{
				$query .= " WHERE " . implode(' AND ', $arrProcedure);
			}

			$objTotal = Database::getInstance()->prepare($query)->execute(...$arrValues);
			$this->total = $objTotal->count;
			$options_total = 0;
			$maxResultsPerPage = Config::get('maxResultsPerPage');
			$blnIsMaxResultsPerPage = false;

			// Overall limit
			if ($maxResultsPerPage > 0 && $this->total > $maxResultsPerPage && ($this->limit === null || preg_replace('/^.*,/', '', $this->limit) == $maxResultsPerPage))
			{
				if ($this->limit === null)
				{
					$this->limit = '0,' . Config::get('maxResultsPerPage');
				}

				$blnIsMaxResultsPerPage = true;
				Config::set('resultsPerPage', Config::get('maxResultsPerPage'));
				$session['filter'][$filter]['limit'] = Config::get('maxResultsPerPage');
			}

			// Build options
			if ($this->total > 0)
			{
				$options_total = ceil($this->total / Config::get('resultsPerPage'));

				// Reset limit if other parameters have decreased the number of results
				if ($this->limit !== null && (!$this->limit || preg_replace('/,.*$/', '', $this->limit) > $this->total))
				{
					$this->limit = '0,' . Config::get('resultsPerPage');
				}

				// Build options
				for ($i=0; $i<$options_total; $i++)
				{
					$this_limit = ($i*Config::get('resultsPerPage')) . ',' . Config::get('resultsPerPage');
					$upper_limit = $i*Config::get('resultsPerPage')+Config::get('resultsPerPage');

					if ($upper_limit > $this->total)
					{
						$upper_limit = $this->total;
					}

					$options[] = array(
						'label' => ($i * Config::get('resultsPerPage') + 1) . ' - ' . $upper_limit,
						'value' => $this_limit,
						'selected' => $this->limit === $this_limit,
					);
				}

				if (!$blnIsMaxResultsPerPage)
				{
					$options[] = array(
						'label' => $GLOBALS['TL_LANG']['MSC']['filterAll'],
						'value' => 'all',
						'selected' => $this->limit === null,
					);
				}
			}

			// Return if there is only one page
			if ($blnOptional && ($this->total < 1 || $options_total < 2))
			{
				return '';
			}

			$resultsPerPage = Config::get('resultsPerPage');

			$limit = $session['filter'][$filter]['limit'] ?? null;
			$active = $limit != 'all' && $this->total > $resultsPerPage ? ' active' : '';

			// Only disable reset button if it is not on the first page
			if ($limit !== ('0,' . $resultsPerPage) && $limit !== null)
			{
				$this->setPanelState($active);
			}
		}

		return System::getContainer()
			->get('twig')
			->render('@Contao/backend/data_container/table/menu/limit.html.twig', array(
				'active' => $active,
				'options' => $options,
			))
		;
	}

	/**
	 * Generate the filter panel and return it as HTML string
	 *
	 * @param integer $intFilterPanel
	 *
	 * @return string
	 */
	protected function filterMenu($intFilterPanel)
	{
		$objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');

		$sortingFields = array();
		$session = $objSessionBag->all();
		$filter = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_PARENT ? $this->strTable . '_' . $this->intCurrentPid : $this->strTable;

		// Get the sorting fields
		foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'] as $k=>$v)
		{
			if (($v['filter'] ?? null) == $intFilterPanel)
			{
				$sortingFields[] = $k;
			}
		}

		// Return if there are no sorting fields
		if (empty($sortingFields))
		{
			return '';
		}

		$db = Database::getInstance();

		// Set filter from user input
		if (Input::post('FORM_SUBMIT') == 'tl_filters')
		{
			foreach ($sortingFields as $field)
			{
				if (Input::post($field, true) != 'tl_' . $field)
				{
					$session['filter'][$filter][$field] = Input::post($field, true);
				}
				else
				{
					unset($session['filter'][$filter][$field]);
				}
			}

			$objSessionBag->replace($session);
		}

		// Set filter from table configuration
		else
		{
			foreach ($sortingFields as $field)
			{
				$what = Database::quoteIdentifier($field);

				if (isset($session['filter'][$filter][$field]))
				{
					// Sort by day
					if (\in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['flag'] ?? null, array(self::SORT_DAY_ASC, self::SORT_DAY_DESC, self::SORT_DAY_BOTH)))
					{
						if (!$session['filter'][$filter][$field])
						{
							$this->procedure[] = $what . "=''";
						}
						else
						{
							$objDate = new Date($session['filter'][$filter][$field]);
							$this->procedure[] = $what . ' BETWEEN ? AND ?';
							$this->values[] = $objDate->dayBegin;
							$this->values[] = $objDate->dayEnd;
						}
					}

					// Sort by month
					elseif (\in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['flag'] ?? null, array(self::SORT_MONTH_ASC, self::SORT_MONTH_DESC, self::SORT_MONTH_BOTH)))
					{
						if (!$session['filter'][$filter][$field])
						{
							$this->procedure[] = $what . "=''";
						}
						else
						{
							$objDate = new Date($session['filter'][$filter][$field]);
							$this->procedure[] = $what . ' BETWEEN ? AND ?';
							$this->values[] = $objDate->monthBegin;
							$this->values[] = $objDate->monthEnd;
						}
					}

					// Sort by year
					elseif (\in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['flag'] ?? null, array(self::SORT_YEAR_ASC, self::SORT_YEAR_DESC, self::SORT_YEAR_BOTH)))
					{
						if (!$session['filter'][$filter][$field])
						{
							$this->procedure[] = $what . "=''";
						}
						else
						{
							$objDate = new Date($session['filter'][$filter][$field]);
							$this->procedure[] = $what . ' BETWEEN ? AND ?';
							$this->values[] = $objDate->yearBegin;
							$this->values[] = $objDate->yearEnd;
						}
					}

					// Manual filter
					elseif ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['multiple'] ?? null)
					{
						// CSV lists (see #2890)
						if (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['csv']))
						{
							$this->procedure[] = $db->findInSet('?', $field, true);
							$this->values[] = $session['filter'][$filter][$field];
						}
						else
						{
							$this->procedure[] = $what . ' LIKE ?';
							$this->values[] = '%"' . $session['filter'][$filter][$field] . '"%';
						}
					}

					// Other sort algorithm
					else
					{
						$this->procedure[] = $what . '=?';
						$this->values[] = $session['filter'][$filter][$field];
					}
				}
			}
		}

		// Add sorting options
		$filters = array();

		foreach ($sortingFields as $field)
		{
			$arrValues = array();
			$arrProcedure = array();

			if (($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_PARENT)
			{
				$arrProcedure[] = 'pid=?';
				$arrValues[] = $this->intCurrentPid;
			}

			if (!$this->treeView && !empty($this->root) && \is_array($this->root))
			{
				$arrProcedure[] = "id IN(" . implode(',', array_map('\intval', $this->root)) . ")";
			}

			// Check for a static filter (see #4719)
			if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['filter'] ?? null))
			{
				foreach ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['filter'] as $fltr)
				{
					if (\is_string($fltr))
					{
						$arrProcedure[] = $fltr;
					}
					else
					{
						$arrProcedure[] = $fltr[0];
						$arrValues[] = $fltr[1];
					}
				}
			}

			if ($GLOBALS['TL_DCA'][$this->strTable]['config']['dynamicPtable'] ?? null)
			{
				$arrProcedure[] = 'ptable=?';
				$arrValues[] = $this->ptable;
			}

			$what = Database::quoteIdentifier($field);

			// Optimize the SQL query (see #8485)
			if (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['flag']))
			{
				// Sort by day
				if (\in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['flag'], array(self::SORT_DAY_ASC, self::SORT_DAY_DESC, self::SORT_DAY_BOTH)))
				{
					$what = "IF($what!='', FLOOR(UNIX_TIMESTAMP(FROM_UNIXTIME($what , '%Y-%m-%d'))), '') AS $what";
				}

				// Sort by month
				elseif (\in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['flag'], array(self::SORT_MONTH_ASC, self::SORT_MONTH_DESC, self::SORT_MONTH_BOTH)))
				{
					$what = "IF($what!='', FLOOR(UNIX_TIMESTAMP(FROM_UNIXTIME($what , '%Y-%m-01'))), '') AS $what";
				}

				// Sort by year
				elseif (\in_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['flag'], array(self::SORT_YEAR_ASC, self::SORT_YEAR_DESC, self::SORT_YEAR_BOTH)))
				{
					$what = "IF($what!='', FLOOR(UNIX_TIMESTAMP(FROM_UNIXTIME($what , '%Y-01-01'))), '') AS $what";
				}
			}

			$table = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_TREE_EXTENDED ? $this->ptable : $this->strTable;

			// Limit the options if there are root records
			if ($this->root)
			{
				$rootIds = $this->root;

				// Also add the child records of the table (see #1811)
				if (($GLOBALS['TL_DCA'][$table]['list']['sorting']['mode'] ?? null) == self::MODE_TREE)
				{
					$rootIds = array_merge($rootIds, $db->getChildRecords($rootIds, $table));
				}

				if (($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_TREE_EXTENDED)
				{
					$arrProcedure[] = "pid IN(" . implode(',', $rootIds) . ")";
				}
				else
				{
					$arrProcedure[] = "id IN(" . implode(',', $rootIds) . ")";
				}
			}

			$objFields = $db
				->prepare("SELECT DISTINCT " . $what . " FROM " . $this->strTable . ((\is_array($arrProcedure) && isset($arrProcedure[0])) ? ' WHERE ' . implode(' AND ', $arrProcedure) : ''))
				->execute(...$arrValues);

			$active = isset($session['filter'][$filter][$field]);

			$this->setPanelState($active);

			if ($objFields->numRows)
			{
				$valueFormatter = System::getContainer()->get('contao.data_container.value_formatter');
				$options = array_map(
					static fn (array $option): array => array(
						...$option,
						'selected' => isset($session['filter'][$filter][$field]) && $option['value'] == $session['filter'][$filter][$field],
					),
					$valueFormatter->formatFilterOptions($this->strTable, $field, $objFields->fetchEach($field), $this)
				);
			}
			else
			{
				$options = array();
			}

			$filters[] = array(
				'name' => $field,
				'options' => array(
					array(
						'value' => 'tl_' . $field,
						'label' => '-',
						'selected' => false,
					),
					...$options,
				),
				'active' => $active,
				'placeholder' => \is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label'] ?? null) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label'][0] : ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label'] ?? null),
			);
		}

		return System::getContainer()
			->get('twig')
			->render('@Contao/backend/data_container/table/menu/filter.html.twig', array(
				'filters' => $filters,
			))
		;
	}

	/**
	 * Return a pagination menu to browse results
	 *
	 * @return string
	 */
	protected function paginationMenu()
	{
		$objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');
		$session = $objSessionBag->all();

		$filter = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_PARENT ? $this->strTable . '_' . $this->intCurrentPid : $this->strTable;

		list($offset, $limit) = explode(',', $this->limit ?? '') + array(null, null);

		// Set the limit filter based on the page number
		if (Input::get('lp') !== null)
		{
			$lp = (int) Input::get('lp') - 1;

			if ($lp >= 0 && $lp < ceil($this->total / $limit))
			{
				$session['filter'][$filter]['limit'] = ($lp * $limit) . ',' . $limit;
				$objSessionBag->replace($session);
			}

			$this->redirect(preg_replace('/&(amp;)?lp=[^&]+/i', '', Environment::get('requestUri')));
		}

		$paginationConfig = (new PaginationConfig('lp', (int) $this->total, (int) $limit))->withIgnoreOutOfBounds();

		if ($limit)
		{
			$paginationConfig = $paginationConfig->withCurrentPage($offset / $limit + 1);
		}

		$pagination = System::getContainer()->get('contao.pagination.factory')->create($paginationConfig);

		return System::getContainer()->get('twig')->render('@Contao/backend/component/_pagination.html.twig', array('pagination' => $pagination, 'form_submit' => 'tl_pagination'));
	}

	/**
	 * Return the formatted group header as string
	 *
	 * @param string  $field
	 * @param mixed   $value
	 * @param integer $mode
	 *
	 * @return string
	 */
	protected function formatCurrentValue($field, $value, $mode)
	{
		$remoteNew = $value; // see #3861

		if (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['isBoolean'] ?? null) || (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['inputType'] ?? null) == 'checkbox' && !($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['multiple'] ?? null)))
		{
			$remoteNew = $value ? ucfirst($GLOBALS['TL_LANG']['MSC']['yes']) : ucfirst($GLOBALS['TL_LANG']['MSC']['no']);
		}
		elseif (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['foreignKey']))
		{
			$key = explode('.', $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['foreignKey'], 2);

			$objParent = Database::getInstance()
				->prepare("SELECT " . Database::quoteIdentifier($key[1]) . " AS value FROM " . $key[0] . " WHERE id=?")
				->limit(1)
				->execute($value);

			if ($objParent->numRows)
			{
				$remoteNew = $objParent->value;
			}
		}
		elseif (\in_array($mode, array(self::SORT_INITIAL_LETTER_ASC, self::SORT_INITIAL_LETTER_DESC)))
		{
			$remoteNew = $value ? mb_strtoupper(mb_substr($value, 0, 1)) : '-';
		}
		elseif (\in_array($mode, array(self::SORT_INITIAL_LETTERS_ASC, self::SORT_INITIAL_LETTERS_DESC)))
		{
			if (!isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['length']))
			{
				$GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['length'] = 2;
			}

			$remoteNew = $value ? (new UnicodeString($value))->slice(0, $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['length'])->title()->toString() : '-';
		}
		elseif (\in_array($mode, array(self::SORT_DAY_ASC, self::SORT_DAY_DESC, self::SORT_DAY_BOTH)))
		{
			$remoteNew = $value ? Date::parse(Config::get('dateFormat'), $value) : '-';
		}
		elseif (\in_array($mode, array(self::SORT_MONTH_ASC, self::SORT_MONTH_DESC, self::SORT_MONTH_BOTH)))
		{
			$remoteNew = $value ? date('Y-m', $value) : '-';
			$intMonth = $value ? (date('m', $value) - 1) : '-';

			if (isset($GLOBALS['TL_LANG']['MONTHS'][$intMonth]))
			{
				$remoteNew = $value ? $GLOBALS['TL_LANG']['MONTHS'][$intMonth] . ' ' . date('Y', $value) : '-';
			}
		}
		elseif (\in_array($mode, array(self::SORT_YEAR_ASC, self::SORT_YEAR_DESC, self::SORT_YEAR_BOTH)))
		{
			$remoteNew = $value ? date('Y', $value) : '-';
		}
		else
		{
			if (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['inputType'] ?? null) == 'checkbox' && !($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['multiple'] ?? null))
			{
				$remoteNew = $value ? $field : '';
			}
			elseif (\is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['reference'] ?? null))
			{
				$remoteNew = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['reference'][$value] ?? null;
			}
			elseif (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['isAssociative'] ?? null) || ArrayUtil::isAssoc($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['options'] ?? null))
			{
				$remoteNew = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['options'][$value] ?? null;
			}

			if (\is_array($remoteNew))
			{
				$remoteNew = $remoteNew[0];
			}

			if (empty($remoteNew))
			{
				$remoteNew = '-';
			}
		}

		return $remoteNew;
	}

	/**
	 * Return the formatted group header as string
	 *
	 * @param string  $field
	 * @param mixed   $value
	 * @param integer $mode
	 * @param array   $row
	 *
	 * @return string
	 */
	protected function formatGroupHeader($field, $value, $mode, $row)
	{
		static $lookup = array();

		if (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['isAssociative'] ?? null) || ArrayUtil::isAssoc($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['options'] ?? null))
		{
			$group = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['options'][$value] ?? null;
		}
		elseif (\is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['options_callback'] ?? null))
		{
			if (!isset($lookup[$field]))
			{
				$strClass = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['options_callback'][0];
				$strMethod = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['options_callback'][1];

				$lookup[$field] = System::importStatic($strClass)->$strMethod($this);
			}

			$group = $lookup[$field][$value] ?? null;
		}
		else
		{
			$group = \is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['reference'][$value] ?? null) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['reference'][$value][0] : ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['reference'][$value] ?? null);
		}

		if (empty($group))
		{
			$group = \is_array($GLOBALS['TL_LANG'][$this->strTable][$value] ?? null) ? $GLOBALS['TL_LANG'][$this->strTable][$value][0] : ($GLOBALS['TL_LANG'][$this->strTable][$value] ?? null);
		}

		if (empty($group))
		{
			$group = $value;

			if (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['isBoolean'] ?? null) || (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['inputType'] ?? null) == 'checkbox' && !($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['multiple'] ?? null)))
			{
				$label = \is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label'] ?? null) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label'][0] : ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label'] ?? null);

				if ($label)
				{
					$group = $value == ucfirst($GLOBALS['TL_LANG']['MSC']['yes']) ? $label : \sprintf($GLOBALS['TL_LANG']['MSC']['booleanNot'], lcfirst($label));
				}
			}
		}

		// Call the group callback ($group, $sortingMode, $firstOrderBy, $row, $this)
		if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['group_callback'] ?? null))
		{
			$group = System::importStatic($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['group_callback'][0])->{$GLOBALS['TL_DCA'][$this->strTable]['list']['label']['group_callback'][1]}($group, $mode, $field, $row, $this);
		}
		elseif (\is_callable($GLOBALS['TL_DCA'][$this->strTable]['list']['label']['group_callback'] ?? null))
		{
			$group = $GLOBALS['TL_DCA'][$this->strTable]['list']['label']['group_callback']($group, $mode, $field, $row, $this);
		}

		return $group;
	}

	/**
	 * Initialize the root nodes
	 */
	protected function initRoots()
	{
		$this->rootPaste = $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['rootPaste'] ?? false;

		// Get the IDs of all root records (tree view)
		if ($this->treeView)
		{
			$table = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_TREE_EXTENDED ? $this->ptable : $this->strTable;

			// Unless there are any root records specified, use all records with parent ID 0
			if (!isset($GLOBALS['TL_DCA'][$table]['list']['sorting']['root']) || $GLOBALS['TL_DCA'][$table]['list']['sorting']['root'] === false)
			{
				$db = Database::getInstance();
				$objIds = $db->execute("SELECT id FROM $table WHERE (pid=0 OR pid IS NULL)" . ($db->fieldExists('sorting', $table) ? ' ORDER BY sorting, id' : ''));

				if ($objIds->numRows > 0)
				{
					$this->updateRoot($objIds->fetchEach('id'));
				}
			}

			// Get root records from DCA
			elseif (\is_array($GLOBALS['TL_DCA'][$table]['list']['sorting']['root']))
			{
				if ($GLOBALS['TL_DCA'][$table]['list']['sorting']['root'] == array(0))
				{
					$this->updateRoot(array(0));
				}
				else
				{
					$this->updateRoot($this->eliminateNestedPages($GLOBALS['TL_DCA'][$table]['list']['sorting']['root'], $table));
				}
			}
		}

		// Get the IDs of all root records (list view or parent view)
		elseif (\is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['root'] ?? null))
		{
			$this->updateRoot(array_unique($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['root']));
		}
	}

	/**
	 * Optimized function for common tree view calls in DC_Table to reduce the amount of database queries.
	 *
	 * @param  array<int> $ids
	 * @return array<int>
	 */
	private function getParentRecordIds(array $ids, string $table, bool $skipIds = false): array
	{
		if (!$ids)
		{
			return array();
		}

		$db = Database::getInstance();
		$allParents = array();

		foreach ($ids as $id)
		{
			if (!isset($this->parentPagesCache[$table][$id]))
			{
				$parents = $db->getParentRecords($id, $table, true);
				$this->parentPagesCache[$table][$id] = $parents;

				// Get all IDs on that level, they all have the same parents
				$siblingsOnThisLevel = $db
					->prepare("SELECT id FROM $table WHERE id != ? AND pid = (SELECT pid FROM $table WHERE id = ?)")
					->execute($id, $id)
					->fetchEach('id');

				foreach ($siblingsOnThisLevel as $siblingId)
				{
					$this->parentPagesCache[$table][$siblingId] = $parents;
				}
			}

			foreach ($this->parentPagesCache[$table][$id] as $parent)
			{
				$allParents[$parent] = true;
			}
		}

		// Our cache never includes the IDs, so we add them to the result unless $skipIds was set to true
		if (!$skipIds)
		{
			foreach ($ids as $id)
			{
				$allParents[$id] = true;
			}
		}

		return array_keys($allParents);
	}

	protected function updateRoot(array $root, bool $isSearch = false)
	{
		$db = Database::getInstance();
		$table = ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_TREE_EXTENDED ? $this->ptable : $this->strTable;

		if ($this->treeView)
		{
			$this->root = $this->eliminateNestedPages($root, $table);
			$this->visibleRootTrails = array();

			// Fetch visible root trails if enabled
			if ($GLOBALS['TL_DCA'][$table]['list']['sorting']['showRootTrails'] ?? null)
			{
				$this->visibleRootTrails = $this->getParentRecordIds($this->root, $table, true);
			}

			// Fetch all children of the root
			$this->rootChildren = $db->getChildRecords($this->root, $table);

			if ($isSearch)
			{
				$this->rootChildren = array_intersect($this->rootChildren, $this->getParentRecordIds($root, $table));
				$this->visibleRootTrails = array_merge($this->visibleRootTrails, array_diff($this->rootChildren, $root));
			}

			return;
		}

		$this->root = $root;

		// $this->root might not have a correct order here, let's make sure it's ordered by sorting
		if ($this->root && $db->fieldExists('sorting', $table))
		{
			$this->root = $db->execute("SELECT id FROM $table WHERE id IN (" . implode(',', $this->root) . ") ORDER BY sorting, id")->fetchEach('id');
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function initPicker(PickerInterface $picker)
	{
		$attributes = parent::initPicker($picker);

		if (null === $attributes)
		{
			return null;
		}

		// Predefined node set (see #3563)
		if (isset($attributes['rootNodes']))
		{
			$db = Database::getInstance();
			$blnHasSorting = $db->fieldExists('sorting', $this->strTable);
			$arrRoot = $this->eliminateNestedPages((array) $attributes['rootNodes'], $this->strTable, $blnHasSorting);

			// Calculate the intersection of the root nodes with the mounted nodes (see #1001)
			if (!empty($this->root) && $arrRoot != $this->root)
			{
				$arrRoot = $this->eliminateNestedPages(
					array_intersect(
						array_merge($arrRoot, $db->getChildRecords($arrRoot, $this->strTable)),
						array_merge($this->root, $db->getChildRecords($this->root, $this->strTable))
					),
					$this->strTable,
					$blnHasSorting
				);
			}

			$this->updateRoot($arrRoot);
		}

		return $attributes;
	}

	protected function addDynamicPtable(array $data): array
	{
		if (($GLOBALS['TL_DCA'][$this->strTable]['config']['dynamicPtable'] ?? false) && !isset($data['ptable']))
		{
			$data['ptable'] = $this->ptable;
		}

		return $data;
	}

	protected function canPasteClipboard(array $arrClipboard, array $new): bool
	{
		$security = System::getContainer()->get('security.helper');

		if ($arrClipboard['mode'] === 'create')
		{
			return $security->isGranted(...$this->getClipboardPermission($arrClipboard['mode'], 0, $new));
		}

		foreach ((array) $arrClipboard['id'] as $id)
		{
			if (!$security->isGranted(...$this->getClipboardPermission($arrClipboard['mode'], (int) $id, $new)))
			{
				return false;
			}
		}

		return true;
	}

	protected function getClipboardPermission(string $mode, int $id, array|null $new = null): array
	{
		if (ClipboardManager::MODE_CREATE === $mode && ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['mode'] ?? null) == self::MODE_PARENT)
		{
			$parent = array('pid' => $id);

			if (($GLOBALS['TL_DCA'][$this->strTable]['config']['dynamicPtable'] ?? null) && $this->ptable)
			{
				$parent['ptable'] = $this->ptable;
			}

			$new = array_replace($parent, (array) $new);
		}

		$action = match ($mode)
		{
			ClipboardManager::MODE_CREATE => new CreateAction($this->strTable, $new),
			ClipboardManager::MODE_CUT,
			ClipboardManager::MODE_CUT_ALL => new UpdateAction($this->strTable, $this->getCurrentRecord($id, $this->strTable), array_replace(array('sorting' => null), (array) $new)),
			ClipboardManager::MODE_COPY,
			ClipboardManager::MODE_COPY_ALL => new CreateAction($this->strTable, array_replace($this->getCurrentRecord($id, $this->strTable) ?? array(), array('tstamp' => null, 'sorting' => null), (array) $new))
		};

		return array(ContaoCorePermissions::DC_PREFIX . $this->strTable, $action);
	}

	protected function configurePidAndSortingFields()
	{
		foreach (array('pid', 'sorting') as $f)
		{
			if (!isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$f]['label']))
			{
				$GLOBALS['TL_DCA'][$this->strTable]['fields'][$f]['label'] = &$GLOBALS['TL_LANG']['MSC'][$f];
			}

			if (!isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$f]['inputType']))
			{
				$GLOBALS['TL_DCA'][$this->strTable]['fields'][$f]['inputType'] = 'text';
			}

			if (!isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$f]['eval']['tl_class']))
			{
				$GLOBALS['TL_DCA'][$this->strTable]['fields'][$f]['eval']['tl_class'] = 'w50';
			}

			if (!isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$f]['eval']['rgxp']))
			{
				$GLOBALS['TL_DCA'][$this->strTable]['fields'][$f]['eval']['rgxp'] = 'natural';
			}
		}
	}
}
