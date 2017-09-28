<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;


/**
 * Front end module "random image".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ModuleRandomImage extends \Module
{

	/**
	 * Files object
	 * @var Model\Collection|FilesModel
	 */
	protected $objFiles;

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_randomImage';


	/**
	 * Check the source folder
	 *
	 * @return string
	 */
	public function generate()
	{
		$this->multiSRC = \StringUtil::deserialize($this->multiSRC);

		if (!is_array($this->multiSRC) || empty($this->multiSRC))
		{
			return '';
		}

		$this->objFiles = \FilesModel::findMultipleByUuids($this->multiSRC);

		if ($this->objFiles === null)
		{
			return '';
		}

		return parent::generate();
	}


	/**
	 * Generate the module
	 */
	protected function compile()
	{
		$images = array();
		$objFiles = $this->objFiles;

		// Get all images
		while ($objFiles->next())
		{
			// Continue if the files has been processed or does not exist
			if (isset($images[$objFiles->path]) || !file_exists(TL_ROOT . '/' . $objFiles->path))
			{
				continue;
			}

			// Single files
			if ($objFiles->type == 'file')
			{
				$objFile = new \File($objFiles->path);

				if (!$objFile->isImage)
				{
					continue;
				}

				// Add the image
				$images[$objFiles->path] = array
				(
					'id'         => $objFiles->id,
					'name'       => $objFile->basename,
					'singleSRC'  => $objFiles->path,
					'title'      => \StringUtil::specialchars($objFile->basename),
					'filesModel' => $objFiles->current()
				);
			}

			// Folders
			else
			{
				$objSubfiles = \FilesModel::findByPid($objFiles->uuid);

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

					$objFile = new \File($objSubfiles->path);

					if (!$objFile->isImage)
					{
						continue;
					}

					// Add the image
					$images[$objSubfiles->path] = array
					(
						'id'         => $objSubfiles->id,
						'name'       => $objFile->basename,
						'singleSRC'  => $objSubfiles->path,
						'title'      => \StringUtil::specialchars($objFile->basename),
						'filesModel' => $objSubfiles->current()
					);
				}
			}
		}

		$images = array_values($images);

		if (empty($images))
		{
			return;
		}

		$i = mt_rand(0, (count($images)-1));

		$arrImage = $images[$i];

		$arrImage['size'] = $this->imgSize;
		$arrImage['fullsize'] = $this->fullsize;

		if (!$this->useCaption)
		{
			$arrImage['caption'] = null;
		}
		elseif ($arrImage['caption'] == '')
		{
			$arrImage['caption'] = $arrImage['title'];
		}

		$this->addImageToTemplate($this->Template, $arrImage, null, null, $arrImage['filesModel']);
	}
}
