<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\EventListener\BackendRebuildCacheMessageListener;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\BadRequestException;
use Contao\CoreBundle\Exception\NotFoundException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Picker\PickerInterface;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Util\SymlinkUtil;
use Contao\Image\PictureConfiguration;
use Contao\Image\PictureConfigurationItem;
use Contao\Image\ResizeConfiguration;
use Doctrine\DBAL\Exception\DriverException;
use Imagine\Exception\RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * Provide methods to modify the file system.
 *
 * @property string  $path
 * @property string  $extension
 * @property string  $uploadPath
 * @property array   $editableFileTypes
 * @property boolean $createNewVersion
 * @property boolean $isDbAssisted
 */
class DC_Folder extends DataContainer implements ListableDataContainerInterface, EditableDataContainerInterface
{
	/**
	 * Current path
	 * @var string
	 */
	protected $strPath;

	/**
	 * Current file extension
	 * @var string
	 */
	protected $strExtension;

	/**
	 * Root dir
	 * @var string
	 */
	protected $strRootDir;

	/**
	 * Upload path
	 * @var string
	 */
	protected $strUploadPath;

	/**
	 * Initial ID of the record
	 * @var string
	 */
	protected $initialId;

	/**
	 * Current file mounts
	 * @var array
	 */
	protected $arrFilemounts = array();

	/**
	 * Valid file types
	 * @var array
	 */
	protected $arrValidFileTypes = array();

	/**
	 * Editable file types
	 * @var array
	 */
	protected $arrEditableFileTypes = array();

	/**
	 * Messages
	 * @var array
	 */
	protected $arrMessages = array();

	/**
	 * Counts
	 * @var array
	 */
	protected $arrCounts = array();

	/**
	 * Database assisted
	 * @var boolean
	 */
	protected $blnIsDbAssisted = false;

	/**
	 * Show files
	 * @var boolean
	 */
	protected $blnFiles = true;

	/**
	 * Only allow to select files
	 * @var boolean
	 */
	protected $blnFilesOnly = false;

	/**
	 * Initialize the object
	 *
	 * @param string $strTable
	 *
	 * @throws AccessDeniedException
	 */
	public function __construct($strTable)
	{
		parent::__construct();

		$container = System::getContainer();
		$objSession = $container->get('request_stack')->getSession();

		// Check the request token (see #4007)
		if (!\in_array(Input::get('act'), array(null, 'edit', 'source', 'show', 'select'), true) && (Input::get('rt') === null || !$container->get('contao.csrf.token_manager')->isTokenValid(new CsrfToken($container->getParameter('contao.csrf_token_name'), Input::get('rt')))))
		{
			$objSession->set('INVALID_TOKEN_URL', Environment::get('requestUri'));
			$this->redirect($container->get('router')->generate('contao_backend_confirm'));
		}

		$this->intId = Input::get('id', true);

		// Clear the clipboard
		if (Input::get('clipboard') !== null)
		{
			$objSession->set('CLIPBOARD', array());
			$this->redirect($this->getReferer());
		}

		// Check whether the table is defined
		if (!$strTable || !isset($GLOBALS['TL_DCA'][$strTable]))
		{
			$container->get('monolog.logger.contao.error')->error('Could not load data container configuration for "' . $strTable . '"');
			trigger_error('Could not load data container configuration', E_USER_ERROR);
		}

		// Check permission to create new folders
		if (isset($GLOBALS['TL_DCA'][$strTable]['list']['new']) && Input::get('act') == 'paste' && Input::get('mode') == 'create')
		{
			throw new AccessDeniedException('Attempt to create a new folder although the method has been overwritten in the data container.');
		}

		$ids = null;

		// Set IDs
		if (Input::post('FORM_SUBMIT') == 'tl_select' || (\in_array(Input::post('FORM_SUBMIT'), array($strTable, $strTable . '_all')) && Input::get('act') == 'editAll'))
		{
			$ids = Input::post('IDS');

			if (!empty($ids) && \is_array($ids))
			{
				// Decode the values (see #5764)
				$ids = array_map('rawurldecode', $ids);

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
				$this->redirect(str_replace('act=select', 'act=editAll', Environment::get('requestUri')));
			}
			elseif (Input::post('delete') !== null)
			{
				$this->redirect(str_replace('act=select', 'act=deleteAll', Environment::get('requestUri')));
			}
			elseif (Input::post('cut') !== null || Input::post('copy') !== null)
			{
				$arrClipboard = $objSession->get('CLIPBOARD');

				$arrClipboard[$strTable] = array
				(
					'id' => $ids,
					'mode' => (Input::post('cut') !== null ? 'cutAll' : 'copyAll')
				);

				$objSession->set('CLIPBOARD', $arrClipboard);
				$this->redirect($this->getReferer());
			}
		}

		$this->strTable = $strTable;
		$this->blnIsDbAssisted = $GLOBALS['TL_DCA'][$strTable]['config']['databaseAssisted'] ?? false;
		$this->strRootDir = $container->getParameter('kernel.project_dir');

		// Check for valid file types
		if ($GLOBALS['TL_DCA'][$this->strTable]['config']['validFileTypes'] ?? null)
		{
			$this->arrValidFileTypes = StringUtil::trimsplit(',', strtolower($GLOBALS['TL_DCA'][$this->strTable]['config']['validFileTypes']));
		}

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

		$this->arrEditableFileTypes = StringUtil::trimsplit(',', strtolower($GLOBALS['TL_DCA'][$this->strTable]['config']['editableFileTypes'] ?? throw new \InvalidArgumentException(sprintf('Missing config.editableFileTypes setting for DC_Folder "%s"', $this->strTable))));
		$this->strUploadPath = $GLOBALS['TL_DCA'][$this->strTable]['config']['uploadPath'] ?? throw new \InvalidArgumentException(sprintf('Missing config.uploadPath setting for DC_Folder "%s"', $this->strTable));

		// Get all file mounts (root folders)
		$this->updateFilemounts(\is_array($GLOBALS['TL_DCA'][$strTable]['list']['sorting']['root'] ?? null) ? $GLOBALS['TL_DCA'][$strTable]['list']['sorting']['root'] : array());
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
			case 'path':
				return $this->strPath;

			case 'extension':
				return $this->strExtension;

			case 'uploadPath':
				return $this->strUploadPath;

			case 'isDbAssisted':
				return $this->blnIsDbAssisted;

			case 'editableFileTypes':
				return $this->arrEditableFileTypes;
		}

		return parent::__get($strKey);
	}

	/**
	 * List all files and folders of the file system
	 *
	 * @return string
	 */
	public function showAll()
	{
		$return = '';
		$objSession = System::getContainer()->get('request_stack')->getSession();

		/** @var AttributeBagInterface $objSessionBag */
		$objSessionBag = $objSession->getBag('contao_backend');
		$session = $objSessionBag->all();

		// Add to clipboard
		if (Input::get('act') == 'paste')
		{
			$mode = Input::get('mode');

			if ($mode != 'create' && $mode != 'move')
			{
				$this->isValid($this->intId);
			}

			$arrClipboard = $objSession->get('CLIPBOARD');

			$arrClipboard[$this->strTable] = array
			(
				'id' => $this->urlEncode($this->intId),
				'childs' => Input::get('childs'),
				'mode' => $mode
			);

			$objSession->set('CLIPBOARD', $arrClipboard);
		}

		// Get the session data and toggle the nodes
		if (Input::get('tg') == 'all')
		{
			$state = Input::get('state');

			// Expand tree
			if ($state || (null === $state && (empty($session['filetree']) || !\is_array($session['filetree']) || current($session['filetree']) != 1)))
			{
				$session['filetree'] = $this->getMD5Folders($this->strUploadPath);
			}
			// Collapse tree
			else
			{
				$session['filetree'] = array();
			}

			$objSessionBag->replace($session);
			$this->redirect(preg_replace('/(&(amp;)?|\?)tg=[^& ]*/i', '', Environment::get('requestUri')));
		}

		$blnClipboard = false;
		$arrClipboard = $objSession->get('CLIPBOARD');

		// Check clipboard
		if (!empty($arrClipboard[$this->strTable]))
		{
			$blnClipboard = true;
			$arrClipboard = $arrClipboard[$this->strTable];
		}
		else
		{
			$arrClipboard = null;
		}

		$arrFound = array();
		$for = $session['search'][$this->strTable]['value'] ?? null;

		// Limit the results by modifying the file mounts
		if ((string) $for !== '')
		{
			$db = Database::getInstance();

			try
			{
				$db->prepare("SELECT '' REGEXP ?")->execute($for);
			}
			catch (DriverException $exception)
			{
				// Quote search string if it is not a valid regular expression
				$for = preg_quote($for, null);
			}

			$strPattern = "LOWER(CAST(name AS CHAR)) REGEXP LOWER(?)";

			$objRoot = $db
				->prepare("SELECT path, type, extension FROM " . $this->strTable . " WHERE " . $strPattern)
				->execute($for);

			if ($objRoot->numRows < 1)
			{
				$this->updateFilemounts(array());
			}
			else
			{
				$arrRoot = array();

				// Respect existing limitations (root IDs)
				if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['root'] ?? null))
				{
					while ($objRoot->next())
					{
						foreach ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['root'] as $root)
						{
							if (strncmp($root . '/', $objRoot->path . '/', \strlen($root) + 1) === 0)
							{
								if ($objRoot->type == 'folder' || empty($this->arrValidFileTypes) || \in_array($objRoot->extension, $this->arrValidFileTypes))
								{
									$arrFound[] = $objRoot->path;
								}

								$arrRoot[] = ($objRoot->type == 'folder') ? $objRoot->path : \dirname($objRoot->path);
								continue 2;
							}
						}
					}
				}
				else
				{
					while ($objRoot->next())
					{
						if ($objRoot->type == 'folder' || empty($this->arrValidFileTypes) || \in_array($objRoot->extension, $this->arrValidFileTypes))
						{
							$arrFound[] = $objRoot->path;
						}

						$arrRoot[] = ($objRoot->type == 'folder') ? $objRoot->path : \dirname($objRoot->path);
					}
				}

				$this->updateFilemounts($arrRoot);
			}
		}

		// Call recursive function tree()
		if ((string) $for !== '' && empty($this->arrFilemounts))
		{
			// Show an empty tree if there are no search results
		}
		elseif (empty($this->arrFilemounts) && !\is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['root'] ?? null) && ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['root'] ?? null) !== false)
		{
			$return .= $this->generateTree($this->strRootDir . '/' . $this->strUploadPath, 0, false, true, $blnClipboard ? $arrClipboard : false, $arrFound);
		}
		else
		{
			$topMostVisibleRootTrails = $this->eliminateNestedPaths($this->visibleRootTrails);

			for ($i=0, $c=\count($topMostVisibleRootTrails); $i<$c; $i++)
			{
				if ($topMostVisibleRootTrails[$i] && is_dir($this->strRootDir . '/' . $topMostVisibleRootTrails[$i]))
				{
					$return .= $this->generateTree($this->strRootDir . '/' . $topMostVisibleRootTrails[$i], 0, true, $this->isProtectedPath($topMostVisibleRootTrails[$i]), $blnClipboard ? $arrClipboard : false, $arrFound);
				}
			}
		}

		// Check for the "create new" button
		$clsNew = 'header_new_folder';
		$lblNew = $GLOBALS['TL_LANG'][$this->strTable]['new'][0];
		$ttlNew = $GLOBALS['TL_LANG'][$this->strTable]['new'][1];
		$hrfNew = 'act=paste&amp;mode=create';

		if (isset($GLOBALS['TL_DCA'][$this->strTable]['list']['new']))
		{
			$clsNew = $GLOBALS['TL_DCA'][$this->strTable]['list']['new']['class'];
			$lblNew = $GLOBALS['TL_DCA'][$this->strTable]['list']['new']['label'][0];
			$ttlNew = $GLOBALS['TL_DCA'][$this->strTable]['list']['new']['label'][1];
			$hrfNew = $GLOBALS['TL_DCA'][$this->strTable]['list']['new']['href'];
		}

		$labelPasteInto = $GLOBALS['TL_LANG'][$this->strTable]['pasteinto'] ?? $GLOBALS['TL_LANG']['DCA']['pasteinto'];
		$imagePasteInto = Image::getHtml('pasteinto.svg', $labelPasteInto[0]);

		if ((string) $for !== '')
		{
			Message::addInfo($GLOBALS['TL_LANG']['MSC']['searchExclude']);
		}

		if (isset($GLOBALS['TL_DCA'][$this->strTable]['config']['label']))
		{
			$label = $GLOBALS['TL_DCA'][$this->strTable]['config']['label'];
		}
		elseif (($do = Input::get('do')) && isset($GLOBALS['TL_LANG']['MOD'][$do]))
		{
			$label = $GLOBALS['TL_LANG']['MOD'][$do][0];
		}
		else
		{
			$label = $GLOBALS['TL_LANG']['MOD']['files'][0];
		}

		$icon = !empty($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['icon']) ? $GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['icon'] : 'filemounts.svg';
		$label = Image::getHtml($icon) . ' <label>' . $label . '</label>';

		$requestToken = htmlspecialchars(System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue());
		$strRefererId = System::getContainer()->get('request_stack')->getCurrentRequest()->attributes->get('_contao_referer_id');

		$security = System::getContainer()->get('security.helper');

		$buttons = ((Input::get('act') == 'select') ? '
<a href="' . $this->getReferer(true) . '" class="header_back" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']) . '" accesskey="b" onclick="Backend.getScrollOffset()">' . $GLOBALS['TL_LANG']['MSC']['backBT'] . '</a> ' : '') . ((Input::get('act') != 'select' && !$blnClipboard && !($GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] ?? null) && !($GLOBALS['TL_DCA'][$this->strTable]['config']['notCreatable'] ?? null) && $security->isGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new CreateAction($this->strTable))) ? '
<a href="' . $this->addToUrl($hrfNew) . '" class="' . $clsNew . '" title="' . StringUtil::specialchars($ttlNew) . '" accesskey="n" onclick="Backend.getScrollOffset()">' . $lblNew . '</a>
<a href="' . $this->addToUrl('&amp;act=paste&amp;mode=move') . '" class="header_new" title="' . StringUtil::specialchars($GLOBALS['TL_LANG'][$this->strTable]['move'][1]) . '" onclick="Backend.getScrollOffset()">' . $GLOBALS['TL_LANG'][$this->strTable]['move'][0] . '</a>  ' : '') . ($blnClipboard ? '
<a href="' . $this->addToUrl('clipboard=1') . '" class="header_clipboard" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['clearClipboard']) . '" accesskey="x">' . $GLOBALS['TL_LANG']['MSC']['clearClipboard'] . '</a> ' : $this->generateGlobalButtons());

		// Build the tree
		$return = $this->panel() . Message::generate() . ($buttons ? '
<div id="tl_buttons">' . $buttons . '</div>' : '') . ((Input::get('act') == 'select') ? '
<form id="tl_select" class="tl_form' . ((Input::get('act') == 'select') ? ' unselectable' : '') . '" method="post" novalidate>
<div class="tl_formbody_edit">
<input type="hidden" name="FORM_SUBMIT" value="tl_select">
<input type="hidden" name="REQUEST_TOKEN" value="' . $requestToken . '">' : '') . ($blnClipboard ? '
<div id="paste_hint" data-add-to-scroll-offset="20">
  <p>' . $GLOBALS['TL_LANG']['MSC']['selectNewPosition'] . '</p>
</div>' : '') . '
<div class="tl_listing_container tree_view" id="tl_listing"' . $this->getPickerValueAttribute() . '>' . ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['breadcrumb'] ?? '') . ((Input::get('act') == 'select' || $this->strPickerFieldType == 'checkbox') ? '
<div class="tl_select_trigger">
<label for="tl_select_trigger" class="tl_select_label">' . $GLOBALS['TL_LANG']['MSC']['selectAll'] . '</label> <input type="checkbox" id="tl_select_trigger" onclick="Backend.toggleCheckboxes(this)" class="tl_tree_checkbox">
</div>' : '') . '
<ul class="tl_listing tl_file_manager' . ($this->strPickerFieldType ? ' picker unselectable' : '') . '">
  <li class="tl_folder_top cf"><div class="tl_left">' . $label . '</div> <div class="tl_right">' . (($blnClipboard && empty($this->arrFilemounts) && !\is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['root'] ?? null) && ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['root'] ?? null) !== false && $security->isGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new CreateAction($this->strTable))) ? '<a href="' . $this->addToUrl('&amp;act=' . $arrClipboard['mode'] . '&amp;mode=2&amp;pid=' . $this->strUploadPath . (!\is_array($arrClipboard['id'] ?? null) ? '&amp;id=' . $arrClipboard['id'] : '')) . '" title="' . StringUtil::specialchars($labelPasteInto[0]) . '" onclick="Backend.getScrollOffset()">' . $imagePasteInto . '</a>' : '&nbsp;') . '</div></li>' . $return . '
</ul>' . ($this->strPickerFieldType == 'radio' ? '
<div class="tl_radio_reset">
<label for="tl_radio_reset" class="tl_radio_label">' . $GLOBALS['TL_LANG']['MSC']['resetSelected'] . '</label> <input type="radio" name="picker" id="tl_radio_reset" value="" class="tl_tree_radio">
</div>' : '') . '
</div>';

		// Close the form
		if (Input::get('act') == 'select')
		{
			// Submit buttons
			$arrButtons = array();

			if (!($GLOBALS['TL_DCA'][$this->strTable]['config']['notEditable'] ?? null))
			{
				$arrButtons['edit'] = '<button type="submit" name="edit" id="edit" class="tl_submit" accesskey="s">' . $GLOBALS['TL_LANG']['MSC']['editSelected'] . '</button>';
			}

			if (!($GLOBALS['TL_DCA'][$this->strTable]['config']['notDeletable'] ?? null))
			{
				$arrButtons['delete'] = '<button type="submit" name="delete" id="delete" class="tl_submit" accesskey="d" onclick="return confirm(\'' . $GLOBALS['TL_LANG']['MSC']['delAllConfirmFile'] . '\')">' . $GLOBALS['TL_LANG']['MSC']['deleteSelected'] . '</button>';
			}

			if (!($GLOBALS['TL_DCA'][$this->strTable]['config']['notSortable'] ?? null))
			{
				$arrButtons['cut'] = '<button type="submit" name="cut" id="cut" class="tl_submit" accesskey="x">' . $GLOBALS['TL_LANG']['MSC']['moveSelected'] . '</button>';
			}

			if (!($GLOBALS['TL_DCA'][$this->strTable]['config']['notCopyable'] ?? null))
			{
				$arrButtons['copy'] = '<button type="submit" name="copy" id="copy" class="tl_submit" accesskey="c">' . $GLOBALS['TL_LANG']['MSC']['copySelected'] . '</button>';
			}

			// Call the buttons_callback (see #4691)
			if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['select']['buttons_callback'] ?? null))
			{
				foreach ($GLOBALS['TL_DCA'][$this->strTable]['select']['buttons_callback'] as $callback)
				{
					if (\is_array($callback))
					{
						$arrButtons = System::importStatic($callback[0])->{$callback[1]}($arrButtons, $this);
					}
					elseif (\is_callable($callback))
					{
						$arrButtons = $callback($arrButtons, $this);
					}
				}
			}

			if (\count($arrButtons) < 3)
			{
				$strButtons = implode(' ', $arrButtons);
			}
			else
			{
				$strButtons = array_shift($arrButtons) . ' ';
				$strButtons .= '<div class="split-button">';
				$strButtons .= array_shift($arrButtons) . '<button type="button" id="sbtog">' . Image::getHtml('navcol.svg') . '</button> <ul class="invisible">';

				foreach ($arrButtons as $strButton)
				{
					$strButtons .= '<li>' . $strButton . '</li>';
				}

				$strButtons .= '</ul></div>';
			}

			$return .= '
</div>
<div class="tl_formbody_submit" style="text-align:right">
<div class="tl_submit_container">
  ' . $strButtons . '
</div>
</div>
</form>';
		}

		if (!($GLOBALS['TL_DCA'][$this->strTable]['config']['closed'] ?? null) && !($GLOBALS['TL_DCA'][$this->strTable]['config']['notCreatable'] ?? null) && Input::get('act') != 'select')
		{
			$GLOBALS['TL_CSS'][] = 'assets/dropzone/css/dropzone.min.css';
			$GLOBALS['TL_JAVASCRIPT'][] = 'assets/dropzone/js/dropzone.min.js';

			$strAccepted = implode(',', array_map(static function ($a) { return '.' . $a; }, StringUtil::trimsplit(',', strtolower(Config::get('uploadTypes')))));
			$intMaxSize = round(FileUpload::getMaxUploadSize() / 1024 / 1024);

			$return .= '<script>'
				. 'Dropzone.autoDiscover = false;'
				. 'Backend.enableFileTreeUpload("tl_listing", ' . json_encode(array(
					'url' => html_entity_decode($this->addToUrl('act=move&mode=2&pid=' . urlencode($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['root'][0] ?? $this->strUploadPath))),
					'paramName' => 'files',
					'maxFilesize' => $intMaxSize,
					'acceptedFiles' => $strAccepted,
					'params' => array(
						'FORM_SUBMIT' => 'tl_upload',
						'action' => 'fileupload',
					),
				)) . ')</script>'
			;
		}

		$return .= '<script>'
			. 'Backend.enableFileTreeDragAndDrop($("tl_listing").getChildren(".tl_file_manager")[0], ' . json_encode(array(
				'url' => html_entity_decode($this->addToUrl('act=cut&mode=2&pid=' . urlencode($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['root'][0] ?? $this->strUploadPath))),
			)) . ')</script>'
		;

		if (isset($GLOBALS['TL_DCA'][$this->strTable]['list']['global_operations']['toggleNodes']))
		{
			$return = '<div
					data-controller="contao--toggle-nodes"
					data-contao--toggle-nodes-toggle-action-value="toggleFileManager"
					data-contao--toggle-nodes-load-action-value="loadFileManager"
					data-contao--toggle-nodes-request-token-value="' . $requestToken . '"
					data-contao--toggle-nodes-referer-id-value="' . $strRefererId . '"
					data-contao--toggle-nodes-expand-value="' . $GLOBALS['TL_LANG']['MSC']['expandNode'] . '"
					data-contao--toggle-nodes-collapse-value="' . $GLOBALS['TL_LANG']['MSC']['collapseNode'] . '"
					data-contao--toggle-nodes-expand-all-value="' . $GLOBALS['TL_LANG']['DCA']['expandNodes'][0] . '"
					data-contao--toggle-nodes-expand-all-title-value="' . $GLOBALS['TL_LANG']['DCA']['expandNodes'][1] . '"
					data-contao--toggle-nodes-collapse-all-value="' . $GLOBALS['TL_LANG']['DCA']['collapseNodes'][0] . '"
					data-contao--toggle-nodes-collapse-all-title-value="' . $GLOBALS['TL_LANG']['DCA']['collapseNodes'][0] . '"
				>' . $return . '</div>';
		}

		return $return;
	}

	/**
	 * Automatically switch to showAll
	 *
	 * @return string
	 */
	public function show()
	{
		return $this->showAll();
	}

	/**
	 * Create a new folder
	 *
	 * @throws AccessDeniedException
	 */
	public function create()
	{
		if ($GLOBALS['TL_DCA'][$this->strTable]['config']['notCreatable'] ?? null)
		{
			throw new AccessDeniedException('Table "' . $this->strTable . '" is not creatable.');
		}

		$strFolder = Input::get('pid', true);
		$id = $strFolder . '/__new__';

		if (!$strFolder || !file_exists($this->strRootDir . '/' . $strFolder) || !$this->isMounted($strFolder))
		{
			throw new AccessDeniedException('Folder "' . $strFolder . '" is not mounted or is not a directory.');
		}

		$this->denyAccessUnlessGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new CreateAction($this->strTable, array('id' => $id, 'pid' => $strFolder)));

		$objSession = System::getContainer()->get('request_stack')->getSession();

		// Empty clipboard
		$arrClipboard = $objSession->get('CLIPBOARD');
		$arrClipboard[$this->strTable] = array();
		$objSession->set('CLIPBOARD', $arrClipboard);

		Files::getInstance()->mkdir($id);
		$this->redirect(html_entity_decode($this->switchToEdit($id)));
	}

	/**
	 * Move an existing file or folder
	 *
	 * @param string $source
	 *
	 * @throws AccessDeniedException
	 * @throws UnprocessableEntityHttpException
	 */
	public function cut($source=null)
	{
		if ($GLOBALS['TL_DCA'][$this->strTable]['config']['notSortable'] ?? null)
		{
			throw new AccessDeniedException('Table "' . $this->strTable . '" is not sortable.');
		}

		$strFolder = Input::get('pid', true);
		$blnDoNotRedirect = $source !== null;

		if ($source === null)
		{
			$source = $this->intId;
		}

		$this->isValid($source);

		if (!file_exists($this->strRootDir . '/' . $source) || !$this->isMounted($source))
		{
			throw new AccessDeniedException('File or folder "' . $source . '" is not mounted or cannot be found.');
		}

		if (!file_exists($this->strRootDir . '/' . $strFolder) || !$this->isMounted($strFolder))
		{
			throw new AccessDeniedException('Parent folder "' . $strFolder . '" is not mounted or is not a directory.');
		}

		// Avoid a circular reference
		if (preg_match('/^' . preg_quote($source, '/') . '/i', $strFolder))
		{
			throw new UnprocessableEntityHttpException('Attempt to move the folder "' . $source . '" to "' . $strFolder . '" (circular reference).');
		}

		$objSession = System::getContainer()->get('request_stack')->getSession();

		// Empty clipboard
		$arrClipboard = $objSession->get('CLIPBOARD');
		$arrClipboard[$this->strTable] = array();
		$objSession->set('CLIPBOARD', $arrClipboard);

		// Calculate the destination path
		$destination = str_replace(\dirname($source), $strFolder, $source);

		$this->denyAccessUnlessGranted(
			ContaoCorePermissions::DC_PREFIX . $this->strTable,
			new UpdateAction($this->strTable, array('id' => $source, 'pid' => \dirname($source)), array('pid' => $strFolder))
		);

		// Do not move if the target exists and would be overwritten (not possible for folders anyway)
		if (file_exists($this->strRootDir . '/' . $destination))
		{
			Message::addError(sprintf($GLOBALS['TL_LANG']['ERR']['filetarget'], basename($source), \dirname($destination)));
		}
		else
		{
			Files::getInstance()->rename($source, $destination);

			// Update the database AFTER the file has been moved
			if ($this->blnIsDbAssisted)
			{
				$syncSource = Dbafs::shouldBeSynchronized($source);
				$syncTarget = Dbafs::shouldBeSynchronized($destination);

				if ($syncSource && $syncTarget)
				{
					Dbafs::moveResource($source, $destination);
				}
				elseif ($syncSource)
				{
					Dbafs::deleteResource($source);
				}
				elseif ($syncTarget)
				{
					Dbafs::addResource($destination);
				}
			}

			// Call the oncut_callback
			if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['oncut_callback'] ?? null))
			{
				foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['oncut_callback'] as $callback)
				{
					if (\is_array($callback))
					{
						System::importStatic($callback[0])->{$callback[1]}($source, $destination, $this);
					}
					elseif (\is_callable($callback))
					{
						$callback($source, $destination, $this);
					}
				}
			}

			// Regenerate the symlinks (see #5903)
			if (is_dir($this->strRootDir . '/' . $destination))
			{
				(new Automator())->generateSymlinks();
			}

			System::getContainer()->get('monolog.logger.contao.files')->info('File or folder "' . $source . '" has been moved to "' . $destination . '"');
		}

		// Redirect
		if (!$blnDoNotRedirect)
		{
			$this->redirect($this->getReferer());
		}
	}

	/**
	 * Move all selected files and folders
	 *
	 * @throws AccessDeniedException
	 */
	public function cutAll()
	{
		if ($GLOBALS['TL_DCA'][$this->strTable]['config']['notSortable'] ?? null)
		{
			throw new AccessDeniedException('Table "' . $this->strTable . '" is not sortable.');
		}

		// PID is mandatory
		if (!Input::get('pid', true))
		{
			throw new BadRequestException();
		}

		$objSession = System::getContainer()->get('request_stack')->getSession();
		$arrClipboard = $objSession->get('CLIPBOARD');

		if (isset($arrClipboard[$this->strTable]) && \is_array($arrClipboard[$this->strTable]['id']))
		{
			foreach ($arrClipboard[$this->strTable]['id'] as $id)
			{
				try
				{
					$this->cut($id); // do not urldecode() here (see #6840)
				}
				catch (AccessDeniedException)
				{
					// noop
				}
			}
		}

		$this->redirect($this->getReferer());
	}

	/**
	 * Recursively duplicate files and folders
	 *
	 * @param string $source
	 * @param string $destination
	 *
	 * @throws AccessDeniedException
	 * @throws UnprocessableEntityHttpException
	 */
	public function copy($source=null, $destination=null)
	{
		if ($GLOBALS['TL_DCA'][$this->strTable]['config']['notCopyable'] ?? null)
		{
			throw new AccessDeniedException('Table "' . $this->strTable . '" is not copyable.');
		}

		$strFolder = Input::get('pid', true);
		$blnDoNotRedirect = $source !== null;

		if ($source === null)
		{
			$source = $this->intId;
		}

		if ($destination === null)
		{
			$destination = str_replace(\dirname($source), $strFolder, $source);
		}

		$this->isValid($source);
		$this->isValid($destination);

		if (!file_exists($this->strRootDir . '/' . $source) || !$this->isMounted($source))
		{
			throw new AccessDeniedException('File or folder "' . $source . '" is not mounted or cannot be found.');
		}

		if (!file_exists($this->strRootDir . '/' . $strFolder) || !$this->isMounted($strFolder))
		{
			throw new AccessDeniedException('Parent folder "' . $strFolder . '" is not mounted or is not a directory.');
		}

		// Avoid a circular reference
		if (preg_match('/^' . preg_quote($source, '/') . '/i', $strFolder))
		{
			throw new UnprocessableEntityHttpException('Attempt to copy the folder "' . $source . '" to "' . $strFolder . '" (circular reference).');
		}

		$this->denyAccessUnlessGranted(
			ContaoCorePermissions::DC_PREFIX . $this->strTable,
			new ReadAction($this->strTable, array('id' => $source))
		);

		$this->denyAccessUnlessGranted(
			ContaoCorePermissions::DC_PREFIX . $this->strTable,
			new CreateAction($this->strTable, array('id' => $destination, 'pid' => $strFolder))
		);

		$objSession = System::getContainer()->get('request_stack')->getSession();

		// Empty clipboard
		$arrClipboard = $objSession->get('CLIPBOARD');
		$arrClipboard[$this->strTable] = array();
		$objSession->set('CLIPBOARD', $arrClipboard);

		// Copy folders
		if (is_dir($this->strRootDir . '/' . $source))
		{
			$count = 1;
			$new = $destination;

			// Add a suffix if the folder exists
			while (is_dir($this->strRootDir . '/' . $new) && $count < 12)
			{
				$new = $destination . '_' . $count++;
			}

			$destination = $new;
			Files::getInstance()->rcopy($source, $destination);
		}

		// Copy a file
		else
		{
			$count = 1;
			$new = $destination;
			$ext = strtolower(substr($destination, strrpos($destination, '.') + 1));

			// Add a suffix if the file exists
			while (file_exists($this->strRootDir . '/' . $new) && $count < 12)
			{
				$new = str_replace('.' . $ext, '_' . $count++ . '.' . $ext, $destination);
			}

			$destination = $new;
			Files::getInstance()->copy($source, $destination);
		}

		// Update the database AFTER the file has been copied
		if ($this->blnIsDbAssisted)
		{
			$syncSource = Dbafs::shouldBeSynchronized($source);
			$syncTarget = Dbafs::shouldBeSynchronized($destination);

			if ($syncSource && $syncTarget)
			{
				Dbafs::copyResource($source, $destination);
			}
			elseif ($syncTarget)
			{
				Dbafs::addResource($destination);
			}
		}

		// Call the oncopy_callback
		if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['oncopy_callback'] ?? null))
		{
			foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['oncopy_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					System::importStatic($callback[0])->{$callback[1]}($source, $destination, $this);
				}
				elseif (\is_callable($callback))
				{
					$callback($source, $destination, $this);
				}
			}
		}

		// Regenerate the symlinks (see #5903)
		if (is_dir($this->strRootDir . '/' . $destination))
		{
			(new Automator())->generateSymlinks();
		}

		System::getContainer()->get('monolog.logger.contao.files')->info('File or folder "' . $source . '" has been copied to "' . $destination . '"');

		// Redirect
		if (!$blnDoNotRedirect)
		{
			// Switch to edit mode
			if (is_file($this->strRootDir . '/' . $destination))
			{
				$this->redirect($this->switchToEdit($destination));
			}

			$this->redirect($this->getReferer());
		}
	}

	/**
	 * Move all selected files and folders
	 *
	 * @throws AccessDeniedException
	 */
	public function copyAll()
	{
		if ($GLOBALS['TL_DCA'][$this->strTable]['config']['notCopyable'] ?? null)
		{
			throw new AccessDeniedException('Table "' . $this->strTable . '" is not copyable.');
		}

		// PID is mandatory
		if (!Input::get('pid', true))
		{
			throw new BadRequestException();
		}

		$objSession = System::getContainer()->get('request_stack')->getSession();
		$arrClipboard = $objSession->get('CLIPBOARD');

		if (isset($arrClipboard[$this->strTable]) && \is_array($arrClipboard[$this->strTable]['id']))
		{
			foreach ($arrClipboard[$this->strTable]['id'] as $id)
			{
				try
				{
					$this->copy($id); // do not urldecode() here (see #6840)
				}
				catch (AccessDeniedException)
				{
					// noop
				}
			}
		}

		$this->redirect($this->getReferer());
	}

	/**
	 * Recursively delete files and folders
	 *
	 * @param string $source
	 *
	 * @throws AccessDeniedException
	 */
	public function delete($source=null)
	{
		if ($GLOBALS['TL_DCA'][$this->strTable]['config']['notDeletable'] ?? null)
		{
			throw new AccessDeniedException('Table "' . $this->strTable . '" is not deletable.');
		}

		$blnDoNotRedirect = $source !== null;

		if ($source === null)
		{
			$source = $this->intId;
		}

		$this->isValid($source);

		if (!file_exists($this->strRootDir . '/' . $source) || !$this->isMounted($source))
		{
			throw new AccessDeniedException('File or folder "' . $source . '" is not mounted or cannot be found.');
		}

		$this->denyAccessUnlessGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new DeleteAction($this->strTable, array('id' => $source)));

		// Call the ondelete_callback
		if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['ondelete_callback'] ?? null))
		{
			foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['ondelete_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					System::importStatic($callback[0])->{$callback[1]}($source, $this);
				}
				elseif (\is_callable($callback))
				{
					$callback($source, $this);
				}
			}
		}

		$filesObj = Files::getInstance();

		// Delete the folder or file
		if (is_dir($this->strRootDir . '/' . $source))
		{
			$filesObj->rrdir($source);

			$this->purgeCache($source);

			$strWebDir = StringUtil::stripRootDir(System::getContainer()->getParameter('contao.web_dir'));

			// Also delete the symlink (see #710)
			if (is_link($this->strRootDir . '/' . $strWebDir . '/' . $source))
			{
				$filesObj->delete($strWebDir . '/' . $source);
			}
		}
		else
		{
			$filesObj->delete($source);
		}

		// Update the database AFTER the resource has been deleted
		if ($this->blnIsDbAssisted && Dbafs::shouldBeSynchronized($source))
		{
			Dbafs::deleteResource($source);
		}

		System::getContainer()->get('monolog.logger.contao.files')->info('File or folder "' . $source . '" has been deleted');

		// Redirect
		if (!$blnDoNotRedirect)
		{
			$this->redirect($this->getReferer());
		}
	}

	/**
	 * Delete all files and folders that are currently shown
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

		if (!empty($ids) && \is_array($ids))
		{
			$ids = $this->eliminateNestedPaths($ids); // see #941

			foreach ($ids as $id)
			{
				try
				{
					$this->delete($id); // do not urldecode() here (see #6840)
				}
				catch (AccessDeniedException)
				{
					// noop
				}
			}
		}

		$this->redirect($this->getReferer());
	}

	/**
	 * Automatically switch to showAll
	 *
	 * @return string
	 */
	public function undo()
	{
		return $this->showAll();
	}

	/**
	 * Move one or more local files to the server
	 *
	 * @param boolean $blnIsAjax
	 *
	 * @return string
	 *
	 * @throws AccessDeniedException
	 */
	public function move($blnIsAjax=false)
	{
		$strFolder = Input::get('pid', true);

		if (!file_exists($this->strRootDir . '/' . $strFolder) || !$this->isMounted($strFolder))
		{
			throw new AccessDeniedException('Folder "' . $strFolder . '" is not mounted or is not a directory.');
		}

		if (!preg_match('/^' . preg_quote($this->strUploadPath, '/') . '/i', $strFolder))
		{
			throw new AccessDeniedException('Parent folder "' . $strFolder . '" is not within the files directory.');
		}

		// Empty clipboard
		if (!$blnIsAjax)
		{
			$objSession = System::getContainer()->get('request_stack')->getSession();
			$arrClipboard = $objSession->get('CLIPBOARD');
			$arrClipboard[$this->strTable] = array();
			$objSession->set('CLIPBOARD', $arrClipboard);
		}

		// Instantiate the uploader
		$class = BackendUser::getInstance()->uploader;

		// See #4086
		if (!class_exists($class))
		{
			$class = DropZone::class;
		}

		/** @var FileUpload $objUploader */
		$objUploader = new $class();

		// Process the uploaded files
		if (Input::post('FORM_SUBMIT') == 'tl_upload')
		{
			// Generate the DB entries
			if ($this->blnIsDbAssisted && Dbafs::shouldBeSynchronized($strFolder))
			{
				// Upload the files
				$arrUploaded = $objUploader->uploadTo($strFolder);

				if (empty($arrUploaded) && !$objUploader->hasError())
				{
					if ($blnIsAjax)
					{
						throw new ResponseException(new Response($GLOBALS['TL_LANG']['ERR']['emptyUpload'], 400));
					}

					Message::addError($GLOBALS['TL_LANG']['ERR']['emptyUpload']);
					$this->reload();
				}

				foreach ($arrUploaded as $strFile)
				{
					Dbafs::addResource($strFile);
				}
			}
			else
			{
				// Not DB-assisted, so just upload the file
				$arrUploaded = $objUploader->uploadTo($strFolder);
			}

			// HOOK: post upload callback
			if (isset($GLOBALS['TL_HOOKS']['postUpload']) && \is_array($GLOBALS['TL_HOOKS']['postUpload']))
			{
				foreach ($GLOBALS['TL_HOOKS']['postUpload'] as $callback)
				{
					if (\is_array($callback))
					{
						System::importStatic($callback[0])->{$callback[1]}($arrUploaded);
					}
					elseif (\is_callable($callback))
					{
						$callback($arrUploaded);
					}
				}
			}

			// Update the hash of the target folder
			if ($this->blnIsDbAssisted && Dbafs::shouldBeSynchronized($strFolder))
			{
				Dbafs::updateFolderHashes($strFolder);
			}

			$request = System::getContainer()->get('request_stack')->getCurrentRequest();
			$strMode = ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request)) ? 'BE' : 'FE';

			// Redirect or reload
			if (!$objUploader->hasError())
			{
				if ($blnIsAjax)
				{
					$objSession = System::getContainer()->get('request_stack')->getSession();

					if ($objSession->isStarted())
					{
						// Get the info messages only
						$arrMessages = $objSession->getFlashBag()->get('contao.' . $strMode . '.info');
						Message::reset();

						if (!empty($arrMessages))
						{
							throw new ResponseException(new Response('<p class="tl_info">' . implode('</p><p class="tl_info">', $arrMessages) . '</p>', 201));
						}
					}

					throw new ResponseException(new Response('', 201));
				}

				// Do not purge the html folder (see #2898)
				if (Input::post('uploadNback') !== null && !$objUploader->hasResized())
				{
					Message::reset();
					$this->redirect($this->getReferer());
				}

				$this->reload();
			}
			elseif ($blnIsAjax)
			{
				throw new ResponseException(new Response(Message::generateUnwrapped($strMode, true), 500));
			}
		}

		// Submit buttons
		$arrButtons = array();
		$arrButtons['upload'] = '<button type="submit" name="upload" class="tl_submit" accesskey="s">' . $GLOBALS['TL_LANG'][$this->strTable]['move'][0] . '</button>';
		$arrButtons['uploadNback'] = '<button type="submit" name="uploadNback" class="tl_submit" accesskey="c">' . $GLOBALS['TL_LANG'][$this->strTable]['uploadNback'] . '</button>';

		// Call the buttons_callback (see #4691)
		if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['edit']['buttons_callback'] ?? null))
		{
			foreach ($GLOBALS['TL_DCA'][$this->strTable]['edit']['buttons_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					$arrButtons = System::importStatic($callback[0])->{$callback[1]}($arrButtons, $this);
				}
				elseif (\is_callable($callback))
				{
					$arrButtons = $callback($arrButtons, $this);
				}
			}
		}

		if (\count($arrButtons) < 3)
		{
			$strButtons = implode(' ', $arrButtons);
		}
		else
		{
			$strButtons = array_shift($arrButtons) . ' ';
			$strButtons .= '<div class="split-button">';
			$strButtons .= array_shift($arrButtons) . '<button type="button" id="sbtog">' . Image::getHtml('navcol.svg') . '</button> <ul class="invisible">';

			foreach ($arrButtons as $strButton)
			{
				$strButtons .= '<li>' . $strButton . '</li>';
			}

			$strButtons .= '</ul></div>';
		}

		// Display the upload form
		return Message::generate() . '
<div id="tl_buttons">
<a href="' . $this->getReferer(true) . '" class="header_back" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']) . '" accesskey="b" onclick="Backend.getScrollOffset()">' . $GLOBALS['TL_LANG']['MSC']['backBT'] . '</a>
</div>
<form id="' . $this->strTable . '" class="tl_form tl_edit_form" method="post"' . (!empty($this->onsubmit) ? ' onsubmit="' . implode(' ', $this->onsubmit) . '"' : '') . ' enctype="multipart/form-data">
<div class="tl_formbody_edit">
<input type="hidden" name="FORM_SUBMIT" value="tl_upload">
<input type="hidden" name="REQUEST_TOKEN" value="' . htmlspecialchars(System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue()) . '">
<input type="hidden" name="MAX_FILE_SIZE" value="' . Config::get('maxFileSize') . '">
<div class="tl_tbox">
<div class="widget">
  <h3>' . $GLOBALS['TL_LANG'][$this->strTable]['fileupload'][0] . '</h3>' . $objUploader->generateMarkup() . '
</div>
</div>
</div>
<div class="tl_formbody_submit">
<div class="tl_submit_container">
  ' . $strButtons . '
</div>
</div>
</form>';
	}

	/**
	 * Auto-generate a form to rename a file or folder
	 *
	 * @return string
	 *
	 * @throws AccessDeniedException
	 */
	public function edit()
	{
		$return = '';
		$this->noReload = false;

		$this->isValid($this->intId);

		if (!file_exists($this->strRootDir . '/' . $this->intId) || !$this->isMounted($this->intId))
		{
			throw new AccessDeniedException('File or folder "' . $this->intId . '" is not mounted or cannot be found.');
		}

		$this->denyAccessUnlessGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new UpdateAction($this->strTable, array('id' => $this->intId)));

		$objModel = null;
		$objVersions = null;

		// Add the versioning routines
		if ($this->blnIsDbAssisted && Dbafs::shouldBeSynchronized($this->intId))
		{
			if (stripos($this->intId, '__new__') === false)
			{
				$objModel = FilesModel::findByPath($this->intId);

				if ($objModel === null)
				{
					$objModel = Dbafs::addResource($this->intId);
				}

				$this->objActiveRecord = $objModel;
				$this->blnCreateNewVersion = false;

				/** @var FilesModel $objModel */
				$objVersions = new Versions($this->strTable, $objModel->id);

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
						$this->reload();
					}
				}

				$objVersions->initialize();
			}
		}
		else
		{
			// Unset the database fields
			$GLOBALS['TL_DCA'][$this->strTable]['fields'] = array_intersect_key($GLOBALS['TL_DCA'][$this->strTable]['fields'] ?? array(), array('name' => true, 'protected' => true, 'syncExclude' => true));
		}

		$security = System::getContainer()->get('security.helper');

		// Build an array from boxes and rows (do not show excluded fields)
		$this->strPalette = $this->getPalette();
		$boxes = StringUtil::trimsplit(';', $this->strPalette);

		if (!empty($boxes))
		{
			// Get fields
			foreach ($boxes as $k=>$v)
			{
				$boxes[$k] = StringUtil::trimsplit(',', $v);

				foreach ($boxes[$k] as $kk=>$vv)
				{
					if (!isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$vv]) || (DataContainer::isFieldExcluded($this->strTable, $vv) && !$security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, $this->strTable . '::' . $vv)))
					{
						unset($boxes[$k][$kk]);
					}
				}

				// Unset a box if it does not contain any fields
				if (empty($boxes[$k]))
				{
					unset($boxes[$k]);
				}
			}

			// Render boxes
			$class = 'tl_tbox';

			foreach ($boxes as $v)
			{
				$return .= '
<div class="' . $class . ' cf">';

				// Build rows of the current box
				foreach ($v as $vv)
				{
					$this->strField = $vv;
					$this->strInputName = $vv;

					// Load the current value
					if ($vv == 'name')
					{
						$objFile = is_dir($this->strRootDir . '/' . $this->intId) ? new Folder($this->intId) : new File($this->intId);

						$this->strPath = StringUtil::stripRootDir($objFile->dirname);
						$this->strExtension = $objFile->origext ? '.' . $objFile->origext : '';
						$this->varValue = $objFile->filename;

						// Fix hidden Unix system files
						if (strncmp($this->varValue, '.', 1) === 0)
						{
							$this->strExtension = '';
						}

						// Clear the current value if it is a new folder
						if ($this->varValue == '__new__' && !\in_array(Input::post('FORM_SUBMIT'), array('tl_files', 'tl_templates')))
						{
							$this->varValue = '';
						}
					}
					else
					{
						$this->varValue = ($objModel !== null) ? $objModel->$vv : null;
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

					// Build row
					$return .= $this->row();
				}

				$class = 'tl_box';

				$return .= '
</div>';
			}
		}

		// Versions overview
		if ($objVersions && $this->blnIsDbAssisted && ($GLOBALS['TL_DCA'][$this->strTable]['config']['enableVersioning'] ?? null) && !($GLOBALS['TL_DCA'][$this->strTable]['config']['hideVersionMenu'] ?? null) && Dbafs::shouldBeSynchronized($this->intId))
		{
			$version = $objVersions->renderDropdown();
		}
		else
		{
			$version = '';
		}

		// Submit buttons
		$arrButtons = array();
		$arrButtons['save'] = '<button type="submit" name="save" id="save" class="tl_submit" accesskey="s">' . $GLOBALS['TL_LANG']['MSC']['save'] . '</button>';

		if (!Input::get('nb'))
		{
			$arrButtons['saveNclose'] = '<button type="submit" name="saveNclose" id="saveNclose" class="tl_submit" accesskey="c">' . $GLOBALS['TL_LANG']['MSC']['saveNclose'] . '</button>';
		}

		// Call the buttons_callback (see #4691)
		if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['edit']['buttons_callback'] ?? null))
		{
			foreach ($GLOBALS['TL_DCA'][$this->strTable]['edit']['buttons_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					$arrButtons = System::importStatic($callback[0])->{$callback[1]}($arrButtons, $this);
				}
				elseif (\is_callable($callback))
				{
					$arrButtons = $callback($arrButtons, $this);
				}
			}
		}

		if (\count($arrButtons) < 3)
		{
			$strButtons = implode(' ', $arrButtons);
		}
		else
		{
			$strButtons = array_shift($arrButtons) . ' ';
			$strButtons .= '<div class="split-button">';
			$strButtons .= array_shift($arrButtons) . '<button type="button" id="sbtog">' . Image::getHtml('navcol.svg') . '</button> <ul class="invisible">';

			foreach ($arrButtons as $strButton)
			{
				$strButtons .= '<li>' . $strButton . '</li>';
			}

			$strButtons .= '</ul></div>';
		}

		// Add the buttons and end the form
		$return .= '
</div>
<div class="tl_formbody_submit">
<div class="tl_submit_container">
  ' . $strButtons . '
</div>
</div>
</form>';

		// Begin the form (-> DO NOT CHANGE THIS ORDER -> this way the onsubmit attribute of the form can be changed by a field)
		$return = $version . Message::generate() . ($this->noReload ? '
<p class="tl_error">' . $GLOBALS['TL_LANG']['ERR']['general'] . '</p>' : '') . '
<div id="tl_buttons">
<a href="' . $this->getReferer(true) . '" class="header_back" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']) . '" accesskey="b" onclick="Backend.getScrollOffset()">' . $GLOBALS['TL_LANG']['MSC']['backBT'] . '</a>
</div>
<form id="' . $this->strTable . '" class="tl_form tl_edit_form" method="post"' . (!empty($this->onsubmit) ? ' onsubmit="' . implode(' ', $this->onsubmit) . '"' : '') . '>
<div class="tl_formbody_edit">
<input type="hidden" name="FORM_SUBMIT" value="' . $this->strTable . '">
<input type="hidden" name="REQUEST_TOKEN" value="' . htmlspecialchars(System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue()) . '">' . $return;

		// Always create a new version if something has changed, even if the form has errors (see #237)
		if ($this->noReload && $this->blnCreateNewVersion && $objModel !== null && Input::post('FORM_SUBMIT') == $this->strTable)
		{
			$objVersions->create();
		}

		// Reload the page to prevent _POST variables from being sent twice
		if (!$this->noReload && Input::post('FORM_SUBMIT') == $this->strTable)
		{
			// Trigger the onsubmit_callback
			if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['onsubmit_callback'] ?? null))
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

			// Set the current timestamp before creating a new version
			if ($this->blnIsDbAssisted && $objModel !== null)
			{
				Database::getInstance()
					->prepare("UPDATE " . $this->strTable . " SET tstamp=? WHERE id=?")
					->execute(time(), $objModel->id);
			}

			// Save the current version
			if ($this->blnCreateNewVersion && $objModel !== null)
			{
				$objVersions->create();
			}

			// Redirect
			if (Input::post('saveNclose') !== null)
			{
				Message::reset();
				$this->redirect($this->getReferer());
			}

			// Reload
			if ($this->blnIsDbAssisted && $this->objActiveRecord !== null)
			{
				$this->redirect($this->addToUrl('id=' . $this->urlEncode($this->objActiveRecord->path)));
			}
			else
			{
				$this->redirect($this->addToUrl('id=' . $this->urlEncode($this->intId)));
			}
		}

		// Set the focus if there is an error
		if ($this->noReload)
		{
			$return .= '
<script>
  window.addEvent(\'domready\', function() {
    Backend.vScrollTo(($(\'' . $this->strTable . '\').getElement(\'label.error\').getPosition().y - 20));
  });
</script>';
		}

		return $return;
	}

	/**
	 * Auto-generate a form to edit all records that are currently shown
	 *
	 * @return string
	 *
	 * @throws AccessDeniedException
	 */
	public function editAll()
	{
		$return = '';

		if ($GLOBALS['TL_DCA'][$this->strTable]['config']['notEditable'] ?? null)
		{
			throw new AccessDeniedException('Table "' . $this->strTable . '" is not editable.');
		}

		$security = System::getContainer()->get('security.helper');

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

		$fields = $session['CURRENT'][$this->strTable] ?? array();

		// Add fields
		if (!empty($fields) && \is_array($fields) && Input::get('fields'))
		{
			$class = 'tl_tbox';

			// Walk through each record
			foreach ($ids as $id)
			{
				try
				{
					$this->denyAccessUnlessGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new UpdateAction($this->strTable, array('id' => $id)));
				}
				catch (AccessDeniedException)
				{
					continue;
				}

				$this->intId = $id;
				$this->initialId = $id;
				$this->strPalette = StringUtil::trimsplit('[;,]', $this->getPalette());

				$objModel = null;
				$objVersions = null;

				// Get the DB entry
				if ($this->blnIsDbAssisted && Dbafs::shouldBeSynchronized($id))
				{
					$objModel = FilesModel::findByPath($id);

					if ($objModel === null)
					{
						$objModel = Dbafs::addResource($id);
					}

					$this->objActiveRecord = $objModel;
					$this->blnCreateNewVersion = false;

					/** @var FilesModel $objModel */
					$objVersions = new Versions($this->strTable, $objModel->id);
					$objVersions->initialize();
				}
				else
				{
					// Unset the database fields
					$this->strPalette = array_filter($this->strPalette, static function ($val) { return $val == 'name' || $val == 'protected'; });
				}

				$return .= '
<div class="' . $class . '">';

				$class = 'tl_box';
				$strHash = md5($id);

				foreach ($this->strPalette as $v)
				{
					if (!\in_array($v, $fields))
					{
						continue;
					}

					// Check whether field is excluded
					if (DataContainer::isFieldExcluded($this->strTable, $v) && !$security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, $this->strTable . '::' . $v))
					{
						continue;
					}

					$this->strField = $v;
					$this->strInputName = $v . '_' . $strHash;

					// Load the current value
					if ($v == 'name')
					{
						$objFile = is_dir($this->strRootDir . '/' . $id) ? new Folder($id) : new File($id);

						$this->strPath = StringUtil::stripRootDir($objFile->dirname);
						$this->strExtension = $objFile->origext ? '.' . $objFile->origext : '';
						$this->varValue = $objFile->filename;

						// Fix hidden Unix system files
						if (strncmp($this->varValue, '.', 1) === 0)
						{
							$this->strExtension = '';
						}
					}
					else
					{
						$this->varValue = ($objModel !== null) ? $objModel->$v : null;
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

					// Build the current row
					$return .= $this->row();
				}

				// Close box
				$return .= '
</div>';

				// Always create a new version if something has changed, even if the form has errors (see #237)
				if ($this->noReload && $this->blnCreateNewVersion && $objModel !== null && Input::post('FORM_SUBMIT') == $this->strTable)
				{
					$objVersions->create();
				}

				// Save the record
				if (!$this->noReload && Input::post('FORM_SUBMIT') == $this->strTable)
				{
					// Call onsubmit_callback
					if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['onsubmit_callback'] ?? null))
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

					// Set the current timestamp before adding a new version
					if ($this->blnIsDbAssisted && $objModel !== null)
					{
						Database::getInstance()
							->prepare("UPDATE " . $this->strTable . " SET tstamp=? WHERE id=?")
							->execute(time(), $objModel->id);
					}

					// Create a new version
					if ($this->blnCreateNewVersion && $objModel !== null)
					{
						$objVersions->create();
					}
				}
			}

			// Submit buttons
			$arrButtons = array();
			$arrButtons['save'] = '<button type="submit" name="save" id="save" class="tl_submit" accesskey="s">' . $GLOBALS['TL_LANG']['MSC']['save'] . '</button>';
			$arrButtons['saveNclose'] = '<button type="submit" name="saveNclose" id="saveNclose" class="tl_submit" accesskey="c">' . $GLOBALS['TL_LANG']['MSC']['saveNclose'] . '</button>';

			// Call the buttons_callback (see #4691)
			if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['edit']['buttons_callback'] ?? null))
			{
				foreach ($GLOBALS['TL_DCA'][$this->strTable]['edit']['buttons_callback'] as $callback)
				{
					if (\is_array($callback))
					{
						$arrButtons = System::importStatic($callback[0])->{$callback[1]}($arrButtons, $this);
					}
					elseif (\is_callable($callback))
					{
						$arrButtons = $callback($arrButtons, $this);
					}
				}
			}

			if (\count($arrButtons) < 3)
			{
				$strButtons = implode(' ', $arrButtons);
			}
			else
			{
				$strButtons = array_shift($arrButtons) . ' ';
				$strButtons .= '<div class="split-button">';
				$strButtons .= array_shift($arrButtons) . '<button type="button" id="sbtog">' . Image::getHtml('navcol.svg') . '</button> <ul class="invisible">';

				foreach ($arrButtons as $strButton)
				{
					$strButtons .= '<li>' . $strButton . '</li>';
				}

				$strButtons .= '</ul></div>';
			}

			// Add the form
			$return = '
<form id="' . $this->strTable . '" class="tl_form tl_edit_form" method="post">
<div class="tl_formbody_edit nogrid">
<input type="hidden" name="FORM_SUBMIT" value="' . $this->strTable . '">
<input type="hidden" name="REQUEST_TOKEN" value="' . htmlspecialchars(System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue()) . '">
<input type="hidden" name="IDS[]" value="' . implode('"><input type="hidden" name="IDS[]" value="', array_map(array($this, 'urlEncode'), $ids)) . '">' . ($this->noReload ? '
<p class="tl_error">' . $GLOBALS['TL_LANG']['ERR']['general'] . '</p>' : '') . $return . '
</div>
<div class="tl_formbody_submit">
<div class="tl_submit_container">
  ' . $strButtons . '
</div>
</div>
</form>';

			// Set the focus if there is an error
			if ($this->noReload)
			{
				$return .= '
<script>
  window.addEvent(\'domready\', function() {
    Backend.vScrollTo(($(\'' . $this->strTable . '\').getElement(\'label.error\').getPosition().y - 20));
  });
</script>';
			}

			// Reload the page to prevent _POST variables from being sent twice
			if (!$this->noReload && Input::post('FORM_SUBMIT') == $this->strTable)
			{
				if (Input::post('saveNclose') !== null)
				{
					$this->redirect($this->getReferer());
				}

				$this->reload();
			}
		}

		// Else show a form to select the fields
		else
		{
			$options = '';
			$fields = array();

			// Add fields of the current table
			$fields = array_merge($fields, array_keys($GLOBALS['TL_DCA'][$this->strTable]['fields'] ?? array()));

			// Show all non-excluded fields
			foreach ($fields as $field)
			{
				if ((!DataContainer::isFieldExcluded($this->strTable, $field) || $security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, $this->strTable . '::' . $field)) && !($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['doNotShow'] ?? null) && (isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['inputType']) || \is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['input_field_callback'] ?? null)))
				{
					$options .= '
  <input type="checkbox" name="all_fields[]" id="all_' . $field . '" class="tl_checkbox" value="' . StringUtil::specialchars($field) . '"> <label for="all_' . $field . '" class="tl_checkbox_label">' . (($GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['label'][0] ?? (\is_array($GLOBALS['TL_LANG']['MSC'][$field] ?? null) ? $GLOBALS['TL_LANG']['MSC'][$field][0] : ($GLOBALS['TL_LANG']['MSC'][$field] ?? null) ?? $field)) . ' <span class="label-info">[' . $field . ']</span>') . '</label><br>';
				}
			}

			$blnIsError = Input::isPost() && !Input::post('all_fields');

			// Return the select menu
			$return .= '
<form action="' . StringUtil::ampersand(Environment::get('requestUri')) . '&amp;fields=1" id="' . $this->strTable . '_all" class="tl_form tl_edit_form" method="post">
<div class="tl_formbody_edit">
<input type="hidden" name="FORM_SUBMIT" value="' . $this->strTable . '_all">
<input type="hidden" name="REQUEST_TOKEN" value="' . htmlspecialchars(System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue()) . '">
<input type="hidden" name="IDS[]" value="' . implode('"><input type="hidden" name="IDS[]" value="', array_map(array($this, 'urlEncode'), $ids)) . '">' . ($blnIsError ? '
<p class="tl_error">' . $GLOBALS['TL_LANG']['ERR']['general'] . '</p>' : '') . '
<div class="tl_tbox">
<div class="widget">
<fieldset class="tl_checkbox_container">
  <legend' . ($blnIsError ? ' class="error"' : '') . '>' . $GLOBALS['TL_LANG']['MSC']['all_fields'][0] . '<span class="mandatory">*</span></legend>
  <input type="checkbox" id="check_all" class="tl_checkbox" onclick="Backend.toggleCheckboxes(this)"> <label for="check_all" class="check-all"><em>' . $GLOBALS['TL_LANG']['MSC']['selectAll'] . '</em></label><br>' . $options . '
</fieldset>' . ($blnIsError ? '
<p class="tl_error">' . $GLOBALS['TL_LANG']['ERR']['all_fields'] . '</p>' : ((Config::get('showHelp') && isset($GLOBALS['TL_LANG']['MSC']['all_fields'][1])) ? '
<p class="tl_help tl_tip">' . $GLOBALS['TL_LANG']['MSC']['all_fields'][1] . '</p>' : '')) . '
</div>
</div>
</div>
<div class="tl_formbody_submit">
<div class="tl_submit_container">
  <button type="submit" name="save" id="save" class="tl_submit" accesskey="s">' . $GLOBALS['TL_LANG']['MSC']['continue'] . '</button>
</div>
</div>
</form>';
		}

		// Return
		return '
<div id="tl_buttons">
<a href="' . $this->getReferer(true) . '" class="header_back" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']) . '" accesskey="b" onclick="Backend.getScrollOffset()">' . $GLOBALS['TL_LANG']['MSC']['backBT'] . '</a>
</div>' . $return;
	}

	/**
	 * Load the source editor
	 *
	 * @return string
	 *
	 * @throws AccessDeniedException
	 * @throws UnprocessableEntityHttpException
	 * @throws NotFoundException
	 */
	public function source()
	{
		$this->isValid($this->intId);

		if (is_dir($this->strRootDir . '/' . $this->intId))
		{
			throw new UnprocessableEntityHttpException('Folder "' . $this->intId . '" cannot be edited.');
		}

		if (!file_exists($this->strRootDir . '/' . $this->intId))
		{
			throw new NotFoundException('File "' . $this->intId . '" does not exist.');
		}

		$this->denyAccessUnlessGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new UpdateAction($this->strTable, array('id' => $this->intId)));

		$objFile = new File($this->intId);

		// Check whether file type is editable
		if (!\in_array($objFile->extension, $this->arrEditableFileTypes))
		{
			throw new AccessDeniedException('File type "' . $objFile->extension . '" (' . $this->intId . ') is not allowed to be edited.');
		}

		$objMeta = null;
		$objVersions = null;

		// Add the versioning routines
		if ($this->blnIsDbAssisted && Dbafs::shouldBeSynchronized($this->intId))
		{
			$objMeta = FilesModel::findByPath($objFile->value);

			if ($objMeta === null)
			{
				$objMeta = Dbafs::addResource($objFile->value);
			}

			$objVersions = new Versions($this->strTable, $objMeta->id);

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

					// Purge the script cache (see #7005)
					$this->purgeCache($objFile->path);
					$this->reload();
				}
			}

			$objVersions->initialize();
		}

		$strContent = $objFile->getContent();

		if ($objFile->extension == 'svgz')
		{
			$strContent = gzdecode($strContent);
		}

		// Process the request
		if (Input::post('FORM_SUBMIT') == 'tl_files')
		{
			$strSource = System::getContainer()->get('request_stack')->getCurrentRequest()->request->get('source');
			$strEnding = preg_match_all('/(?<!\r)\n/', $strContent) >= preg_match_all('/\r\n/', $strContent) ? "\n" : "\r\n";
			$strSource = str_replace("\r\n", $strEnding, $strSource); // see #5096

			// Save the file
			if (md5($strContent) != md5($strSource))
			{
				if ($objFile->extension == 'svgz')
				{
					$strSource = gzencode($strSource);
				}

				// Write the file
				$objFile->write($strSource);
				$objFile->close();

				// Update the database
				if ($this->blnIsDbAssisted && $objMeta !== null)
				{
					/** @var FilesModel $objMeta */
					$objMeta->hash = $objFile->hash;
					$objMeta->save();

					$objVersions->create();
				}

				// Purge the script cache (see #7005)
				$this->purgeCache($objFile->path);
			}

			if (Input::post('saveNclose') !== null)
			{
				$this->redirect($this->getReferer());
			}

			$this->reload();
		}

		$codeEditor = '';

		// Prepare the code editor
		if (Config::get('useCE'))
		{
			$objTemplate = new BackendTemplate('be_ace');
			$objTemplate->selector = 'ctrl_source';
			$objTemplate->type = $objFile->extension;

			$codeEditor = $objTemplate->parse();
		}

		// Versions overview
		if ($this->blnIsDbAssisted && $objVersions !== null && ($GLOBALS['TL_DCA'][$this->strTable]['config']['enableVersioning'] ?? null) && !($GLOBALS['TL_DCA'][$this->strTable]['config']['hideVersionMenu'] ?? null))
		{
			$version = $objVersions->renderDropdown();
		}
		else
		{
			$version = '';
		}

		// Submit buttons
		$arrButtons = array();
		$arrButtons['save'] = '<button type="submit" name="save" id="save" class="tl_submit" accesskey="s">' . $GLOBALS['TL_LANG']['MSC']['save'] . '</button>';
		$arrButtons['saveNclose'] = '<button type="submit" name="saveNclose" id="saveNclose" class="tl_submit" accesskey="c">' . $GLOBALS['TL_LANG']['MSC']['saveNclose'] . '</button>';

		// Call the buttons_callback (see #4691)
		if (\is_array($GLOBALS['TL_DCA'][$this->strTable]['edit']['buttons_callback'] ?? null))
		{
			foreach ($GLOBALS['TL_DCA'][$this->strTable]['edit']['buttons_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					$arrButtons = System::importStatic($callback[0])->{$callback[1]}($arrButtons, $this);
				}
				elseif (\is_callable($callback))
				{
					$arrButtons = $callback($arrButtons, $this);
				}
			}
		}

		if (\count($arrButtons) < 3)
		{
			$strButtons = implode(' ', $arrButtons);
		}
		else
		{
			$strButtons = array_shift($arrButtons) . ' ';
			$strButtons .= '<div class="split-button">';
			$strButtons .= array_shift($arrButtons) . '<button type="button" id="sbtog">' . Image::getHtml('navcol.svg') . '</button> <ul class="invisible">';

			foreach ($arrButtons as $strButton)
			{
				$strButtons .= '<li>' . $strButton . '</li>';
			}

			$strButtons .= '</ul></div>';
		}

		// Add the form
		return $version . Message::generate() . '
<div id="tl_buttons">
<a href="' . $this->getReferer(true) . '" class="header_back" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']) . '" accesskey="b" onclick="Backend.getScrollOffset()">' . $GLOBALS['TL_LANG']['MSC']['backBT'] . '</a>
</div>
<form id="tl_files" class="tl_form tl_edit_form" method="post">
<div class="tl_formbody_edit">
<input type="hidden" name="FORM_SUBMIT" value="tl_files">
<input type="hidden" name="REQUEST_TOKEN" value="' . htmlspecialchars(System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue()) . '">
<div class="tl_tbox">
  <div class="widget">
    <h3><label for="ctrl_source">' . $GLOBALS['TL_LANG']['tl_files']['editor'][0] . '</label></h3>
    <textarea name="source" id="ctrl_source" class="tl_textarea monospace" rows="12" cols="80" style="height:400px" onfocus="Backend.getScrollOffset()">' . "\n" . htmlspecialchars($strContent) . '</textarea>' . ((Config::get('showHelp') && isset($GLOBALS['TL_LANG']['tl_files']['editor'][1])) ? '
    <p class="tl_help tl_tip">' . $GLOBALS['TL_LANG']['tl_files']['editor'][1] . '</p>' : '') . '
  </div>
</div>
</div>
<div class="tl_formbody_submit">
<div class="tl_submit_container">
  ' . $strButtons . '
</div>
</div>
</form>' . "\n\n" . $codeEditor;
	}

	private function purgeCache(string ...$affectedPaths): void
	{
		$extensions = array_unique(array_map(
			static function (string $path): string {
				return Path::getExtension($path, true);
			},
			$affectedPaths
		));

		if (!empty(array_intersect(array('css', 'scss', 'less', 'js'), $extensions)))
		{
			(new Automator())->purgeScriptCache();
		}

		$bundleTemplatePaths = array_filter(
			$affectedPaths,
			static function (string $path): bool {
				return Path::isBasePath('templates/bundles', $path) && empty(Path::getExtension($path));
			}
		);

		if (!empty($bundleTemplatePaths))
		{
			// If a bundle template path is changed, the whole container cache needs to
			// be rebuilt because the template paths are added at compile time
			$cacheItemPool = System::getContainer()->get('cache.system');

			$item = $cacheItemPool->getItem(BackendRebuildCacheMessageListener::CACHE_DIRTY_FLAG);
			$item->set(true);

			$cacheItemPool->save($item);

			return;
		}

		if (\in_array('twig', $extensions, true))
		{
			$twigCache = System::getContainer()->get('twig')->getCache();

			if (\is_string($twigCache) && is_dir($twigCache))
			{
				(new Filesystem())->remove($twigCache);
			}
		}
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

		// File names
		if ($this->strField == 'name')
		{
			if ($this->varValue === $varValue || !file_exists($this->strRootDir . '/' . $this->strPath . '/' . $this->varValue . $this->strExtension) || !$this->isMounted($this->strPath . '/' . $this->varValue . $this->strExtension))
			{
				return;
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

			// The target exists
			if (strcasecmp($this->strPath . '/' . $this->varValue . $this->strExtension, $this->strPath . '/' . $varValue . $this->strExtension) !== 0 && file_exists($this->strRootDir . '/' . $this->strPath . '/' . $varValue . $this->strExtension))
			{
				throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['fileExists'], $varValue));
			}

			$filesObj = Files::getInstance();
			$arrImageTypes = System::getContainer()->getParameter('contao.image.valid_extensions');

			// Remove potentially existing thumbnails (see #6641)
			if (\in_array(substr($this->strExtension, 1), $arrImageTypes))
			{
				foreach (glob(System::getContainer()->getParameter('contao.image.target_dir') . '/*/' . $this->varValue . '-*' . $this->strExtension) as $strThumbnail)
				{
					$filesObj->delete(StringUtil::stripRootDir($strThumbnail));
				}
			}

			// Rename the file
			$filesObj->rename($this->strPath . '/' . $this->varValue . $this->strExtension, $this->strPath . '/' . $varValue . $this->strExtension);

			$this->purgeCache($this->strPath . '/' . $this->varValue . $this->strExtension, $this->strPath . '/' . $varValue . $this->strExtension);

			// New folders
			if (stripos($this->intId, '__new__') !== false)
			{
				// Update the database
				if ($this->blnIsDbAssisted && Dbafs::shouldBeSynchronized($this->strPath . '/' . $varValue . $this->strExtension))
				{
					$this->objActiveRecord = Dbafs::addResource($this->strPath . '/' . $varValue . $this->strExtension);
				}

				System::getContainer()->get('monolog.logger.contao.files')->info('Folder "' . $this->strPath . '/' . $varValue . $this->strExtension . '" has been created');
			}
			else
			{
				// Update the database
				if ($this->blnIsDbAssisted)
				{
					$syncSource = Dbafs::shouldBeSynchronized($this->strPath . '/' . $this->varValue . $this->strExtension);
					$syncTarget = Dbafs::shouldBeSynchronized($this->strPath . '/' . $varValue . $this->strExtension);

					if ($syncSource && $syncTarget)
					{
						Dbafs::moveResource($this->strPath . '/' . $this->varValue . $this->strExtension, $this->strPath . '/' . $varValue . $this->strExtension);
					}
					elseif ($syncSource)
					{
						Dbafs::deleteResource($this->strPath . '/' . $this->varValue . $this->strExtension);
					}
					elseif ($syncTarget)
					{
						Dbafs::addResource($this->strPath . '/' . $varValue . $this->strExtension);
					}
				}

				System::getContainer()->get('monolog.logger.contao.files')->info('File or folder "' . $this->strPath . '/' . $this->varValue . $this->strExtension . '" has been renamed to "' . $this->strPath . '/' . $varValue . $this->strExtension . '"');
			}

			$strWebDir = StringUtil::stripRootDir(System::getContainer()->getParameter('contao.web_dir'));

			// Update the symlinks
			if (is_link($this->strRootDir . '/' . $strWebDir . '/' . $this->strPath . '/' . $this->varValue . $this->strExtension))
			{
				$filesObj->delete($strWebDir . '/' . $this->strPath . '/' . $this->varValue . $this->strExtension);
				SymlinkUtil::symlink($this->strPath . '/' . $varValue . $this->strExtension, $strWebDir . '/' . $this->strPath . '/' . $varValue . $this->strExtension, $this->strRootDir);
			}

			// Set the new value so the input field can show it
			if (Input::get('act') == 'editAll')
			{
				$objSession = System::getContainer()->get('request_stack')->getSession();
				$session = $objSession->all();

				if (($index = array_search($this->strPath . '/' . $this->varValue . $this->strExtension, $session['CURRENT']['IDS'])) !== false)
				{
					$session['CURRENT']['IDS'][$index] = $this->strPath . '/' . $varValue . $this->strExtension;
					$objSession->replace($session);
				}
			}

			$this->varValue = $varValue;
			$this->intId = $this->strPath . '/' . $varValue . $this->strExtension;
		}
		elseif ($this->blnIsDbAssisted && $this->objActiveRecord !== null)
		{
			// Convert date formats into timestamps
			if ($varValue !== null && $varValue !== '' && \in_array($arrData['eval']['rgxp'] ?? null, array('date', 'time', 'datim')))
			{
				$objDate = new Date($varValue, Date::getFormatFromRgxp($arrData['eval']['rgxp']));
				$varValue = $objDate->tstamp;
			}

			// Handle multi-select fields in "override all" mode
			if ($this->objActiveRecord !== null && (($arrData['inputType'] ?? null) == 'checkbox' || ($arrData['inputType'] ?? null) == 'checkboxWizard') && ($arrData['eval']['multiple'] ?? null) && Input::get('act') == 'overrideAll')
			{
				$new = StringUtil::deserialize($varValue, true);
				$old = StringUtil::deserialize($this->objActiveRecord->{$this->strField}, true);

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
					$varValue = '';
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

			$db = Database::getInstance();

			// Make sure unique fields are unique
			if (($arrData['eval']['unique'] ?? null) && (\is_array($varValue) || (string) $varValue !== '') && !$db->isUniqueValue($this->strTable, $this->strField, $varValue, $this->objActiveRecord->id))
			{
				throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['unique'], $arrData['label'][0] ?? $this->strField));
			}

			// Save the value if there was no error
			if ((\is_array($varValue) || (string) $varValue !== '' || !($arrData['eval']['doNotSaveEmpty'] ?? null)) && ($this->varValue != $varValue || ($arrData['eval']['alwaysSave'] ?? null)))
			{
				// If the field is a fallback field, empty the other columns
				if ($varValue && ($arrData['eval']['fallback'] ?? null))
				{
					$db->execute("UPDATE " . $this->strTable . " SET " . $this->strField . "=''");
				}

				// Set the correct empty value (see #6284, #6373)
				if (!\is_array($varValue) && (string) $varValue === '')
				{
					$varValue = Widget::getEmptyValueByFieldType($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['sql'] ?? array());
				}

				$this->objActiveRecord->{$this->strField} = $varValue;
				$this->objActiveRecord->save();

				if (!isset($arrData['eval']['versionize']) || $arrData['eval']['versionize'] !== false)
				{
					$this->blnCreateNewVersion = true;
				}

				$this->varValue = StringUtil::deserialize($varValue);
			}
		}
	}

	/**
	 * Synchronize the file system with the database
	 *
	 * @return string
	 */
	public function sync()
	{
		if (!$this->blnIsDbAssisted)
		{
			return '';
		}

		$container = System::getContainer();

		// Synchronize
		$changeSet = $container->get('contao.filesystem.dbafs_manager')->sync();

		return $container->get('twig')->render(
			'@ContaoCore/Backend/be_filesync_report.html.twig',
			array(
				'change_set' => $changeSet,
				'referer' => $this->getReferer(true),
			)
		);
	}

	/**
	 * Return the name of the current palette
	 *
	 * @return string
	 */
	public function getPalette()
	{
		return $GLOBALS['TL_DCA'][$this->strTable]['palettes']['default'];
	}

	/**
	 * Generate a particular subpart of the tree and return it as HTML string
	 *
	 * @param string  $strFolder
	 * @param integer $level
	 *
	 * @return string
	 */
	public function ajaxTreeView($strFolder, $level)
	{
		if (!Environment::get('isAjaxRequest'))
		{
			return '';
		}

		$this->isValid($strFolder);

		if (!is_dir($this->strRootDir . '/' . $strFolder) || (!$this->isMounted($strFolder) && !\in_array($strFolder, $this->visibleRootTrails, true)))
		{
			throw new AccessDeniedException('Folder "' . $strFolder . '" is not mounted, not within the visible root trails or cannot be found.');
		}

		$objSession = System::getContainer()->get('request_stack')->getSession();
		$blnClipboard = false;
		$arrClipboard = $objSession->get('CLIPBOARD');

		// Check clipboard
		if (!empty($arrClipboard[$this->strTable]))
		{
			$blnClipboard = true;
			$arrClipboard = $arrClipboard[$this->strTable];
		}
		else
		{
			$arrClipboard = null;
		}

		return $this->generateTree($this->strRootDir . '/' . $strFolder, ($level + 1) * 18, false, $this->isProtectedPath($strFolder), $blnClipboard ? $arrClipboard : false);
	}

	/**
	 * Render the file tree and return it as HTML string
	 *
	 * @param string  $path
	 * @param integer $intMargin
	 * @param boolean $mount
	 * @param boolean $blnProtected
	 * @param array   $arrClipboard
	 * @param array   $arrFound
	 *
	 * @return string
	 */
	protected function generateTree($path, $intMargin, $mount=false, $blnProtected=true, $arrClipboard=null, $arrFound=array())
	{
		/** @var AttributeBagInterface $objSessionBag */
		$objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');
		$session = $objSessionBag->all();

		// Get the session data and toggle the nodes
		if (Input::get('tg'))
		{
			if (isset($session['filetree'][Input::get('tg')]) && $session['filetree'][Input::get('tg')] == 1)
			{
				unset($session['filetree'][Input::get('tg')]);
			}
			else
			{
				$session['filetree'][Input::get('tg')] = 1;
			}

			$objSessionBag->replace($session);
			$this->redirect(preg_replace('/(&(amp;)?|\?)tg=[^& ]*/i', '', Environment::get('requestUri')));
		}

		$return = '';
		$files = array();
		$folders = array();
		$intSpacing = 18;
		$level = $intMargin / $intSpacing;

		// Mount folder
		if ($mount)
		{
			$folders = array($path);
		}

		// Scan directory and sort the result
		else
		{
			$filesObj = Files::getInstance();

			foreach (Folder::scan($path) as $v)
			{
				if (strncmp($v, '.', 1) === 0)
				{
					continue;
				}

				if (preg_match('//u', $v) !== 1)
				{
					trigger_error(sprintf('Path "%s" contains malformed UTF-8 characters.', $path . '/' . $v), E_USER_WARNING);
					continue;
				}

				if (is_file($path . '/' . $v))
				{
					$files[] = $path . '/' . $v;
				}
				elseif ($v == '__new__')
				{
					$filesObj->rrdir(StringUtil::stripRootDir($path) . '/' . $v);
				}
				else
				{
					$folders[] = $path . '/' . $v;
				}
			}

			natcasesort($folders);
			$folders = array_values($folders);

			natcasesort($files);
			$files = array_values($files);
		}

		$user = BackendUser::getInstance();
		$security = System::getContainer()->get('security.helper');

		// Folders
		for ($f=0, $c=\count($folders); $f<$c; $f++)
		{
			$currentFolder = StringUtil::stripRootDir($folders[$f]);

			// Hide unsynchronized folders in the picker (see #919)
			if ($this->strPickerFieldType && !Dbafs::shouldBeSynchronized($currentFolder))
			{
				continue;
			}

			if (!$this->isMounted($currentFolder) && !\in_array($currentFolder, $this->visibleRootTrails, true))
			{
				continue;
			}

			$md5 = substr(md5($folders[$f]), 0, 8);
			$content = Folder::scan($folders[$f]);
			$session['filetree'][$md5] = is_numeric($session['filetree'][$md5] ?? null) ? $session['filetree'][$md5] : 0;
			$currentEncoded = $this->urlEncode($currentFolder);
			$countFiles = \count($content);

			// Subtract files that will not be shown
			foreach ($content as $file)
			{
				if (strncmp($file, '.', 1) === 0)
				{
					--$countFiles;
				}
				elseif (!empty($arrFound) && !\in_array($currentFolder . '/' . $file, $arrFound) && !preg_grep('/^' . preg_quote($currentFolder . '/' . $file, '/') . '\//', $arrFound))
				{
					--$countFiles;
				}
				elseif (!$this->blnFiles && !$this->blnFilesOnly && !is_dir($this->strRootDir . '/' . $currentFolder . '/' . $file))
				{
					--$countFiles;
				}
				elseif (!empty($this->arrValidFileTypes) && !is_dir($this->strRootDir . '/' . $currentFolder . '/' . $file))
				{
					$objFile = new File($currentFolder . '/' . $file);

					if (!\in_array($objFile->extension, $this->arrValidFileTypes))
					{
						--$countFiles;
					}
				}
			}

			if (!empty($arrFound) && $countFiles < 1 && !\in_array($currentFolder, $arrFound))
			{
				continue;
			}

			$blnIsOpen = !empty($arrFound) || $session['filetree'][$md5] == 1;

			// Always show selected nodes
			if (!$blnIsOpen && !empty($this->arrPickerValue) && \count(preg_grep('/^' . preg_quote($this->urlEncode($currentFolder), '/') . '\//', $this->arrPickerValue)))
			{
				$blnIsOpen = true;
			}

			$return .= "\n  " . '<li data-id="' . htmlspecialchars($currentFolder, ENT_QUOTES) . '" class="tl_folder click2edit toggle_select hover-div"><div class="tl_left" style="padding-left:' . ($intMargin + (($countFiles < 1) ? 18 : 0)) . 'px">';

			// Add a toggle button if there are children
			if ($countFiles > 0)
			{
				$class = $blnIsOpen ? 'foldable foldable--open' : 'foldable';
				$alt = $blnIsOpen ? $GLOBALS['TL_LANG']['MSC']['collapseNode'] : $GLOBALS['TL_LANG']['MSC']['expandNode'];
				$return .= '<a href="' . $this->addToUrl('tg=' . $md5) . '" title="' . StringUtil::specialchars($alt) . '" class="' . $class . '" data-contao--toggle-nodes-target="toggle" data-action="contao--toggle-nodes#toggle" data-contao--toggle-nodes-id-param="filetree_' . $md5 . '" data-contao--toggle-nodes-folder-param="' . $currentFolder . '" data-contao--toggle-nodes-level-param="' . $level . '">' . Image::getHtml('chevron-right.svg') . '</a>';
			}

			$protected = $blnProtected;

			// Check whether the folder is public
			if ($protected === true && \in_array('.public', $content) && !is_dir(Path::join($folders[$f], '.public')))
			{
				$protected = false;
			}

			$folderImg = $protected ? 'folderCP.svg' : 'folderC.svg';
			$folderAlt = $protected ? $GLOBALS['TL_LANG']['MSC']['folderCP'] : $GLOBALS['TL_LANG']['MSC']['folderC'];

			// Add the current folder
			$strFolderNameEncoded = StringUtil::convertEncoding(StringUtil::specialchars(basename($currentFolder)), System::getContainer()->getParameter('kernel.charset'));
			$strFolderLabel = '<strong>' . $strFolderNameEncoded . '</strong>';

			if ($this->isMounted($currentFolder))
			{
				$strFolderLabel = '<a href="' . $this->addToUrl('fn=' . $currentEncoded) . '" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['selectNode']) . '">' . $strFolderLabel . '</a>';
			}

			$return .= Image::getHtml($folderImg, $folderAlt) . ' ' . $strFolderLabel . '</div> <div class="tl_right">';

			if ($this->isMounted($currentFolder))
			{
				// Paste buttons
				if ($arrClipboard !== false && Input::get('act') != 'select')
				{
					$labelPasteInto = $GLOBALS['TL_LANG'][$this->strTable]['pasteinto'] ?? $GLOBALS['TL_LANG']['DCA']['pasteinto'];
					$imagePasteInto = Image::getHtml('pasteinto.svg', sprintf($labelPasteInto[1], $currentEncoded));

					if ((\in_array($arrClipboard['mode'], array('copy', 'cut')) && (($arrClipboard['mode'] == 'cut' && \dirname($arrClipboard['id']) == $currentFolder) || preg_match('#^' . preg_quote(rawurldecode($arrClipboard['id']), '#') . '(/|$)#i', $currentFolder))) || !$security->isGranted(ContaoCorePermissions::DC_PREFIX . $this->strTable, new CreateAction($this->strTable, array('pid' => $currentFolder))))
					{
						$return .= Image::getHtml('pasteinto--disabled.svg');
					}
					else
					{
						$return .= '<a href="' . $this->addToUrl('act=' . $arrClipboard['mode'] . '&amp;mode=2&amp;pid=' . $currentEncoded . (!\is_array($arrClipboard['id']) ? '&amp;id=' . $arrClipboard['id'] : '')) . '" title="' . StringUtil::specialchars(sprintf($labelPasteInto[1], $currentEncoded)) . '" onclick="Backend.getScrollOffset()">' . $imagePasteInto . '</a> ';
					}
				}
				// Default buttons
				else
				{
					$uploadButton = ' <a href="' . $this->addToUrl('&amp;act=move&amp;mode=2&amp;pid=' . $currentEncoded) . '" title="' . StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['tl_files']['uploadFF'], $currentEncoded)) . '">' . Image::getHtml('new.svg', $GLOBALS['TL_LANG']['tl_files']['move'][0]) . '</a>';

					// Only show the upload button for mounted folders
					if (!$user->isAdmin && \in_array($currentFolder, $user->filemounts))
					{
						$return .= $uploadButton;
					}
					else
					{
						$return .= (Input::get('act') == 'select') ? '<input type="checkbox" name="IDS[]" id="ids_' . md5($currentEncoded) . '" class="tl_tree_checkbox" value="' . $currentEncoded . '">' : $this->generateButtons(array('id'=>$currentEncoded, 'fileNameEncoded'=>$strFolderNameEncoded, 'type'=>'folder'), $this->strTable);
					}

					if ($this->strPickerFieldType)
					{
						$return .= $this->getPickerInputField($currentEncoded, $this->blnFilesOnly ? ' disabled' : '');
					}
				}
			}

			$return .= '</div><div style="clear:both"></div></li>';

			// Call the next node
			if (!empty($content) && $blnIsOpen)
			{
				$return .= '<li class="parent" id="filetree_' . $md5 . '" data-contao--toggle-nodes-target="child' . ($level === 0 ? ' rootChild' : '') . '"><ul class="level_' . $level . '">';
				$return .= $this->generateTree($folders[$f], $intMargin + $intSpacing, false, $protected, $arrClipboard, $arrFound);
				$return .= '</ul></li>';
			}
		}

		if (!$this->blnFiles && !$this->blnFilesOnly)
		{
			return $return;
		}

		$staticUrl = System::getContainer()->get('contao.assets.files_context')->getStaticUrl();

		// Process files
		for ($h=0, $c=\count($files); $h<$c; $h++)
		{
			$thumbnail = '';
			$currentFile = StringUtil::stripRootDir($files[$h]);

			// Ignore files not matching the search criteria
			if (!empty($arrFound) && !\in_array($currentFile, $arrFound))
			{
				continue;
			}

			if (!$this->isMounted($currentFile))
			{
				continue;
			}

			$objFile = new File($currentFile);

			if (!empty($this->arrValidFileTypes) && !\in_array($objFile->extension, $this->arrValidFileTypes))
			{
				continue;
			}

			$currentEncoded = $this->urlEncode($currentFile);
			$return .= "\n  " . '<li data-id="' . htmlspecialchars($currentFile, ENT_QUOTES) . '" class="tl_file click2edit toggle_select hover-div"><div class="tl_left" style="padding-left:' . ($intMargin + $intSpacing) . 'px">';
			$thumbnail .= ' <span class="tl_gray">(' . $this->getReadableSize($objFile->filesize);

			if ($objFile->width && $objFile->height)
			{
				$thumbnail .= ', ' . $objFile->width . 'x' . $objFile->height . ' px';
			}

			$thumbnail .= ')</span>';

			// Generate the thumbnail
			if ($objFile->isImage && (!$objFile->isSvgImage || $objFile->viewHeight > 0) && Config::get('thumbnails') && \in_array($objFile->extension, System::getContainer()->getParameter('contao.image.valid_extensions')))
			{
				try
				{
					$thumbnail .= '<br>' . $this->getPreviewImage(rawurldecode($currentEncoded));

					$importantPart = System::getContainer()->get('contao.image.factory')->create($this->strRootDir . '/' . rawurldecode($currentEncoded))->getImportantPart();

					if ($importantPart->getX() > 0 || $importantPart->getY() > 0 || $importantPart->getWidth() < 1 || $importantPart->getHeight() < 1)
					{
						$thumbnail .= ' ' . $this->getPreviewImage(rawurldecode($currentEncoded), true);
					}
				}
				catch (RuntimeException $e)
				{
					$thumbnail .= '<br><p class="preview-image broken-image">Broken image!</p>';
				}
			}

			$strFileNameEncoded = StringUtil::convertEncoding(StringUtil::specialchars(basename($currentFile)), System::getContainer()->getParameter('kernel.charset'));
			$iconAlt = sprintf($GLOBALS['TL_LANG']['MSC']['typeOfFile'], strtoupper($objFile->extension));

			// No popup links for protected files and templates (see #700)
			if ($blnProtected || $this->strTable == 'tl_templates')
			{
				$return .= Image::getHtml($objFile->icon, $iconAlt) . ' ' . $strFileNameEncoded . $thumbnail . '</div> <div class="tl_right">';
			}
			else
			{
				$return .= '<a href="' . $staticUrl . $currentEncoded . '" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['view']) . '" target="_blank">' . Image::getHtml($objFile->icon, $iconAlt) . '</a> ' . $strFileNameEncoded . $thumbnail . '</div> <div class="tl_right">';
			}

			// Buttons
			if ($arrClipboard !== false && Input::get('act') != 'select')
			{
				$_buttons = '&nbsp;';
			}
			else
			{
				$_buttons = (Input::get('act') == 'select') ? '<input type="checkbox" name="IDS[]" id="ids_' . md5($currentEncoded) . '" class="tl_tree_checkbox" value="' . $currentEncoded . '">' : $this->generateButtons(array('id'=>$currentEncoded, 'fileNameEncoded'=>$strFileNameEncoded, 'type'=>'file'), $this->strTable);

				if ($this->strPickerFieldType)
				{
					$_buttons .= $this->getPickerInputField($currentEncoded);
				}
			}

			$return .= $_buttons . '</div><div style="clear:both"></div></li>';
		}

		return $return;
	}

	/**
	 * Return a search form that allows to search results using regular expressions
	 *
	 * @return string
	 */
	protected function searchMenu()
	{
		/** @var AttributeBagInterface $objSessionBag */
		$objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');

		$session = $objSessionBag->all();

		// Store search value in the current session
		if (Input::post('FORM_SUBMIT') == 'tl_filters')
		{
			$strKeyword = ltrim(Input::postRaw('tl_value'), '*');

			$session['search'][$this->strTable]['value'] = $strKeyword;

			$objSessionBag->replace($session);
		}

		$active = isset($session['search'][$this->strTable]['value']) && (string) $session['search'][$this->strTable]['value'] !== '';

		return '
    <div class="tl_search tl_subpanel">
      <strong>' . $GLOBALS['TL_LANG']['MSC']['search'] . ':</strong>
      <select name="tl_field" class="tl_select tl_chosen' . ($active ? ' active' : '') . '">
        <option value="name">' . ($GLOBALS['TL_DCA'][$this->strTable]['fields']['name']['label'][0] ?: (\is_array($GLOBALS['TL_LANG']['MSC']['name'] ?? null) ? $GLOBALS['TL_LANG']['MSC']['name'][0] : ($GLOBALS['TL_LANG']['MSC']['name'] ?? null))) . '</option>
      </select>
      <span>=</span>
      <input type="search" name="tl_value" class="tl_text' . ($active ? ' active' : '') . '" value="' . StringUtil::specialchars($session['search'][$this->strTable]['value'] ?? '') . '">
    </div>';
	}

	/**
	 * Return true if the current folder is mounted
	 *
	 * @param string $strFolder
	 *
	 * @return boolean
	 */
	protected function isMounted($strFolder)
	{
		if (!$strFolder)
		{
			return false;
		}

		if (empty($this->arrFilemounts) && !\is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['root'] ?? null) && ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['root'] ?? null) !== false)
		{
			return true;
		}

		foreach ($this->arrFilemounts as $basePath)
		{
			if (Path::isBasePath($basePath, $strFolder))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Check a file operation
	 *
	 * @param string $strFile
	 *
	 * @return boolean
	 *
	 * @throws AccessDeniedException
	 */
	protected function isValid($strFile)
	{
		$strFolder = Input::get('pid', true);

		// Check the path
		if (Validator::isInsecurePath($strFile))
		{
			throw new AccessDeniedException('Invalid file name "' . $strFile . '" (hacking attempt).');
		}

		if ($strFolder && Validator::isInsecurePath($strFolder))
		{
			throw new AccessDeniedException('Invalid folder name "' . $strFolder . '" (hacking attempt).');
		}

		// Check for valid file types
		if (!empty($this->arrValidFileTypes) && is_file($this->strRootDir . '/' . $strFile))
		{
			$fileinfo = preg_replace('/.*\.(.*)$/u', '$1', $strFile);

			if (!\in_array(strtolower($fileinfo), $this->arrValidFileTypes))
			{
				throw new AccessDeniedException('File "' . $strFile . '" is not an allowed file type.');
			}
		}

		// Check whether the file is within the files directory
		if (!preg_match('/^' . preg_quote($this->strUploadPath, '/') . '/i', $strFile))
		{
			throw new AccessDeniedException('File or folder "' . $strFile . '" is not within the files directory.');
		}

		// Check whether the parent folder is within the files directory
		if ($strFolder && !preg_match('/^' . preg_quote($this->strUploadPath, '/') . '/i', $strFolder))
		{
			throw new AccessDeniedException('Parent folder "' . $strFolder . '" is not within the files directory.');
		}

		// Do not allow file operations on root folders
		if (\in_array(Input::get('act'), array('edit', 'paste', 'delete')))
		{
			$user = BackendUser::getInstance();

			if (!$user->isAdmin && \in_array($strFile, $user->filemounts))
			{
				throw new AccessDeniedException('Attempt to edit, copy, move or delete the root folder "' . $strFile . '".');
			}
		}

		return true;
	}

	/**
	 * Return an array of encrypted folder names
	 *
	 * @param string $strPath
	 *
	 * @return array
	 */
	protected function getMD5Folders($strPath)
	{
		$arrFiles = array();

		foreach (Folder::scan($this->strRootDir . '/' . $strPath) as $strFile)
		{
			if (!is_dir($this->strRootDir . '/' . $strPath . '/' . $strFile))
			{
				continue;
			}

			$arrFiles[substr(md5($this->strRootDir . '/' . $strPath . '/' . $strFile), 0, 8)] = 1;

			// Do not use array_merge() here (see #8105)
			foreach ($this->getMD5Folders($strPath . '/' . $strFile) as $k=>$v)
			{
				$arrFiles[$k] = $v;
			}
		}

		return $arrFiles;
	}

	/**
	 * Check if a path is protected (see #287)
	 *
	 * @param string $path
	 *
	 * @return boolean
	 */
	protected function isProtectedPath($path)
	{
		return !(new Folder($path))->isUnprotected();
	}

	protected function getFormFieldSuffix()
	{
		return md5($this->initialId ?: $this->intId);
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

		$this->blnFiles = isset($attributes['files']) && $attributes['files'];
		$this->blnFilesOnly = isset($attributes['filesOnly']) && $attributes['filesOnly'];

		if (isset($attributes['path']))
		{
			$strPath = (string) $attributes['path'];

			if (Validator::isInsecurePath($strPath) || !is_dir($this->strRootDir . '/' . $strPath))
			{
				throw new \RuntimeException('Invalid path ' . $strPath);
			}

			$strNode = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend')->get('tl_files_node');

			// If the files node is not within the current path, remove it (see #856)
			if ($strNode && ($i = array_search($strNode, $this->arrFilemounts)) !== false && strncmp($strNode . '/', $strPath . '/', \strlen($strPath) + 1) !== 0)
			{
				unset($this->arrFilemounts[$i], $GLOBALS['TL_DCA']['tl_files']['list']['sorting']['breadcrumb']);
				$this->updateFilemounts($this->arrFilemounts);
			}

			// Allow only those roots that are allowed in root nodes
			if (!empty($this->arrFilemounts) || \is_array($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['root'] ?? null) || ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['root'] ?? null) === false)
			{
				$blnValid = false;

				foreach ($this->arrFilemounts as $strFolder)
				{
					if (0 === strpos($strPath, $strFolder))
					{
						$blnValid = true;
						break;
					}
				}

				if (!$blnValid)
				{
					$strPath = '';
				}
			}

			$this->updateFilemounts(array($strPath));
		}

		if (isset($attributes['extensions']))
		{
			$this->arrValidFileTypes = StringUtil::trimsplit(',', strtolower($attributes['extensions']));
		}

		return $attributes;
	}

	protected function updateFilemounts(array $filemounts)
	{
		$this->arrFilemounts = $this->eliminateNestedPaths(array_unique($filemounts));

		// If "showRootTrails" is not enabled, fall back to using the file mounts
		$visibleRootTrails = $this->arrFilemounts;

		// Fetch visible root trails if enabled
		if ($GLOBALS['TL_DCA'][$this->strTable]['list']['sorting']['showRootTrails'] ?? false)
		{
			foreach ($this->arrFilemounts as $filemount)
			{
				$visibleRootTrails = array(...$visibleRootTrails, ...$this->getParentFilemounts($filemount));
			}
		}

		$visibleRootTrails = array_unique($visibleRootTrails);
		sort($visibleRootTrails);

		$this->visibleRootTrails = $visibleRootTrails;
	}

	private function getParentFilemounts(string $filemount): array
	{
		$parents = array();
		$uploadPath = Path::canonicalize($this->strUploadPath);

		while (($filemount = Path::getDirectory($filemount)) !== $uploadPath)
		{
			$parents[] = $filemount;
		}

		return $parents;
	}

	/**
	 * Generate a preview image with a 1x, 2x source set for retina displays
	 *
	 * @param string  $relpath
	 * @param boolean $isImportantPath
	 *
	 * @return string
	 */
	private function getPreviewImage($relpath, $isImportantPath=false)
	{
		$container = System::getContainer();
		$projectDir = $container->getParameter('kernel.project_dir');

		$resizeConfig = (new ResizeConfiguration())
			->setWidth($isImportantPath ? 80 : 100)
			->setHeight($isImportantPath ? 60 : 75)
			->setMode(ResizeConfiguration::MODE_BOX)
			->setZoomLevel($isImportantPath ? 100 : 0);

		$pictureConfig = (new PictureConfiguration())
			->setSize(
				(new PictureConfigurationItem())
					->setResizeConfig($resizeConfig)
					->setDensities('1x, 2x')
			);

		$picture = $container
			->get('contao.image.preview_factory')
			->createPreviewPicture($projectDir . '/' . $relpath, $pictureConfig);

		$img = $picture->getImg($projectDir);

		return sprintf('<img src="%s"%s width="%s" height="%s" alt class="%s" loading="lazy">', $img['src'], $img['srcset'] != $img['src'] ? ' srcset="' . $img['srcset'] . '"' : '', $img['width'], $img['height'], $isImportantPath ? 'preview-important' : 'preview-image');
	}
}
