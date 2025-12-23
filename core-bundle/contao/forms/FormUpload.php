<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

/**
 * Class FormUpload
 *
 * @property boolean $mandatory
 * @property integer $maxlength
 * @property integer $maxImageWidth
 * @property integer $maxImageHeight
 * @property integer $fSize
 * @property string  $extensions
 * @property string  $uploadFolder
 * @property boolean $doNotOverwrite
 */
class FormUpload extends Widget implements UploadableWidgetInterface
{
	/**
	 * Template
	 *
	 * @var string
	 */
	protected $strTemplate = 'form_upload';

	/**
	 * The CSS class prefix
	 *
	 * @var string
	 */
	protected $strPrefix = 'widget widget-upload';

	/**
	 * Add specific attributes
	 *
	 * @param string $strKey   The attribute name
	 * @param mixed  $varValue The attribute value
	 */
	public function __set($strKey, $varValue)
	{
		switch ($strKey)
		{
			case 'maxlength':
				// Do not add as attribute (see #3094)
				$this->arrConfiguration['maxlength'] = $varValue;
				break;

			case 'mandatory':
				if ($varValue)
				{
					$this->arrAttributes['required'] = 'required';
				}
				else
				{
					unset($this->arrAttributes['required']);
				}
				parent::__set($strKey, $varValue);
				break;

			case 'fSize':
				if ($varValue > 0)
				{
					$this->arrAttributes['size'] = $varValue;
				}
				break;

			case 'extensions':
				if ($varValue)
				{
					$this->arrAttributes['accept'] = '.' . strtolower(implode(',.', \is_array($varValue) ? $varValue : StringUtil::trimsplit(',', $varValue)));
				}
				parent::__set($strKey, $varValue);
				break;

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}

	/**
	 * Return a parameter
	 *
	 * @param string $strKey The parameter key
	 *
	 * @return mixed The parameter value
	 */
	public function __get($strKey)
	{
		switch ($strKey)
		{
			case 'name':
				if ($this->multipleFiles)
				{
					return $this->strName . '[]';
				}

				return $this->strName;

			default:
				return parent::__get($strKey);
		}
	}

	/**
	 * Validate the input and set the value
	 */
	public function validate()
	{
		// No file specified
		if (!isset($_FILES[$this->strName]) || empty($_FILES[$this->strName]['name']))
		{
			if ($this->mandatory)
			{
				if (!$this->strLabel)
				{
					$this->addError($GLOBALS['TL_LANG']['ERR']['mdtryNoLabel']);
				}
				else
				{
					$this->addError(\sprintf($GLOBALS['TL_LANG']['ERR']['mandatory'], $this->strLabel));
				}
			}

			return;
		}

		$files = $_FILES[$this->strName];
		$uploadedFiles = array();
		$fileCount = \is_array($files['name']) ? \count($files['name']) : 1;
		$maxlength_kb = $this->getMaximumUploadSize();
		$maxlength_kb_readable = $this->getReadableSize($maxlength_kb);

		if ($fileCount == 1)
		{
			foreach ($files as $k => $v)
			{
				if (!\is_array($v))
				{
					$files[$k] = array($v);
				}
			}
		}

		if ($fileCount > 1 && !$this->multipleFiles)
		{
			$this->addError($GLOBALS['TL_LANG']['ERR']['multipleFilesNotAllowed']);
			unset($_FILES[$this->strName]);

			return;
		}

		for ($i=0; $i < $fileCount; $i++)
		{
			// Sanitize the filename
			try
			{
				$files['name'][$i] = StringUtil::sanitizeFileName($files['name'][$i]);
			}
			catch (\InvalidArgumentException $e)
			{
				$this->addError($GLOBALS['TL_LANG']['ERR']['filename']);

				return;
			}

			// Invalid file name
			if (!Validator::isValidFileName($files['name'][$i]))
			{
				$this->addError($GLOBALS['TL_LANG']['ERR']['filename']);

				return;
			}

			// File was not uploaded
			if (!is_uploaded_file($files['tmp_name'][$i]))
			{
				if ($files['error'][$i] == 1 || $files['error'][$i] == 2)
				{
					$this->addError(\sprintf($GLOBALS['TL_LANG']['ERR']['filesize'], $maxlength_kb_readable));
				}
				elseif ($files['error'][$i] == 3)
				{
					$this->addError(\sprintf($GLOBALS['TL_LANG']['ERR']['filepartial'], $files['name'][$i]));
				}
				elseif ($files['error'][$i] > 0)
				{
					$this->addError(\sprintf($GLOBALS['TL_LANG']['ERR']['fileerror'], $files['error'][$i], $files['name'][$i]));
				}

				unset($_FILES[$this->strName]);

				return;
			}

			// File is too big
			if ($files['size'][$i] > $maxlength_kb)
			{
				$this->addError(\sprintf($GLOBALS['TL_LANG']['ERR']['filesize'], $maxlength_kb_readable));
				unset($_FILES[$this->strName]);

				return;
			}

			$objFile = new File($files['name'][$i]);
			$uploadTypes = StringUtil::trimsplit(',', strtolower($this->extensions));

			// File type is not allowed
			if (!\in_array($objFile->extension, $uploadTypes))
			{
				$this->addError(\sprintf($GLOBALS['TL_LANG']['ERR']['filetype'], $objFile->extension));
				unset($_FILES[$this->strName]);

				return;
			}

			if ($arrImageSize = @getimagesize($files['tmp_name'][$i]))
			{
				$intImageWidth = $this->maxImageWidth ?: Config::get('imageWidth');

				// Image exceeds maximum image width
				if ($intImageWidth > 0 && $arrImageSize[0] > $intImageWidth)
				{
					$this->addError(\sprintf($GLOBALS['TL_LANG']['ERR']['filewidth'], $files['name'][$i], $intImageWidth));
					unset($_FILES[$this->strName]);

					return;
				}

				$intImageHeight = $this->maxImageHeight ?: Config::get('imageHeight');

				// Image exceeds maximum image height
				if ($intImageHeight > 0 && $arrImageSize[1] > $intImageHeight)
				{
					$this->addError(\sprintf($GLOBALS['TL_LANG']['ERR']['fileheight'], $files['name'][$i], $intImageHeight));
					unset($_FILES[$this->strName]);

					return;
				}
			}

			// Sanitize SVGs
			if (\in_array($objFile->extension, array('svg', 'svgz')) && !FileUpload::sanitizeSvg($files['tmp_name'][$i]))
			{
				$this->addError(\sprintf($GLOBALS['TL_LANG']['ERR']['fileerror'], 'Invalid SVG', $files['name'][$i]));
				unset($_FILES[$this->strName]);

				return;
			}

			// Store the file on the server if enabled
			if (!$this->hasErrors())
			{
				$this->varValue = $_FILES[$this->strName];

				if ($this->storeFile)
				{
					$intUploadFolder = $this->uploadFolder;

					// Overwrite the upload folder with user's home directory
					if ($this->useHomeDir && System::getContainer()->get('contao.security.token_checker')->hasFrontendUser())
					{
						$user = FrontendUser::getInstance();

						if ($user->assignDir && $user->homeDir)
						{
							$intUploadFolder = $user->homeDir;
						}
					}

					$objUploadFolder = FilesModel::findByUuid($intUploadFolder);

					// The upload folder could not be found
					if ($objUploadFolder === null)
					{
						throw new \Exception("Invalid upload folder ID $intUploadFolder");
					}

					$strUploadFolder = $objUploadFolder->path;
					$projectDir = System::getContainer()->getParameter('kernel.project_dir');

					// Store the file if the upload folder exists
					if ($strUploadFolder && is_dir($projectDir . '/' . $strUploadFolder))
					{
						// Do not overwrite existing files
						if ($this->doNotOverwrite && file_exists($projectDir . '/' . $strUploadFolder . '/' . $files['name'][$i]))
						{
							$offset = 1;

							$arrAll = Folder::scan($projectDir . '/' . $strUploadFolder, true);
							$arrFiles = preg_grep('/^' . preg_quote($objFile->filename, '/') . '.*\.' . preg_quote($objFile->extension, '/') . '/', $arrAll);

							foreach ($arrFiles as $strFile)
							{
								if (preg_match('/__[0-9]+\.' . preg_quote($objFile->extension, '/') . '$/', $strFile))
								{
									$strFile = str_replace('.' . $objFile->extension, '', $strFile);
									$intValue = (int) substr($strFile, strrpos($strFile, '_') + 1);

									$offset = max($offset, $intValue);
								}
							}

							$files['name'][$i] = str_replace($objFile->filename, $objFile->filename . '__' . ++$offset, $files['name'][$i]);
						}

						// Move the file to its destination
						$filesObj = Files::getInstance();
						$filesObj->move_uploaded_file($files['tmp_name'][$i], $strUploadFolder . '/' . $files['name'][$i]);
						$filesObj->chmod($strUploadFolder . '/' . $files['name'][$i], 0666 & ~umask());

						$strUuid = null;
						$strFile = $strUploadFolder . '/' . $files['name'][$i];

						// Generate the DB entries
						if (Dbafs::shouldBeSynchronized($strFile))
						{
							$objModel = FilesModel::findByPath($strFile);

							if ($objModel === null)
							{
								$objModel = Dbafs::addResource($strFile);
							}

							$strUuid = StringUtil::binToUuid($objModel->uuid);

							// Update the hash of the target folder
							Dbafs::updateFolderHashes($strUploadFolder);
						}

						$uploadedFiles[] = array
						(
							'name'     => $files['name'][$i],
							'type'     => $files['type'][$i],
							'tmp_name' => $projectDir . '/' . $strFile,
							'error'    => $files['error'][$i],
							'size'     => $files['size'][$i],
							'uploaded' => true,
							'uuid'     => $strUuid
						);

						System::getContainer()->get('monolog.logger.contao.files')->info('File "' . $strUploadFolder . '/' . $files['name'][$i] . '" has been uploaded');
					}
				}
				else
				{
					$uploadedFiles[] = array
					(
						'name'     => $files['name'][$i],
						'type'     => $files['type'][$i],
						'tmp_name' => $files['tmp_name'][$i],
						'error'    => $files['error'][$i],
						'size'     => $files['size'][$i]
					);
				}
			}
		}

		if (!empty($uploadedFiles))
		{
			$this->varValue = ($this->multipleFiles) ? $uploadedFiles : $uploadedFiles[0];
		}

		unset($_FILES[$this->strName]);
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string The widget markup
	 */
	public function generate()
	{
		return \sprintf(
			'<input type="file" name="%s" id="ctrl_%s" class="upload%s"%s%s',
			$this->strName,
			$this->strId,
			$this->strClass ? ' ' . $this->strClass : '',
			$this->getAttributes(),
			$this->strTagEnding
		);
	}

	/**
	 * Return the maximum upload file size in bytes
	 *
	 * @return string
	 */
	protected function getMaximumUploadSize()
	{
		if ($this->maxlength > 0)
		{
			return $this->maxlength;
		}

		return FileUpload::getMaxUploadSize();
	}
}
