<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\Image\ResizeConfiguration;
use Doctrine\DBAL\Exception\DriverException;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

trigger_deprecation('contao/core-bundle', '4.13', 'Using the "Contao\FileSelector" class has been deprecated and will no longer work in Contao 5.0. Use the picker instead.');

/**
 * Provide methods to handle input field "file tree".
 *
 * @property string  $path
 * @property string  $fieldType
 * @property string  $sort
 * @property boolean $files
 * @property boolean $filesOnly
 * @property string  $extensions
 *
 * @deprecated Deprecated since Contao 4.13, to be removed in Contao 5.0.
 *             Use the picker instead.
 */
class FileSelector extends Widget
{
	/**
	 * Submit user input
	 * @var boolean
	 */
	protected $blnSubmitInput = true;

	/**
	 * Path nodes
	 * @var array
	 */
	protected $arrNodes = array();

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'be_widget';

	/**
	 * Valid file types
	 * @var array
	 */
	protected $arrValidFileTypes = array();

	/**
	 * Load the database object
	 *
	 * @param array $arrAttributes
	 */
	public function __construct($arrAttributes=null)
	{
		$this->import(Database::class, 'Database');
		parent::__construct($arrAttributes);
	}

	public function __set($strKey, $varValue)
	{
		if ($strKey === 'extensions' && \is_array($varValue))
		{
			$varValue = implode(',', $varValue);
		}

		parent::__set($strKey, $varValue);
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		$this->import(BackendUser::class, 'User');
		$this->convertValuesToPaths();

		if ($this->extensions)
		{
			$this->arrValidFileTypes = StringUtil::trimsplit(',', strtolower($this->extensions));
		}

		/** @var AttributeBagInterface $objSessionBag */
		$objSessionBag = System::getContainer()->get('session')->getBag('contao_backend');

		// Store the keyword
		if (Input::post('FORM_SUBMIT') == 'item_selector')
		{
			$strKeyword = ltrim(Input::postRaw('keyword'), '*');

			$objSessionBag->set('file_selector_search', $strKeyword);
			$this->reload();
		}

		$tree = '';
		$for = $objSessionBag->get('file_selector_search');
		$arrFound = array();

		// Search for a specific file
		if ((string) $for !== '')
		{
			try
			{
				$this->Database->prepare("SELECT '' REGEXP ?")->execute($for);
			}
			catch (DriverException $exception)
			{
				// Quote search string if it is not a valid regular expression
				$for = preg_quote($for, null);
			}

			$strPattern = "CAST(name AS CHAR) REGEXP ?";

			if (substr(Config::get('dbCollation'), -3) == '_ci')
			{
				$strPattern = "LOWER(CAST(name AS CHAR)) REGEXP LOWER(?)";
			}

			$strType = '';

			if (strpos($for, 'type:file') !== false)
			{
				$strType = " AND type='file'";
				$for = trim(str_replace('type:file', '', $for));
			}

			if (strpos($for, 'type:folder') !== false)
			{
				$strType = " AND type='folder'";
				$for = trim(str_replace('type:folder', '', $for));
			}

			$objRoot = $this->Database->prepare("SELECT path, type, extension FROM tl_files WHERE $strPattern $strType")
									  ->execute($for);

			if ($objRoot->numRows < 1)
			{
				$GLOBALS['TL_DCA']['tl_files']['list']['sorting']['root'] = array('');
			}
			else
			{
				$arrPaths = array();

				// Respect existing limitations
				if ($this->path)
				{
					while ($objRoot->next())
					{
						if (strncmp($this->path . '/', $objRoot->path . '/', \strlen($this->path) + 1) === 0)
						{
							if ($objRoot->type == 'folder' || empty($this->arrValidFileTypes) || \in_array($objRoot->extension, $this->arrValidFileTypes))
							{
								$arrFound[] = $objRoot->path;
							}

							$arrPaths[] = ($objRoot->type == 'folder') ? $objRoot->path : \dirname($objRoot->path);
						}
					}
				}
				elseif ($this->User->isAdmin)
				{
					// Show all files to admins
					while ($objRoot->next())
					{
						if ($objRoot->type == 'folder' || empty($this->arrValidFileTypes) || \in_array($objRoot->extension, $this->arrValidFileTypes))
						{
							$arrFound[] = $objRoot->path;
						}

						$arrPaths[] = ($objRoot->type == 'folder') ? $objRoot->path : \dirname($objRoot->path);
					}
				}
				elseif (\is_array($this->User->filemounts))
				{
					while ($objRoot->next())
					{
						// Show only mounted folders to regular users
						foreach ($this->User->filemounts as $path)
						{
							if (strncmp($path . '/', $objRoot->path . '/', \strlen($path) + 1) === 0)
							{
								if ($objRoot->type == 'folder' || empty($this->arrValidFileTypes) || \in_array($objRoot->extension, $this->arrValidFileTypes))
								{
									$arrFound[] = $objRoot->path;
								}

								$arrPaths[] = ($objRoot->type == 'folder') ? $objRoot->path : \dirname($objRoot->path);
							}
						}
					}
				}

				$GLOBALS['TL_DCA']['tl_files']['list']['sorting']['root'] = array_unique($arrPaths);
			}
		}

		$strNode = $objSessionBag->get('tl_files_picker');

		// Unset the node if it is not within the path (see #5899)
		if ($strNode && $this->path && strncmp($strNode . '/', $this->path . '/', \strlen($this->path) + 1) !== 0)
		{
			$objSessionBag->remove('tl_files_picker');
		}

		// Add the breadcrumb menu
		if (Input::get('do') != 'files')
		{
			Backend::addFilesBreadcrumb('tl_files_picker');
		}

		$projectDir = System::getContainer()->getParameter('kernel.project_dir');

		// Root nodes (breadcrumb menu)
		if (!empty($GLOBALS['TL_DCA']['tl_files']['list']['sorting']['root']))
		{
			$root = $GLOBALS['TL_DCA']['tl_files']['list']['sorting']['root'];

			// Allow only those roots that are within the custom path
			if ($this->path)
			{
				$root = array_intersect(preg_grep('/^' . preg_quote($this->path, '/') . '(?:$|\/)/', $root), $root);

				if (empty($root))
				{
					// Set all folders inside the custom path as root nodes
					$root = array_map(function ($el) { return $this->path . '/' . $el; }, Folder::scan($projectDir . '/' . $this->path));

					// Hide the breadcrumb
					$GLOBALS['TL_DCA']['tl_file']['list']['sorting']['breadcrumb'] = '';
				}
			}

			$nodes = $this->eliminateNestedPaths($root);

			foreach ($nodes as $node)
			{
				$tree .= $this->renderFiletree($projectDir . '/' . $node, 0, true, true, $arrFound);
			}
		}

		// Show a custom path (see #4926)
		elseif ($this->path)
		{
			$tree .= $this->renderFiletree($projectDir . '/' . $this->path, 0, false, $this->isProtectedPath($this->path), $arrFound);
		}

		// Start from root
		elseif ($this->User->isAdmin)
		{
			$tree .= $this->renderFiletree($projectDir . '/' . System::getContainer()->getParameter('contao.upload_path'), 0, false, true, $arrFound);
		}

		// Show mounted files to regular users
		else
		{
			$nodes = $this->eliminateNestedPaths($this->User->filemounts);

			foreach ($nodes as $node)
			{
				$tree .= $this->renderFiletree($projectDir . '/' . $node, 0, true, true, $arrFound);
			}
		}

		// Select all checkboxes
		if ($this->fieldType == 'checkbox')
		{
			$strReset = "\n" . '    <li class="tl_folder"><div class="tl_left">&nbsp;</div> <div class="tl_right"><label for="check_all_' . $this->strId . '" class="tl_change_selected">' . $GLOBALS['TL_LANG']['MSC']['selectAll'] . '</label> <input type="checkbox" id="check_all_' . $this->strId . '" class="tl_tree_checkbox" value="" onclick="Backend.toggleCheckboxGroup(this,\'' . $this->strName . '\')"></div><div style="clear:both"></div></li>';
		}
		// Reset radio button selection
		else
		{
			$strReset = "\n" . '    <li class="tl_folder"><div class="tl_left">&nbsp;</div> <div class="tl_right"><label for="reset_' . $this->strId . '" class="tl_change_selected">' . $GLOBALS['TL_LANG']['MSC']['resetSelected'] . '</label> <input type="radio" name="' . $this->strName . '" id="reset_' . $this->strName . '" class="tl_tree_radio" value="" onfocus="Backend.getScrollOffset()"></div><div style="clear:both"></div></li>';
		}

		// Return the tree
		return '<ul class="tl_listing tree_view picker_selector' . ($this->strClass ? ' ' . $this->strClass : '') . '" id="' . $this->strId . '" data-callback="reloadFiletree">
    <li class="tl_folder_top"><div class="tl_left">' . Image::getHtml($GLOBALS['TL_DCA']['tl_files']['list']['sorting']['icon'] ?: 'filemounts.svg') . ' ' . $GLOBALS['TL_LANG']['MOD']['files'][0] . '</div> <div class="tl_right">&nbsp;</div><div style="clear:both"></div></li><li class="parent" id="' . $this->strId . '_parent"><ul>' . $tree . $strReset . '
  </ul></li></ul>';
	}

	/**
	 * Generate a particular subpart of the file tree and return it as HTML string
	 *
	 * @param integer $strFolder
	 * @param string  $strField
	 * @param integer $level
	 * @param boolean $mount
	 *
	 * @return string
	 */
	public function generateAjax($strFolder, $strField, $level, $mount=false)
	{
		if (!Environment::get('isAjaxRequest'))
		{
			return '';
		}

		$this->strField = $strField;
		$this->loadDataContainer($this->strTable);

		// Load the current values
		switch (true)
		{
			case is_a($GLOBALS['TL_DCA'][$this->strTable]['config']['dataContainer'] ?? null, DC_File::class, true):
				if (Config::get($this->strField))
				{
					$this->varValue = Config::get($this->strField);
				}
				break;

			case is_a($GLOBALS['TL_DCA'][$this->strTable]['config']['dataContainer'] ?? null, DC_Table::class, true):
				$this->import(Database::class, 'Database');

				if (!$this->Database->fieldExists($this->strField, $this->strTable))
				{
					break;
				}

				$objField = $this->Database->prepare("SELECT " . Database::quoteIdentifier($this->strField) . " FROM " . $this->strTable . " WHERE id=?")
										   ->limit(1)
										   ->execute($this->strId);

				if ($objField->numRows)
				{
					$this->varValue = StringUtil::deserialize($objField->{$this->strField});
				}
				break;
		}

		$this->convertValuesToPaths();

		if ($this->extensions)
		{
			$this->arrValidFileTypes = StringUtil::trimsplit(',', $this->extensions);
		}

		return $this->renderFiletree(System::getContainer()->getParameter('kernel.project_dir') . '/' . $strFolder, ($level * 20), $mount, $this->isProtectedPath($strFolder));
	}

	/**
	 * Recursively render the filetree
	 *
	 * @param string  $path
	 * @param integer $intMargin
	 * @param boolean $mount
	 * @param boolean $blnProtected
	 * @param array   $arrFound
	 *
	 * @return string
	 */
	protected function renderFiletree($path, $intMargin, $mount=false, $blnProtected=true, $arrFound=array())
	{
		// Invalid path
		if (!is_dir($path))
		{
			return '';
		}

		// Make sure that $this->varValue is an array (see #3369)
		if (!\is_array($this->varValue))
		{
			$this->varValue = array($this->varValue);
		}

		/** @var AttributeBagInterface $objSessionBag */
		$objSessionBag = System::getContainer()->get('session')->getBag('contao_backend');
		$session = $objSessionBag->all();

		$flag = substr($this->strField, 0, 2);
		$node = 'tree_' . $this->strTable . '_' . $this->strField;
		$xtnode = 'tree_' . $this->strTable . '_' . $this->strName;

		// Get session data and toggle nodes
		if (Input::get($flag . 'tg'))
		{
			$session[$node][Input::get($flag . 'tg')] = (isset($session[$node][Input::get($flag . 'tg')]) && $session[$node][Input::get($flag . 'tg')] == 1) ? 0 : 1;
			$objSessionBag->replace($session);
			$this->redirect(preg_replace('/(&(amp;)?|\?)' . $flag . 'tg=[^& ]*/i', '', Environment::get('request')));
		}

		$return = '';
		$intSpacing = 20;
		$files = array();
		$folders = array();
		$level = ($intMargin / $intSpacing + 1);

		// Mount folder
		if ($mount)
		{
			$folders = array($path);
		}

		// Scan directory and sort the result
		else
		{
			foreach (Folder::scan($path) as $v)
			{
				if (strncmp($v, '.', 1) === 0)
				{
					continue;
				}

				if (is_dir($path . '/' . $v))
				{
					$folders[] = $path . '/' . $v;
				}
				else
				{
					$files[] = $path . '/' . $v;
				}
			}
		}

		natcasesort($folders);
		$folders = array_values($folders);

		natcasesort($files);
		$files = array_values($files);

		// Sort descending (see #4072)
		if ($this->sort == 'desc')
		{
			$folders = array_reverse($folders);
			$files = array_reverse($files);
		}

		$folderClass = ($this->files || $this->filesOnly) ? 'tl_folder' : 'tl_file';

		// Process folders
		for ($f=0, $c=\count($folders); $f<$c; $f++)
		{
			$content = Folder::scan($folders[$f]);
			$currentFolder = StringUtil::stripRootDir($folders[$f]);
			$countFiles = \count($content);

			// Check whether there are subfolders or files
			foreach ($content as $file)
			{
				if (strncmp($file, '.', 1) === 0)
				{
					--$countFiles;
				}
				elseif (!$this->files && !$this->filesOnly && is_file($folders[$f] . '/' . $file))
				{
					--$countFiles;
				}
				elseif (!empty($arrFound) && !\in_array($currentFolder . '/' . $file, $arrFound) && !preg_grep('/^' . preg_quote($currentFolder . '/' . $file, '/') . '\//', $arrFound))
				{
					--$countFiles;
				}
			}

			if (!empty($arrFound) && $countFiles < 1 && !\in_array($currentFolder, $arrFound))
			{
				continue;
			}

			$tid = md5($folders[$f]);
			$folderAttribute = 'style="margin-left:20px"';
			$session[$node][$tid] = is_numeric($session[$node][$tid]) ? $session[$node][$tid] : 0;
			$currentFolder = StringUtil::stripRootDir($folders[$f]);
			$blnIsOpen = (!empty($arrFound) || $session[$node][$tid] == 1 || \count(preg_grep('/^' . preg_quote($currentFolder, '/') . '\//', $this->varValue)) > 0);
			$return .= "\n    " . '<li class="' . $folderClass . ' toggle_select hover-div"><div class="tl_left" style="padding-left:' . $intMargin . 'px">';

			// Add a toggle button if there are children
			if ($countFiles > 0)
			{
				$folderAttribute = '';
				$img = $blnIsOpen ? 'folMinus.svg' : 'folPlus.svg';
				$alt = $blnIsOpen ? $GLOBALS['TL_LANG']['MSC']['collapseNode'] : $GLOBALS['TL_LANG']['MSC']['expandNode'];
				$return .= '<a href="' . Backend::addToUrl($flag . 'tg=' . $tid) . '" title="' . StringUtil::specialchars($alt) . '" onclick="return AjaxRequest.toggleFiletree(this,\'' . $xtnode . '_' . $tid . '\',\'' . $currentFolder . '\',\'' . $this->strField . '\',\'' . $this->strName . '\',' . $level . ')">' . Image::getHtml($img, '', 'style="margin-right:2px"') . '</a>';
			}

			$protected = $blnProtected;

			// Check whether the folder is public
			if ($protected === true && \in_array('.public', $content) && !is_dir(Path::join($folders[$f], '.public')))
			{
				$protected = false;
			}

			$folderImg = $protected ? 'folderCP.svg' : 'folderC.svg';
			$folderLabel = ($this->files || $this->filesOnly) ? '<strong>' . StringUtil::specialchars(basename($currentFolder)) . '</strong>' : StringUtil::specialchars(basename($currentFolder));

			// Add the current folder
			$return .= Image::getHtml($folderImg, '', $folderAttribute) . ' <a href="' . Backend::addToUrl('fn=' . $this->urlEncode($currentFolder)) . '" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['selectNode']) . '">' . $folderLabel . '</a></div> <div class="tl_right">';

			// Add a checkbox or radio button
			if (!$this->filesOnly)
			{
				switch ($this->fieldType)
				{
					case 'checkbox':
						$return .= '<input type="checkbox" name="' . $this->strName . '[]" id="' . $this->strName . '_' . md5($currentFolder) . '" class="tl_tree_checkbox" value="' . StringUtil::specialchars($currentFolder) . '" onfocus="Backend.getScrollOffset()"' . $this->optionChecked($currentFolder, $this->varValue) . '>';
						break;

					default:
					case 'radio':
						$return .= '<input type="radio" name="' . $this->strName . '" id="' . $this->strName . '_' . md5($currentFolder) . '" class="tl_tree_radio" value="' . StringUtil::specialchars($currentFolder) . '" onfocus="Backend.getScrollOffset()"' . $this->optionChecked($currentFolder, $this->varValue) . '>';
						break;
				}
			}

			$return .= '</div><div style="clear:both"></div></li>';

			// Call the next node
			if ($blnIsOpen)
			{
				$return .= '<li class="parent" id="' . $xtnode . '_' . $tid . '"><ul class="level_' . $level . '">';
				$return .= $this->renderFiletree($folders[$f], ($intMargin + $intSpacing), false, $protected, $arrFound);
				$return .= '</ul></li>';
			}
		}

		// Process files
		if ($this->files || $this->filesOnly)
		{
			for ($h=0, $c=\count($files); $h<$c; $h++)
			{
				$thumbnail = '';
				$currentFile = StringUtil::stripRootDir($files[$h]);
				$currentEncoded = $this->urlEncode($currentFile);

				$objFile = new File($currentFile);

				if (!empty($this->arrValidFileTypes) && !\in_array($objFile->extension, $this->arrValidFileTypes))
				{
					continue;
				}

				// Ignore files not matching the search criteria
				if (!empty($arrFound) && !\in_array($currentFile, $arrFound))
				{
					continue;
				}

				$return .= "\n    " . '<li class="tl_file toggle_select hover-div"><div class="tl_left" style="padding-left:' . ($intMargin + $intSpacing) . 'px">';
				$thumbnail .= ' <span class="tl_gray">(' . $this->getReadableSize($objFile->filesize);

				if ($objFile->width && $objFile->height)
				{
					$thumbnail .= ', ' . $objFile->width . 'x' . $objFile->height . ' px';
				}

				$thumbnail .= ')</span>';

				// Generate thumbnail
				if ($objFile->isImage && $objFile->viewHeight > 0 && Config::get('thumbnails') && ($objFile->isSvgImage || ($objFile->height <= Config::get('gdMaxImgHeight') && $objFile->width <= Config::get('gdMaxImgWidth'))))
				{
					$projectDir = System::getContainer()->getParameter('kernel.project_dir');
					$thumbnail .= '<br>' . Image::getHtml(System::getContainer()->get('contao.image.factory')->create($projectDir . '/' . rawurldecode($currentEncoded), array(100, 75, ResizeConfiguration::MODE_BOX))->getUrl($projectDir), '', 'style="margin:0 0 2px -18px"');
					$importantPart = System::getContainer()->get('contao.image.factory')->create($projectDir . '/' . rawurldecode($currentEncoded))->getImportantPart();

					if ($importantPart->getX() > 0 || $importantPart->getY() > 0 || $importantPart->getWidth() < 1 || $importantPart->getHeight() < 1)
					{
						$thumbnail .= ' ' . Image::getHtml(System::getContainer()->get('contao.image.factory')->create($projectDir . '/' . rawurldecode($currentEncoded), (new ResizeConfiguration())->setWidth(80)->setHeight(60)->setMode(ResizeConfiguration::MODE_BOX)->setZoomLevel(100))->getUrl($projectDir), '', 'style="margin:0 0 2px 0;vertical-align:bottom"');
					}
				}

				$return .= Image::getHtml($objFile->icon, $objFile->mime) . ' ' . StringUtil::convertEncoding(StringUtil::specialchars(basename($currentFile)), System::getContainer()->getParameter('kernel.charset')) . $thumbnail . '</div> <div class="tl_right">';

				// Add checkbox or radio button
				switch ($this->fieldType)
				{
					case 'checkbox':
						$return .= '<input type="checkbox" name="' . $this->strName . '[]" id="' . $this->strName . '_' . md5($currentFile) . '" class="tl_tree_checkbox" value="' . StringUtil::specialchars($currentFile) . '" onfocus="Backend.getScrollOffset()"' . $this->optionChecked($currentFile, $this->varValue) . '>';
						break;

					default:
					case 'radio':
						$return .= '<input type="radio" name="' . $this->strName . '" id="' . $this->strName . '_' . md5($currentFile) . '" class="tl_tree_radio" value="' . StringUtil::specialchars($currentFile) . '" onfocus="Backend.getScrollOffset()"' . $this->optionChecked($currentFile, $this->varValue) . '>';
						break;
				}

				$return .= '</div><div style="clear:both"></div></li>';
			}
		}

		return $return;
	}

	/**
	 * Translate the file IDs to file paths
	 */
	protected function convertValuesToPaths()
	{
		if (empty($this->varValue))
		{
			return;
		}

		if (!\is_array($this->varValue))
		{
			$this->varValue = array($this->varValue);
		}
		elseif (empty($this->varValue[0]))
		{
			$this->varValue = array();
		}

		if (empty($this->varValue))
		{
			return;
		}

		// TinyMCE will pass the path instead of the ID
		if (strpos($this->varValue[0], System::getContainer()->getParameter('contao.upload_path') . '/') === 0)
		{
			return;
		}

		// Ignore the numeric IDs when in switch mode (TinyMCE)
		if (Input::get('switch'))
		{
			return;
		}

		// Return if the custom path is not within the upload path (see #8562)
		if ($this->path && strpos($this->path, System::getContainer()->getParameter('contao.upload_path') . '/') !== 0)
		{
			return;
		}

		$objFiles = FilesModel::findMultipleByIds($this->varValue);

		if ($objFiles !== null)
		{
			$this->varValue = array_values($objFiles->fetchEach('path'));
		}
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
		$projectDir = System::getContainer()->getParameter('kernel.project_dir');

		do
		{
			if (is_file($projectDir . '/' . $path . '/.public'))
			{
				return false;
			}

			$path = \dirname($path);
		}
		while ($path != '.');

		return true;
	}
}

class_alias(FileSelector::class, 'FileSelector');
