<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Image\Preview\MissingPreviewProviderException;
use Contao\CoreBundle\Image\Preview\UnableToGeneratePreviewException;
use Contao\Image\PictureConfiguration;
use Contao\Image\PictureConfigurationItem;
use Contao\Image\ResizeConfiguration;

/**
 * Provide methods to handle input field "file tree".
 *
 * @property boolean $isSortable
 * @property boolean $multiple
 * @property boolean $isGallery
 * @property boolean $isDownloads
 * @property boolean $files
 * @property boolean $filesOnly
 * @property boolean $showFilePreview
 * @property string  $path
 * @property string  $extensions
 * @property string  $fieldType
 */
class FileTree extends Widget
{
	/**
	 * Submit user input
	 * @var boolean
	 */
	protected $blnSubmitInput = true;

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'be_widget';

	public function __set($strKey, $varValue)
	{
		if ($strKey === 'extensions' && \is_array($varValue))
		{
			$varValue = implode(',', $varValue);
		}

		parent::__set($strKey, $varValue);
	}

	/**
	 * Return an array if the "multiple" attribute is set
	 *
	 * @param mixed $varInput
	 *
	 * @return mixed
	 */
	protected function validator($varInput)
	{
		$this->checkValue($varInput);

		if ($this->hasErrors())
		{
			return '';
		}

		// Return the value as usual
		if (!$varInput)
		{
			if ($this->mandatory)
			{
				$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['mandatory'], $this->strLabel));
			}

			return '';
		}

		if (!str_contains($varInput, ','))
		{
			$varInput = StringUtil::uuidToBin($varInput);

			return $this->multiple ? array($varInput) : $varInput;
		}

		$arrValue = array_values(array_filter(explode(',', $varInput)));

		return $this->multiple ? array_map('\Contao\StringUtil::uuidToBin', $arrValue) : StringUtil::uuidToBin($arrValue[0]);
	}

	/**
	 * Check the selected value
	 *
	 * @param mixed $varInput
	 */
	protected function checkValue($varInput)
	{
		if (!$varInput)
		{
			return;
		}

		if (!str_contains($varInput, ','))
		{
			$arrUuids = array($varInput);
		}
		else
		{
			$arrUuids = array_filter(explode(',', $varInput));
		}

		$objFiles = FilesModel::findMultipleByUuids($arrUuids);

		if ($objFiles === null)
		{
			return;
		}

		$projectDir = System::getContainer()->getParameter('kernel.project_dir');

		foreach ($objFiles as $objFile)
		{
			// Only files can be selected
			if ($this->filesOnly && is_dir($projectDir . '/' . $objFile->path))
			{
				$this->addError($GLOBALS['TL_LANG']['ERR']['filesOnly']);
				break;
			}

			// Only folders can be selected
			if ($this->files === false && !is_dir($projectDir . '/' . $objFile->path))
			{
				$this->addError($GLOBALS['TL_LANG']['ERR']['foldersOnly']);
				break;
			}

			// Only files within a custom path can be selected
			if ($this->path && !str_starts_with($objFile->path, $this->path . '/'))
			{
				$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['pathOnly'], $this->path));
				break;
			}

			// Only certain file types can be selected
			if ($this->extensions && !is_dir($projectDir . '/' . $objFile->path))
			{
				$objFile = new File($objFile->path);
				$extensions = StringUtil::trimsplit(',', $this->extensions);

				if (!\in_array($objFile->extension, $extensions))
				{
					$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['extensionsOnly'], $this->extensions));
					break;
				}
			}
		}
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		$arrSet = array();
		$arrValues = array();

		// $this->varValue can be an array, so use empty() here
		if (!empty($this->varValue))
		{
			$objFiles = FilesModel::findMultipleByUuids((array) $this->varValue);
			$allowedDownload = StringUtil::trimsplit(',', strtolower(Config::get('allowedDownload')));
			$projectDir = System::getContainer()->getParameter('kernel.project_dir');

			if ($objFiles !== null)
			{
				while ($objFiles->next())
				{
					// File system and database seem not in sync
					if (!file_exists($projectDir . '/' . $objFiles->path))
					{
						continue;
					}

					$arrSet[$objFiles->id] = $objFiles->uuid;

					// Show files and folders
					if (!$this->isGallery && !$this->isDownloads)
					{
						if ($objFiles->type == 'folder')
						{
							$arrValues[$objFiles->uuid] = Image::getHtml('folderC.svg') . ' ' . $objFiles->path;
						}
						else
						{
							$objFile = new File($objFiles->path);
							$strInfo = $objFiles->path . ' <span class="tl_gray">(' . $this->getReadableSize($objFile->size) . ($objFile->isImage ? ', ' . $objFile->width . 'x' . $objFile->height . ' px' : '') . ')</span>';

							if ($this->showAsImage($objFile))
							{
								$arrValues[$objFiles->uuid] = $this->getPreviewImage($objFile, $strInfo);
							}
							else
							{
								$arrValues[$objFiles->uuid] = Image::getHtml($objFile->icon) . ' ' . $strInfo;
							}
						}
					}

					// Show a sortable list of files only
					elseif ($objFiles->type == 'folder')
					{
						$objSubfiles = FilesModel::findByPid($objFiles->uuid, array('order' => 'name'));

						if ($objSubfiles === null)
						{
							continue;
						}

						while ($objSubfiles->next())
						{
							// Skip subfolders
							if ($objSubfiles->type == 'folder')
							{
								continue;
							}

							$objFile = new File($objSubfiles->path);
							$strInfo = '<span class="dirname">' . \dirname($objSubfiles->path) . '/</span>' . $objFile->basename . ' <span class="tl_gray">(' . $this->getReadableSize($objFile->size) . ($objFile->isImage ? ', ' . $objFile->width . 'x' . $objFile->height . ' px' : '') . ')</span>';

							if ($this->isGallery)
							{
								// Only show images
								if ($objFile->isImage)
								{
									$arrValues[$objSubfiles->uuid] = $this->getPreviewImage($objFile, $strInfo);
								}
							}
							// Only show allowed download types
							elseif (\in_array($objFile->extension, $allowedDownload))
							{
								if ($this->showAsImage($objFile))
								{
									$arrValues[$objSubfiles->uuid] = $this->getPreviewImage($objFile, $strInfo);
								}
								else
								{
									$arrValues[$objSubfiles->uuid] = Image::getHtml($objFile->icon) . ' ' . $strInfo;
								}
							}
						}
					}
					else
					{
						$objFile = new File($objFiles->path);
						$strInfo = '<span class="dirname">' . \dirname($objFiles->path) . '/</span>' . $objFile->basename . ' <span class="tl_gray">(' . $this->getReadableSize($objFile->size) . ($objFile->isImage ? ', ' . $objFile->width . 'x' . $objFile->height . ' px' : '') . ')</span>';

						if ($this->isGallery)
						{
							// Only show images
							if ($objFile->isImage)
							{
								$arrValues[$objFiles->uuid] = $this->getPreviewImage($objFile, $strInfo, 'gimage removable');
							}
						}
						// Only show allowed download types
						elseif (\in_array($objFile->extension, $allowedDownload))
						{
							if ($this->showAsImage($objFile))
							{
								$arrValues[$objFiles->uuid] = $this->getPreviewImage($objFile, $strInfo, 'gimage removable');
							}
							else
							{
								$arrValues[$objFiles->uuid] = Image::getHtml($objFile->icon) . ' ' . $strInfo;
							}
						}
					}
				}
			}
		}

		// Convert the binary UUIDs
		$strSet = implode(',', array_map('\Contao\StringUtil::binToUuid', $arrSet));

		$return = '<input type="hidden" name="' . $this->strName . '" id="ctrl_' . $this->strId . '" value="' . $strSet . '"' . ($this->onchange ? ' onchange="' . $this->onchange . '"' : '') . '>
  <div class="selector_container">' . (($this->isSortable && \count($arrValues) > 1) ? '
    <p class="sort_hint">' . $GLOBALS['TL_LANG']['MSC']['dragItemsHint'] . '</p>' : '') . '
    <ul id="sort_' . $this->strId . '" class="' . trim(($this->isSortable ? 'sortable ' : '') . ($this->isGallery ? 'sgallery' : '')) . '">';

		foreach ($arrValues as $k=>$v)
		{
			$return .= '<li data-id="' . StringUtil::binToUuid($k) . '">' . $v . '</li>';
		}

		$return .= '</ul>';

		if (!System::getContainer()->get('contao.picker.builder')->supportsContext('file'))
		{
			$return .= '
	<p><button class="tl_submit" disabled>' . $GLOBALS['TL_LANG']['MSC']['changeSelection'] . '</button></p>';
		}
		else
		{
			$extras = $this->getPickerUrlExtras($arrValues);

			$return .= '
    <p><a href="' . StringUtil::ampersand(System::getContainer()->get('contao.picker.builder')->getUrl('file', $extras)) . '" class="tl_submit" id="ft_' . $this->strName . '">' . $GLOBALS['TL_LANG']['MSC']['changeSelection'] . '</a></p>
    <script>
      $("ft_' . $this->strName . '").addEvent("click", function(e) {
        e.preventDefault();
        Backend.openModalSelector({
          "id": "tl_listing",
          "title": ' . json_encode($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['label'][0] ?? '') . ',
          "url": this.href + document.getElementById("ctrl_' . $this->strId . '").value,
          "callback": function(table, value) {
            new Request.Contao({
              evalScripts: false,
              onSuccess: function(txt, json) {
                $("ctrl_' . $this->strId . '").getParent("div").set("html", json.content);
                json.javascript && Browser.exec(json.javascript);
                var evt = document.createEvent("HTMLEvents");
                evt.initEvent("change", true, true);
                $("ctrl_' . $this->strId . '").dispatchEvent(evt);
              }
            }).post({"action":"reloadFiletree", "name":"' . $this->strName . '", "value":value.join("\t"), "REQUEST_TOKEN":"' . System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue() . '"});
          }
        });
      });
    </script>' . ($this->isSortable ? '
    <script>Backend.makeMultiSrcSortable("sort_' . $this->strId . '", "ctrl_' . $this->strId . '", "ctrl_' . $this->strId . '")</script>' : '');
		}

		$return = '<div>' . $return . '</div></div>';

		return $return;
	}

	/**
	 * Return the extra parameters for the picker URL
	 *
	 * @param array $values
	 *
	 * @return array
	 */
	protected function getPickerUrlExtras($values = array())
	{
		$extras = array();
		$extras['fieldType'] = $this->fieldType;

		if ($this->files)
		{
			$extras['files'] = $this->files;
		}

		if ($this->filesOnly)
		{
			$extras['filesOnly'] = $this->filesOnly;
		}

		if ($this->path)
		{
			$extras['path'] = $this->path;
		}

		if ($this->extensions)
		{
			$extras['extensions'] = $this->extensions;
		}

		return $extras;
	}

	/**
	 * Return the preview image
	 *
	 * @param File   $objFile
	 * @param string $strInfo
	 * @param string $strClass
	 *
	 * @return string
	 */
	protected function getPreviewImage(File $objFile, $strInfo, $strClass='gimage')
	{
		if ($previewPath = $this->getFilePreviewPath($objFile->path))
		{
			$objFile = new File(StringUtil::stripRootDir($previewPath));
		}

		if ($objFile->viewWidth && $objFile->viewHeight)
		{
			$container = System::getContainer();
			$projectDir = $container->getParameter('kernel.project_dir');

			$resizeConfig = (new ResizeConfiguration())
				->setWidth(100)
				->setHeight(75)
				->setMode(ResizeConfiguration::MODE_BOX);

			$pictureConfig = (new PictureConfiguration())
				->setSize(
					(new PictureConfigurationItem())
						->setResizeConfig($resizeConfig)
						->setDensities('1x, 2x')
				);

			$picture = $container
				->get('contao.image.preview_factory')
				->createPreviewPicture($projectDir . '/' . $objFile->path, $pictureConfig);

			$img = $picture->getImg($projectDir);

			return sprintf('<img src="%s"%s width="%s" height="%s" alt class="%s" title="%s" loading="lazy">', $img['src'], $img['srcset'] != $img['src'] ? ' srcset="' . $img['srcset'] . '"' : '', $img['width'], $img['height'], $strClass, StringUtil::specialchars($strInfo));
		}

		return Image::getHtml('placeholder.svg', '', 'class="' . $strClass . '" title="' . StringUtil::specialchars($strInfo) . '"');
	}

	private function getFilePreviewPath(string $path): string|null
	{
		if (!$this->showFilePreview)
		{
			return null;
		}

		$container = System::getContainer();
		$factory = $container->get('contao.image.preview_factory');
		$sourcePath = $container->getParameter('kernel.project_dir') . '/' . $path;

		try
		{
			return $factory->createPreview($sourcePath, 100)->getPath();
		}
		catch (UnableToGeneratePreviewException|MissingPreviewProviderException $exception)
		{
			return null;
		}
	}

	private function showAsImage(File $objFile): bool
	{
		if ($objFile->isImage && !$this->isDownloads)
		{
			return true;
		}

		return $this->getFilePreviewPath($objFile->path) !== null;
	}
}
