<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\Database\Result;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\String\UnicodeString;

/**
 * Provide methods to handle themes.
 */
class Theme extends Backend
{
	/**
	 * @var string
	 */
	protected $strRootDir;

	/**
	 * Set the root directory
	 */
	public function __construct()
	{
		parent::__construct();
		$this->strRootDir = System::getContainer()->getParameter('kernel.project_dir');
	}

	/**
	 * Import a theme
	 *
	 * @return string
	 */
	public function importTheme()
	{
		Config::set('uploadTypes', Config::get('uploadTypes') . ',cto,sql');

		if (!System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_IMPORT_THEMES))
		{
			throw new AccessDeniedException('Not enough permissions to import themes.');
		}

		$objUploader = new FileUpload();

		if (Input::post('FORM_SUBMIT') == 'tl_theme_import')
		{
			$objSession = System::getContainer()->get('request_stack')->getSession();

			if (!Input::post('confirm'))
			{
				$arrUploaded = $objUploader->uploadTo('system/tmp');

				if (empty($arrUploaded))
				{
					Message::addError($GLOBALS['TL_LANG']['ERR']['all_fields']);
					$this->reload();
				}

				$arrFiles = array();

				foreach ($arrUploaded as $strFile)
				{
					// Skip folders
					if (is_dir($this->strRootDir . '/' . $strFile))
					{
						Message::addError(sprintf($GLOBALS['TL_LANG']['ERR']['importFolder'], basename($strFile)));
						continue;
					}

					$objFile = new File($strFile);

					// Skip anything but .cto, .sql and .zip files
					if ($objFile->extension != 'cto' && $objFile->extension != 'sql' && $objFile->extension != 'zip')
					{
						Message::addError(sprintf($GLOBALS['TL_LANG']['ERR']['filetype'], $objFile->extension));
						continue;
					}

					$arrFiles[] = $strFile;
				}
			}
			else
			{
				$arrFiles = explode(',', $objSession->get('uploaded_themes'));
			}

			// Check whether there are any files
			if (empty($arrFiles))
			{
				Message::addError($GLOBALS['TL_LANG']['ERR']['all_fields']);
				$this->reload();
			}

			$db = Database::getInstance();

			// Store the field names of the theme tables
			$arrDbFields = array
			(
				'tl_files'           => $db->getFieldNames('tl_files'),
				'tl_theme'           => $db->getFieldNames('tl_theme'),
				'tl_module'          => $db->getFieldNames('tl_module'),
				'tl_layout'          => $db->getFieldNames('tl_layout'),
				'tl_image_size'      => $db->getFieldNames('tl_image_size'),
				'tl_image_size_item' => $db->getFieldNames('tl_image_size_item')
			);

			// Proceed
			if (Input::post('confirm') == 1)
			{
				$this->extractThemeFiles($arrFiles, $arrDbFields);
			}
			else
			{
				$objSession->set('uploaded_themes', implode(',', $arrFiles));

				return $this->compareThemeFiles($arrFiles, $arrDbFields);
			}
		}

		// Return the form
		return Message::generate() . '
<div id="tl_buttons">
<a href="' . StringUtil::ampersand(str_replace('&key=importTheme', '', Environment::get('requestUri'))) . '" class="header_back" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']) . '" accesskey="b">' . $GLOBALS['TL_LANG']['MSC']['backBT'] . '</a>
</div>
<form id="tl_theme_import" class="tl_form tl_edit_form" method="post" enctype="multipart/form-data">
<div class="tl_formbody_edit">
<input type="hidden" name="FORM_SUBMIT" value="tl_theme_import">
<input type="hidden" name="REQUEST_TOKEN" value="' . htmlspecialchars(System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue()) . '">
<input type="hidden" name="MAX_FILE_SIZE" value="' . Config::get('maxFileSize') . '">

<div class="tl_tbox">
  <div class="widget">
    <h3>' . $GLOBALS['TL_LANG']['tl_theme']['source'][0] . '</h3>' . $objUploader->generateMarkup() . (isset($GLOBALS['TL_LANG']['tl_theme']['source'][1]) ? '
    <p class="tl_help tl_tip">' . $GLOBALS['TL_LANG']['tl_theme']['source'][1] . '</p>' : '') . '
  </div>
</div>

</div>

<div class="tl_formbody_submit">

<div class="tl_submit_container">
  <button type="submit" name="save" id="save" class="tl_submit" accesskey="s">' . $GLOBALS['TL_LANG']['tl_theme']['importTheme'][0] . '</button>
</div>

</div>
</form>';
	}

	/**
	 * Compare the theme tables with the local database and check whether there are custom layout sections
	 *
	 * @param array $arrFiles
	 * @param array $arrDbFields
	 *
	 * @return string
	 */
	protected function compareThemeFiles($arrFiles, $arrDbFields)
	{
		$return = Message::generate() . '
<div id="tl_buttons">
<a href="' . StringUtil::ampersand(str_replace('&key=importTheme', '', Environment::get('requestUri'))) . '" class="header_back" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']) . '" accesskey="b">' . $GLOBALS['TL_LANG']['MSC']['backBT'] . '</a>
</div>
<form id="tl_theme_import" class="tl_form tl_edit_form" method="post">
<div class="tl_formbody_edit">
<input type="hidden" name="FORM_SUBMIT" value="tl_theme_import">
<input type="hidden" name="REQUEST_TOKEN" value="' . htmlspecialchars(System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue()) . '">
<input type="hidden" name="confirm" value="1">';

		$count = 0;
		$exampleWebsites = array();

		// Check the theme data
		foreach ($arrFiles as $strFile)
		{
			if ((new File($strFile))->extension === 'sql')
			{
				$exampleWebsites[] = basename($strFile);

				continue;
			}

			++$count;

			$return .= '

<div class="tl_tbox theme_import">
  <h3>' . basename($strFile) . '</h3>
  <h4>' . $GLOBALS['TL_LANG']['tl_theme']['tables_fields'] . '</h4>';

			// Find the XML file
			$objArchive = new ZipReader($strFile, true);

			// Continue if there is no XML file
			if ($objArchive->getFile('theme.xml') === false)
			{
				$return .= "\n  " . '<p class="tl_red" style="margin:0">' . sprintf($GLOBALS['TL_LANG']['tl_theme']['missing_xml'], basename($strFile)) . "</p>\n</div>";
				continue;
			}

			// Open the XML file
			$xml = new \DOMDocument();
			$xml->preserveWhiteSpace = false;
			$xml->loadXML($objArchive->unzip());
			$tables = $xml->getElementsByTagName('table');

			$blnHasError = false;

			// Loop through the tables
			for ($i=0; $i<$tables->length; $i++)
			{
				$rows = $tables->item($i)->childNodes;
				$table = $tables->item($i)->getAttribute('name');

				// Skip invalid tables
				if ($table != 'tl_theme' && $table != 'tl_module' && $table != 'tl_layout' && $table != 'tl_image_size' && $table != 'tl_image_size_item')
				{
					continue;
				}

				$arrFieldNames = array();

				// Loop through the rows
				for ($j=0; $j<$rows->length; $j++)
				{
					$fields = $rows->item($j)->childNodes;

					// Loop through the fields
					for ($k=0; $k<$fields->length; $k++)
					{
						$arrFieldNames[$fields->item($k)->getAttribute('name')] = true;
					}
				}

				$arrFieldNames = array_keys($arrFieldNames);

				// Loop through the fields
				foreach ($arrFieldNames as $name)
				{
					// Print a warning if a field is missing
					if (!\in_array($name, $arrDbFields[$table]))
					{
						$blnHasError = true;
						$return .= "\n  " . '<p class="tl_red" style="margin:0">' . sprintf($GLOBALS['TL_LANG']['tl_theme']['missing_field'], $table . '.' . $name) . '</p>';
					}
				}
			}

			// Confirmation
			if (!$blnHasError)
			{
				$return .= "\n  " . '<p class="tl_green" style="margin:0">' . $GLOBALS['TL_LANG']['tl_theme']['tables_ok'] . '</p>';
			}

			// Check the custom templates
			$return .= '
  <h4>' . $GLOBALS['TL_LANG']['tl_theme']['custom_templates'] . '</h4>';

			$objArchive->reset();
			$blnTplExists = false;

			// Loop through the archive
			while ($objArchive->next())
			{
				if (strncmp($objArchive->file_name, 'templates/', 10) !== 0)
				{
					continue;
				}

				if (strtolower(pathinfo($objArchive->file_name, PATHINFO_EXTENSION)) === 'sql')
				{
					$exampleWebsites[] = substr($objArchive->file_name, 10);
				}

				if (file_exists($this->strRootDir . '/' . $objArchive->file_name))
				{
					$blnTplExists = true;
					$return .= "\n  " . '<p class="tl_red" style="margin:0">' . sprintf($GLOBALS['TL_LANG']['tl_theme']['template_exists'], $objArchive->file_name) . '</p>';
				}
			}

			// Confirmation
			if (!$blnTplExists)
			{
				$return .= "\n  " . '<p class="tl_green" style="margin:0">' . $GLOBALS['TL_LANG']['tl_theme']['templates_ok'] . '</p>';
			}

			// HOOK: add custom logic
			if (isset($GLOBALS['TL_HOOKS']['compareThemeFiles']) && \is_array($GLOBALS['TL_HOOKS']['compareThemeFiles']))
			{
				foreach ($GLOBALS['TL_HOOKS']['compareThemeFiles'] as $callback)
				{
					$return .= System::importStatic($callback[0])->{$callback[1]}($xml, $objArchive);
				}
			}

			$return .= '
</div>';
		}

		if ($exampleWebsites)
		{
			$return .= '<br class="clr"><div class="w50 clr widget">
  <h3><label>' . ($GLOBALS['TL_LANG']['tl_theme']['selectExampleWebsite'][0] ?? '') . '</label></h3>
  <select name="example_website" class="tl_select" onchange="document.querySelector(\'#ctrl_example_website_import\').style.display = this.value ? \'\' : \'none\'">';

			if ($count)
			{
				$return .= '<option value="">-</option>';
			}

			foreach ($exampleWebsites as $exampleWebsite)
			{
				$return .= '<option value="' . htmlspecialchars($exampleWebsite) . '">' . htmlspecialchars($exampleWebsite) . '</option>';
			}

			$return .= '</select>
  <p class="tl_help tl_tip" title="">' . ($GLOBALS['TL_LANG']['tl_theme']['selectExampleWebsite'][1] ?? '') . '</p>
</div><div class="w50 widget"' . ($count ? ' style="display: none"' : '') . ' id="ctrl_example_website_import">
  <h3><label>' . ($GLOBALS['TL_LANG']['tl_theme']['exampleWebsiteImportType'][0] ?? '') . '</label></h3>
  <select name="example_website_import" class="tl_select">
    <option value="full">' . ($GLOBALS['TL_LANG']['tl_theme']['exampleWebsiteImport']['full'] ?? '') . '</option>
    <option value="data">' . ($GLOBALS['TL_LANG']['tl_theme']['exampleWebsiteImport']['data'] ?? '') . '</option>
    <option value="data_no_truncate">' . ($GLOBALS['TL_LANG']['tl_theme']['exampleWebsiteImport']['data_no_truncate'] ?? '') . '</option>
  </select>
  <p class="tl_help tl_tip" title="">' . ($GLOBALS['TL_LANG']['tl_theme']['exampleWebsiteImportType'][1] ?? '') . '</p>
</div><br class="clr">';
		}

		// Return the form
		return $return . '

</div>

<div class="tl_formbody_submit">

<div class="tl_submit_container">
  <button type="submit" name="save" id="save" class="tl_submit" accesskey="s">' . $GLOBALS['TL_LANG']['MSC']['continue'] . '</button>
</div>

</div>
</form>';
	}

	/**
	 * Extract the theme files and write the data to the database
	 *
	 * @param array $arrFiles
	 * @param array $arrDbFields
	 */
	protected function extractThemeFiles($arrFiles, $arrDbFields)
	{
		$db = Database::getInstance();
		$exampleWebsites = array();

		foreach ($arrFiles as $strZipFile)
		{
			if ((new File($strZipFile))->extension === 'sql')
			{
				$exampleWebsites[basename($strZipFile)] = $strZipFile;

				continue;
			}

			$xml = null;

			// Open the archive
			$objArchive = new ZipReader($strZipFile, true);

			// Extract all files
			while ($objArchive->next())
			{
				// Load the XML file
				if ($objArchive->file_name == 'theme.xml')
				{
					$xml = new \DOMDocument();
					$xml->preserveWhiteSpace = false;
					$xml->loadXML($objArchive->unzip());
					continue;
				}

				// Limit file operations to files and the templates directory
				if (strncmp($objArchive->file_name, 'files/', 6) !== 0 && strncmp($objArchive->file_name, 'tl_files/', 9) !== 0 && strncmp($objArchive->file_name, 'templates/', 10) !== 0)
				{
					continue;
				}

				// Extract the files
				try
				{
					File::putContent($this->customizeUploadPath($objArchive->file_name), $objArchive->unzip());

					if (strncmp($objArchive->file_name, 'templates/', 10) === 0 && strtolower(pathinfo($objArchive->file_name, PATHINFO_EXTENSION)) === 'sql')
					{
						$exampleWebsites[substr($objArchive->file_name, 10)] = $objArchive->file_name;
					}
				}
				catch (\Exception $e)
				{
					Message::addError($e->getMessage());
				}
			}

			// Continue if there is no XML file
			if (!$xml instanceof \DOMDocument)
			{
				Message::addError(sprintf($GLOBALS['TL_LANG']['tl_theme']['missing_xml'], basename($strZipFile)));
				continue;
			}

			$arrMapper = array();
			$tables = $xml->getElementsByTagName('table');
			$arrNewFolders = array();

			// Extract the folder names from the XML file
			for ($i=0; $i<$tables->length; $i++)
			{
				if ($tables->item($i)->getAttribute('name') == 'tl_theme')
				{
					$fields = $tables->item($i)->childNodes->item(0)->childNodes;

					for ($k=0; $k<$fields->length; $k++)
					{
						if ($fields->item($k)->getAttribute('name') == 'folders')
						{
							$arrNewFolders = StringUtil::deserialize($fields->item($k)->nodeValue);
							break;
						}
					}

					break;
				}
			}

			// Sync the new folder(s)
			if (!empty($arrNewFolders) && \is_array($arrNewFolders))
			{
				foreach ($arrNewFolders as $strFolder)
				{
					$strCustomized = $this->customizeUploadPath($strFolder);

					if (Dbafs::shouldBeSynchronized($strCustomized))
					{
						Dbafs::addResource($strCustomized);
					}
				}
			}

			// Lock the tables
			$arrLocks = array
			(
				'tl_files'           => 'WRITE',
				'tl_theme'           => 'WRITE',
				'tl_module'          => 'WRITE',
				'tl_layout'          => 'WRITE',
				'tl_image_size'      => 'WRITE',
				'tl_image_size_item' => 'WRITE'
			);

			// Load the DCAs of the locked tables (see #7345)
			foreach (array_keys($arrLocks) as $table)
			{
				$this->loadDataContainer($table);
			}

			$db->lockTables($arrLocks);

			// Get the current auto_increment values
			$tl_files = $db->getNextId('tl_files');
			$tl_theme = $db->getNextId('tl_theme');
			$tl_module = $db->getNextId('tl_module');
			$tl_layout = $db->getNextId('tl_layout');
			$tl_image_size = $db->getNextId('tl_image_size');
			$tl_image_size_item = $db->getNextId('tl_image_size_item');

			// Build the mapper data (see #8326)
			for ($i=0; $i<$tables->length; $i++)
			{
				$rows = $tables->item($i)->childNodes;
				$table = $tables->item($i)->getAttribute('name');

				// Skip invalid tables
				if (!\array_key_exists($table, $arrLocks))
				{
					continue;
				}

				// Loop through the rows
				for ($j=0; $j<$rows->length; $j++)
				{
					$fields = $rows->item($j)->childNodes;

					// Loop through the fields
					for ($k=0; $k<$fields->length; $k++)
					{
						// Increment the ID
						if ($fields->item($k)->getAttribute('name') == 'id')
						{
							$arrMapper[$table][$fields->item($k)->nodeValue] = ${$table}++;
							break;
						}
					}
				}
			}

			// Loop through the tables
			for ($i=0; $i<$tables->length; $i++)
			{
				$rows = $tables->item($i)->childNodes;
				$table = $tables->item($i)->getAttribute('name');

				// Skip invalid tables
				if (!\array_key_exists($table, $arrLocks))
				{
					continue;
				}

				// Loop through the rows
				for ($j=0; $j<$rows->length; $j++)
				{
					$set = array();
					$fields = $rows->item($j)->childNodes;

					// Loop through the fields
					for ($k=0; $k<$fields->length; $k++)
					{
						$value = $fields->item($k)->nodeValue;
						$name = $fields->item($k)->getAttribute('name');

						// Skip NULL values
						if ($value == 'NULL')
						{
							continue;
						}

						// Increment the ID
						if ($name == 'id')
						{
							$value = $arrMapper[$table][$value];
						}

						// Increment the parent IDs
						elseif ($name == 'pid')
						{
							if ($table == 'tl_image_size_item')
							{
								$value = $arrMapper['tl_image_size'][$value];
							}
							else
							{
								$value = $arrMapper['tl_theme'][$value];
							}
						}

						// Handle fallback fields
						elseif ($name == 'fallback')
						{
							$value = '';
						}

						// Adjust the module IDs of the page layout
						elseif ($table == 'tl_layout' && $name == 'modules')
						{
							$modules = StringUtil::deserialize($value);

							if (\is_array($modules))
							{
								foreach ($modules as $key=>$mod)
								{
									if ($mod['mod'] > 0)
									{
										$modules[$key]['mod'] = $arrMapper['tl_module'][$mod['mod']];
									}
								}

								$value = serialize($modules);
							}
						}

						// Adjust duplicate theme names
						elseif ($table == 'tl_theme' && $name == 'name')
						{
							$objCount = $db
								->prepare("SELECT COUNT(*) AS count FROM " . $table . " WHERE name=?")
								->execute($value);

							if ($objCount->count > 0)
							{
								$value = preg_replace('/[ -][0-9]+$/', '', $value);
								$value .= ' ' . ${$table};
							}
						}

						// Adjust the file paths in tl_files
						elseif ($table == 'tl_files' && $name == 'path' && strpos($value, 'files') !== false)
						{
							$tmp = StringUtil::deserialize($value);

							if (\is_array($tmp))
							{
								foreach ($tmp as $kk=>$vv)
								{
									$tmp[$kk] = $this->customizeUploadPath($vv);
								}

								$value = serialize($tmp);
							}
							else
							{
								$value = $this->customizeUploadPath($value);
							}
						}

						// Replace the file paths in singleSRC fields with their tl_files ID
						elseif (($GLOBALS['TL_DCA'][$table]['fields'][$name]['inputType'] ?? null) == 'fileTree' && !($GLOBALS['TL_DCA'][$table]['fields'][$name]['eval']['multiple'] ?? null))
						{
							if (!$value)
							{
								$value = null; // Contao >= 3.2
							}
							else
							{
								// Do not use the FilesModel here – tables are locked!
								$objFile = $db
									->prepare("SELECT uuid FROM tl_files WHERE path=?")
									->limit(1)
									->execute($this->customizeUploadPath($value));

								$value = $objFile->uuid;
							}
						}

						// Replace the file paths in multiSRC fields with their tl_files ID
						elseif (($GLOBALS['TL_DCA'][$table]['fields'][$name]['inputType'] ?? null) == 'fileTree')
						{
							$tmp = StringUtil::deserialize($value);

							if (\is_array($tmp))
							{
								foreach ($tmp as $kk=>$vv)
								{
									// Do not use the FilesModel here – tables are locked!
									$objFile = $db
										->prepare("SELECT uuid FROM tl_files WHERE path=?")
										->limit(1)
										->execute($this->customizeUploadPath($vv));

									$tmp[$kk] = $objFile->uuid;
								}

								$value = serialize($tmp);
							}
						}

						// Adjust the imageSize widget data
						elseif (($GLOBALS['TL_DCA'][$table]['fields'][$name]['inputType'] ?? null) == 'imageSize')
						{
							$imageSizes = StringUtil::deserialize($value, true);

							if (!empty($imageSizes) && is_numeric($imageSizes[2]))
							{
								$imageSizes[2] = $arrMapper['tl_image_size'][$imageSizes[2]];
							}

							$value = serialize($imageSizes);
						}

						$set[$name] = $value;
					}

					// Skip fields that are not in the database (e.g. because of missing extensions)
					foreach ($set as $k=>$v)
					{
						if (!\in_array($k, $arrDbFields[$table]))
						{
							unset($set[$k]);
						}
					}

					// Create the templates folder even if it is empty (see #4793)
					if ($table == 'tl_theme' && isset($set['templates']) && strncmp($set['templates'], 'templates/', 10) === 0 && !is_dir($this->strRootDir . '/' . $set['templates']))
					{
						new Folder($set['templates']);
					}

					// Update tl_files (entries have been created by the Dbafs class)
					if ($table == 'tl_files')
					{
						$db->prepare("UPDATE $table %s WHERE path=?")->set($set)->execute($set['path']);
					}
					else
					{
						$db->prepare("INSERT INTO $table %s")->set($set)->execute();
					}
				}
			}

			// Unlock the tables
			$db->unlockTables();

			// Notify the user
			Message::addConfirmation(sprintf($GLOBALS['TL_LANG']['tl_theme']['theme_imported'], basename($strZipFile)));

			// HOOK: add custom logic
			if (isset($GLOBALS['TL_HOOKS']['extractThemeFiles']) && \is_array($GLOBALS['TL_HOOKS']['extractThemeFiles']))
			{
				$intThemeId = empty($arrMapper['tl_theme']) ? null : reset($arrMapper['tl_theme']);

				foreach ($GLOBALS['TL_HOOKS']['extractThemeFiles'] as $callback)
				{
					System::importStatic($callback[0])->{$callback[1]}($xml, $objArchive, $intThemeId, $arrMapper);
				}
			}

			unset($tl_files, $tl_theme, $tl_module, $tl_layout, $tl_image_size, $tl_image_size_item);
		}

		$objSession = System::getContainer()->get('request_stack')->getSession();
		$objSession->remove('uploaded_themes');

		(new Automator())->generateSymlinks();

		if (($exampleWebsite = Input::post('example_website')) && isset($exampleWebsites[$exampleWebsite]))
		{
			$importType = Input::post('example_website_import');
			$this->importExampleWebsite($exampleWebsites[$exampleWebsite], $importType === 'data_no_truncate', $importType !== 'full');
		}

		$this->redirect(str_replace('&key=importTheme', '', Environment::get('requestUri')));
	}

	private function importExampleWebsite(string $exampleWebsite, bool $preserveData, bool $insertOnly): void
	{
		$connection = System::getContainer()->get('database_connection');
		$userRow = $connection->fetchAssociative('SELECT * FROM tl_user WHERE id = ?', array(BackendUser::getInstance()->id));

		if (!$preserveData && $insertOnly)
		{
			$tables = $connection->createSchemaManager()->listTableNames();

			foreach ($tables as $table)
			{
				if (0 === strncmp($table, 'tl_', 3))
				{
					$connection->executeStatement('TRUNCATE TABLE ' . $connection->quoteIdentifier($table));
				}
			}
		}

		$data = file(Path::join(System::getContainer()->getParameter('kernel.project_dir'), $exampleWebsite));

		try
		{
			foreach ($insertOnly ? preg_grep('/^INSERT /', $data) : $data as $query)
			{
				$connection->executeStatement($query);
			}
		}
		finally
		{
			// Restore backend user
			if ((int) $connection->fetchOne('SELECT COUNT(*) FROM tl_user') < 1)
			{
				$connection->insert(
					'tl_user',
					array_combine(array_map($connection->quoteIdentifier(...), array_keys($userRow)), $userRow),
				);
			}
		}
	}

	/**
	 * Export a theme
	 *
	 * @param DataContainer $dc
	 */
	public function exportTheme(DataContainer $dc)
	{
		if (!System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_EXPORT_THEMES))
		{
			throw new AccessDeniedException('Not enough permissions to export themes.');
		}

		// Get the theme metadata
		$objTheme = Database::getInstance()
			->prepare("SELECT * FROM tl_theme WHERE id=?")
			->limit(1)
			->execute($dc->id);

		if ($objTheme->numRows < 1)
		{
			return;
		}

		// Romanize the name
		$strName = (new UnicodeString($objTheme->name))->ascii()->toString();
		$strName = strtolower(str_replace(' ', '_', $strName));
		$strName = preg_replace('/[^A-Za-z0-9._-]/', '', $strName);
		$strName = basename($strName);

		// Create a new XML document
		$xml = new \DOMDocument('1.0', 'UTF-8');
		$xml->formatOutput = true;

		// Root element
		$tables = $xml->createElement('tables');
		$tables = $xml->appendChild($tables);

		// Add the tables
		$this->addTableTlTheme($xml, $tables, $objTheme);
		$this->addTableTlImageSize($xml, $tables, $objTheme);
		$this->addTableTlModule($xml, $tables, $objTheme);
		$this->addTableTlLayout($xml, $tables, $objTheme);

		// Generate the archive
		$strTmp = md5(uniqid(mt_rand(), true));
		$objArchive = new ZipWriter('system/tmp/' . $strTmp);

		// Add the files
		$this->addTableTlFiles($xml, $tables, $objTheme, $objArchive);

		// Add the template files
		$this->addTemplatesToArchive($objArchive, $objTheme->templates);

		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['exportTheme']) && \is_array($GLOBALS['TL_HOOKS']['exportTheme']))
		{
			foreach ($GLOBALS['TL_HOOKS']['exportTheme'] as $callback)
			{
				System::importStatic($callback[0])->{$callback[1]}($xml, $objArchive, $objTheme->id);
			}
		}

		// Add the XML document
		$objArchive->addString($xml->saveXML(), 'theme.xml');

		// Close the archive
		$objArchive->close();

		// Open the "save as …" dialogue
		$objFile = new File('system/tmp/' . $strTmp);
		$objFile->sendToBrowser($strName . '.cto');
	}

	/**
	 * Add the table tl_theme
	 *
	 * @param \DOMDocument         $xml
	 * @param \DOMNode|\DOMElement $tables
	 * @param Result               $objTheme
	 */
	protected function addTableTlTheme(\DOMDocument $xml, \DOMNode $tables, Result $objTheme)
	{
		// Add the table
		$table = $xml->createElement('table');
		$table->setAttribute('name', 'tl_theme');
		$table = $tables->appendChild($table);

		// Load the DCA
		$this->loadDataContainer('tl_theme');

		// Add the row
		$this->addDataRow($xml, $table, $objTheme->row());
	}

	/**
	 * Add the table tl_module
	 *
	 * @param \DOMDocument         $xml
	 * @param \DOMNode|\DOMElement $tables
	 * @param Result               $objTheme
	 */
	protected function addTableTlModule(\DOMDocument $xml, \DOMNode $tables, Result $objTheme)
	{
		// Add the table
		$table = $xml->createElement('table');
		$table->setAttribute('name', 'tl_module');
		$table = $tables->appendChild($table);

		// Load the DCA
		$this->loadDataContainer('tl_module');

		// Get all modules
		$objModule = Database::getInstance()
			->prepare("SELECT * FROM tl_module WHERE pid=? ORDER BY name")
			->execute($objTheme->id);

		// Add the rows
		while ($objModule->next())
		{
			$this->addDataRow($xml, $table, $objModule->row());
		}
	}

	/**
	 * Add the table tl_layout
	 *
	 * @param \DOMDocument         $xml
	 * @param \DOMNode|\DOMElement $tables
	 * @param Result               $objTheme
	 */
	protected function addTableTlLayout(\DOMDocument $xml, \DOMNode $tables, Result $objTheme)
	{
		// Add the table
		$table = $xml->createElement('table');
		$table->setAttribute('name', 'tl_layout');
		$table = $tables->appendChild($table);

		// Load the DCA
		$this->loadDataContainer('tl_layout');

		// Get all layouts
		$objLayout = Database::getInstance()
			->prepare("SELECT * FROM tl_layout WHERE pid=? ORDER BY name")
			->execute($objTheme->id);

		// Add the rows
		while ($objLayout->next())
		{
			$this->addDataRow($xml, $table, $objLayout->row());
		}
	}

	/**
	 * Add the table tl_image_size
	 *
	 * @param \DOMDocument         $xml
	 * @param \DOMNode|\DOMElement $tables
	 * @param Result               $objTheme
	 */
	protected function addTableTlImageSize(\DOMDocument $xml, \DOMNode $tables, Result $objTheme)
	{
		// Add the tables
		$imageSizeTable = $xml->createElement('table');
		$imageSizeTable->setAttribute('name', 'tl_image_size');
		$imageSizeTable = $tables->appendChild($imageSizeTable);

		$imageSizeItemTable = $xml->createElement('table');
		$imageSizeItemTable->setAttribute('name', 'tl_image_size_item');
		$imageSizeItemTable = $tables->appendChild($imageSizeItemTable);

		$db = Database::getInstance();

		// Get all sizes
		$objSizes = $db
			->prepare("SELECT * FROM tl_image_size WHERE pid=?")
			->execute($objTheme->id);

		// Add the rows
		while ($objSizes->next())
		{
			$this->addDataRow($xml, $imageSizeTable, $objSizes->row());

			// Get all size items
			$objSizeItems = $db
				->prepare("SELECT * FROM tl_image_size_item WHERE pid=?")
				->execute($objSizes->id);

			// Add the rows
			while ($objSizeItems->next())
			{
				$this->addDataRow($xml, $imageSizeItemTable, $objSizeItems->row());
			}
		}
	}

	/**
	 * Add the table tl_files to the XML and the files to the archive
	 * @param \DOMDocument         $xml
	 * @param \DOMNode|\DOMElement $tables
	 * @param Result               $objTheme
	 * @param ZipWriter            $objArchive
	 */
	protected function addTableTlFiles(\DOMDocument $xml, \DOMElement $tables, Result $objTheme, ZipWriter $objArchive)
	{
		// Add the table
		$table = $xml->createElement('table');
		$table->setAttribute('name', 'tl_files');
		$table = $tables->appendChild($table);

		// Load the DCA
		$this->loadDataContainer('tl_files');

		// Add the folders
		$arrFolders = StringUtil::deserialize($objTheme->folders);

		if (!empty($arrFolders) && \is_array($arrFolders))
		{
			$objFolders = FilesModel::findMultipleByUuids($arrFolders);

			if ($objFolders !== null)
			{
				foreach ($this->eliminateNestedPaths($objFolders->fetchEach('path')) as $strFolder)
				{
					$this->addFolderToArchive($objArchive, $strFolder, $xml, $table);
				}
			}
		}
	}

	/**
	 * Add a data row to the XML document
	 * @param \DOMDocument         $xml
	 * @param \DOMNode|\DOMElement $table
	 * @param array                $arrRow
	 */
	protected function addDataRow(\DOMDocument $xml, \DOMElement $table, array $arrRow)
	{
		$t = $table->getAttribute('name');

		$row = $xml->createElement('row');
		$row = $table->appendChild($row);

		foreach ($arrRow as $k=>$v)
		{
			$field = $xml->createElement('field');
			$field->setAttribute('name', $k);
			$field = $row->appendChild($field);

			if ($v === null)
			{
				$v = 'NULL';
			}

			// Replace the IDs of singleSRC fields with their path (see #4952)
			elseif (($GLOBALS['TL_DCA'][$t]['fields'][$k]['inputType'] ?? null) == 'fileTree' && !($GLOBALS['TL_DCA'][$t]['fields'][$k]['eval']['multiple'] ?? null))
			{
				$objFile = FilesModel::findByUuid($v);

				if ($objFile !== null)
				{
					$v = $this->standardizeUploadPath($objFile->path);
				}
				else
				{
					$v = 'NULL';
				}
			}

			// Replace the IDs of multiSRC fields with their paths (see #4952)
			elseif (($GLOBALS['TL_DCA'][$t]['fields'][$k]['inputType'] ?? null) == 'fileTree')
			{
				$arrFiles = StringUtil::deserialize($v);

				if (!empty($arrFiles) && \is_array($arrFiles))
				{
					$objFiles = FilesModel::findMultipleByUuids($arrFiles);

					if ($objFiles !== null)
					{
						$arrTmp = array();

						while ($objFiles->next())
						{
							$arrTmp[] = $this->standardizeUploadPath($objFiles->path);
						}

						$v = serialize($arrTmp);
					}
					else
					{
						$v = 'NULL';
					}
				}
			}

			$value = $xml->createTextNode($v);
			$field->appendChild($value);
		}
	}

	/**
	 * Recursively add a folder to the archive
	 *
	 * @param ZipWriter            $objArchive
	 * @param string               $strFolder
	 * @param \DOMDocument         $xml
	 * @param \DOMNode|\DOMElement $table
	 *
	 * @throws \Exception If the folder path is insecure
	 */
	protected function addFolderToArchive(ZipWriter $objArchive, $strFolder, \DOMDocument $xml, \DOMElement $table)
	{
		$strUploadPath = System::getContainer()->getParameter('contao.upload_path');

		// Strip the custom upload folder name
		$strFolder = preg_replace('@^' . preg_quote($strUploadPath, '@') . '/@', '', $strFolder);

		// Add the default upload folder name
		if (!$strFolder)
		{
			$strTarget = 'files';
			$strFolder = $strUploadPath;
		}
		else
		{
			$strTarget = 'files/' . $strFolder;
			$strFolder = $strUploadPath . '/' . $strFolder;
		}

		if (Validator::isInsecurePath($strFolder))
		{
			throw new \RuntimeException('Insecure path ' . $strFolder);
		}

		// Return if the folder does not exist
		if (!is_dir($this->strRootDir . '/' . $strFolder))
		{
			return;
		}

		// Recursively add the files and subfolders
		foreach (Folder::scan($this->strRootDir . '/' . $strFolder) as $strFile)
		{
			// Skip hidden resources
			if (strncmp($strFile, '.', 1) === 0)
			{
				continue;
			}

			if (is_dir($this->strRootDir . '/' . $strFolder . '/' . $strFile))
			{
				$this->addFolderToArchive($objArchive, $strFolder . '/' . $strFile, $xml, $table);
			}
			else
			{
				// Always store files in files and convert the directory upon import
				$objArchive->addFile($strFolder . '/' . $strFile, $strTarget . '/' . $strFile);

				$arrRow = array();
				$objFile = new File($strFolder . '/' . $strFile);
				$objModel = FilesModel::findByPath($strFolder . '/' . $strFile);

				if ($objModel !== null)
				{
					$arrRow = $objModel->row();

					foreach (array('id', 'pid', 'tstamp', 'uuid', 'type', 'extension', 'found', 'name') as $key)
					{
						unset($arrRow[$key]);
					}
				}

				// Always use files as directory and convert it upon import
				$arrRow['path'] = $strTarget . '/' . $strFile;
				$arrRow['hash'] = $objFile->hash;

				// Add the row
				$this->addDataRow($xml, $table, $arrRow);
			}
		}
	}

	/**
	 * Add templates to the archive
	 *
	 * @param ZipWriter $objArchive
	 * @param string    $strFolder
	 */
	protected function addTemplatesToArchive(ZipWriter $objArchive, $strFolder)
	{
		// Strip the templates folder name
		$strFolder = preg_replace('@^templates/@', '', $strFolder);

		// Re-add the templates folder name
		if (!$strFolder)
		{
			$strFolder = 'templates';
		}
		else
		{
			$strFolder = 'templates/' . $strFolder;
		}

		if (Validator::isInsecurePath($strFolder))
		{
			throw new \RuntimeException('Insecure path ' . $strFolder);
		}

		// Return if the folder does not exist
		if (!is_dir($this->strRootDir . '/' . $strFolder))
		{
			return;
		}

		// Add all template files to the archive (see #7048)
		foreach (Folder::scan($this->strRootDir . '/' . $strFolder) as $strFile)
		{
			if (preg_match('/\.(html5|sql)$/', $strFile) && strncmp($strFile, 'be_', 3) !== 0 && strncmp($strFile, 'nl_', 3) !== 0)
			{
				$objArchive->addFile($strFolder . '/' . $strFile);
			}
		}
	}

	/**
	 * Replace files/ with the custom upload folder name
	 *
	 * @param string $strPath
	 *
	 * @return string
	 */
	protected function customizeUploadPath($strPath)
	{
		if (!$strPath)
		{
			return '';
		}

		return preg_replace('@^(tl_)?files/@', System::getContainer()->getParameter('contao.upload_path') . '/', $strPath);
	}

	/**
	 * Replace a custom upload folder name with files/
	 *
	 * @param string $strPath
	 *
	 * @return string
	 */
	protected function standardizeUploadPath($strPath)
	{
		if (!$strPath)
		{
			return '';
		}

		return preg_replace('@^' . preg_quote(System::getContainer()->getParameter('contao.upload_path'), '@') . '/@', 'files/', $strPath);
	}
}
