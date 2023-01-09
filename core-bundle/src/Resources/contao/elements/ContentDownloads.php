<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Model\Collection;

/**
 * Front end content element "downloads".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContentDownloads extends ContentElement
{
	/**
	 * Files object
	 * @var Collection|FilesModel
	 */
	protected $objFiles;

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_downloads';

	/**
	 * Return if there are no files
	 *
	 * @return string
	 */
	public function generate()
	{
		if ($this->isHidden())
		{
			return '';
		}

		// Use the home directory of the current user as file source
		if ($this->useHomeDir && System::getContainer()->get('contao.security.token_checker')->hasFrontendUser())
		{
			$this->import(FrontendUser::class, 'User');

			if ($this->User->assignDir && $this->User->homeDir)
			{
				$this->multiSRC = array($this->User->homeDir);
			}
		}
		else
		{
			$this->multiSRC = StringUtil::deserialize($this->multiSRC);
		}

		// Return if there are no files
		if (empty($this->multiSRC) && !\is_array($this->multiSRC))
		{
			return '';
		}

		// Get the file entries from the database
		$this->objFiles = FilesModel::findMultipleByUuids($this->multiSRC);

		if ($this->objFiles === null)
		{
			return '';
		}

		$file = Input::get('file', true);

		// Send the file to the browser (see #4632 and #8375)
		if ($file && \is_string($file) && (!isset($_GET['cid']) || Input::get('cid') == $this->id))
		{
			while ($this->objFiles->next())
			{
				if ($file == $this->objFiles->path || \dirname($file) == $this->objFiles->path)
				{
					Controller::sendFileToBrowser($file, (bool) $this->inline);
				}
			}

			if (isset($_GET['cid']))
			{
				throw new PageNotFoundException('Invalid file name');
			}

			$this->objFiles->reset();
		}

		return parent::generate();
	}

	/**
	 * Generate the content element
	 */
	protected function compile()
	{
		/** @var PageModel $objPage */
		global $objPage;

		$files = array();
		$auxDate = array();

		$objFiles = $this->objFiles;
		$allowedDownload = StringUtil::trimsplit(',', strtolower(Config::get('allowedDownload')));

		// Get all files
		while ($objFiles->next())
		{
			// Continue if the files has been processed or does not exist
			if (isset($files[$objFiles->path]) || !file_exists(System::getContainer()->getParameter('kernel.project_dir') . '/' . $objFiles->path))
			{
				continue;
			}

			// Single files
			if ($objFiles->type == 'file')
			{
				$objFile = new File($objFiles->path);

				if (!\in_array($objFile->extension, $allowedDownload) || preg_match('/^meta(_[a-z]{2})?\.txt$/', $objFile->basename))
				{
					continue;
				}

				$arrMeta = $this->getMetaData($objFiles->meta, $objPage->language);

				if (empty($arrMeta))
				{
					if ($this->metaIgnore)
					{
						continue;
					}

					if ($objPage->rootFallbackLanguage !== null)
					{
						$arrMeta = $this->getMetaData($objFiles->meta, $objPage->rootFallbackLanguage);
					}
				}

				// Use the file name as title if none is given
				if (!$arrMeta['title'])
				{
					$arrMeta['title'] = StringUtil::specialchars($objFile->basename);
				}

				$strHref = Environment::get('request');

				// Remove an existing file parameter (see #5683)
				if (isset($_GET['file']))
				{
					$strHref = preg_replace('/(&(amp;)?|\?)file=[^&]+/', '', $strHref);
				}

				if (isset($_GET['cid']))
				{
					$strHref = preg_replace('/(&(amp;)?|\?)cid=\d+/', '', $strHref);
				}

				$strHref .= (strpos($strHref, '?') !== false ? '&amp;' : '?') . 'file=' . System::urlEncode($objFiles->path) . '&amp;cid=' . $this->id;

				// Add the image
				$files[$objFiles->path] = array
				(
					'id'        => $objFiles->id,
					'uuid'      => $objFiles->uuid,
					'name'      => $objFile->basename,
					'title'     => StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['download'], $objFile->basename)),
					'link'      => $arrMeta['title'],
					'caption'   => $arrMeta['caption'],
					'href'      => $strHref,
					'filesize'  => $this->getReadableSize($objFile->filesize),
					'icon'      => Image::getPath($objFile->icon),
					'mime'      => $objFile->mime,
					'meta'      => $arrMeta,
					'extension' => $objFile->extension,
					'path'      => $objFile->dirname
				);

				$auxDate[] = $objFile->mtime;
			}

			// Folders
			else
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

					if (!\in_array($objFile->extension, $allowedDownload) || preg_match('/^meta(_[a-z]{2})?\.txt$/', $objFile->basename))
					{
						continue;
					}

					$arrMeta = $this->getMetaData($objSubfiles->meta, $objPage->language);

					if (empty($arrMeta))
					{
						if ($this->metaIgnore)
						{
							continue;
						}

						if ($objPage->rootFallbackLanguage !== null)
						{
							$arrMeta = $this->getMetaData($objSubfiles->meta, $objPage->rootFallbackLanguage);
						}
					}

					// Use the file name as title if none is given
					if (!$arrMeta['title'])
					{
						$arrMeta['title'] = StringUtil::specialchars($objFile->basename);
					}

					$strHref = Environment::get('request');

					// Remove an existing file parameter (see #5683)
					if (preg_match('/(&(amp;)?|\?)file=/', $strHref))
					{
						$strHref = preg_replace('/(&(amp;)?|\?)file=[^&]+/', '', $strHref);
					}

					$strHref .= (strpos($strHref, '?') !== false ? '&amp;' : '?') . 'file=' . System::urlEncode($objSubfiles->path);

					// Add the image
					$files[$objSubfiles->path] = array
					(
						'id'        => $objSubfiles->id,
						'uuid'      => $objSubfiles->uuid,
						'name'      => $objFile->basename,
						'title'     => StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['download'], $objFile->basename)),
						'link'      => $arrMeta['title'],
						'caption'   => $arrMeta['caption'],
						'href'      => $strHref,
						'filesize'  => $this->getReadableSize($objFile->filesize),
						'icon'      => Image::getPath($objFile->icon),
						'mime'      => $objFile->mime,
						'meta'      => $arrMeta,
						'extension' => $objFile->extension,
						'path'      => $objFile->dirname
					);

					$auxDate[] = $objFile->mtime;
				}
			}
		}

		// Sort array
		switch ($this->sortBy)
		{
			default:
			case 'name_asc':
				uksort($files, 'basename_natcasecmp');
				break;

			case 'name_desc':
				uksort($files, 'basename_natcasercmp');
				break;

			case 'date_asc':
				array_multisort($files, SORT_NUMERIC, $auxDate, SORT_ASC);
				break;

			case 'date_desc':
				array_multisort($files, SORT_NUMERIC, $auxDate, SORT_DESC);
				break;

			// Deprecated since Contao 4.0, to be removed in Contao 5.0
			case 'meta':
				@trigger_error('The "meta" key in ContentDownloads::compile() has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);
				// no break

			case 'custom':
				if ($this->orderSRC)
				{
					$tmp = StringUtil::deserialize($this->orderSRC);

					if (!empty($tmp) && \is_array($tmp))
					{
						// Remove all values
						$arrOrder = array_map(static function () {}, array_flip($tmp));

						// Move the matching elements to their position in $arrOrder
						foreach ($files as $k=>$v)
						{
							if (\array_key_exists($v['uuid'], $arrOrder))
							{
								$arrOrder[$v['uuid']] = $v;
								unset($files[$k]);
							}
						}

						// Append the left-over files at the end
						if (!empty($files))
						{
							$arrOrder = array_merge($arrOrder, array_values($files));
						}

						// Remove empty (unreplaced) entries
						$files = array_values(array_filter($arrOrder));
						unset($arrOrder);
					}
				}
				break;

			case 'random':
				shuffle($files);
				break;
		}

		$this->Template->files = array_values($files);
	}
}

class_alias(ContentDownloads::class, 'ContentDownloads');
