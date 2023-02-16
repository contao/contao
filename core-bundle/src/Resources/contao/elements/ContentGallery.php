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
 * Front end content element "gallery".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContentGallery extends ContentElement
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
	protected $strTemplate = 'ce_gallery';

	/**
	 * Return if there are no files
	 *
	 * @return string
	 */
	public function generate()
	{
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
		if (empty($this->multiSRC) || !\is_array($this->multiSRC))
		{
			return '';
		}

		// Get the file entries from the database
		$this->objFiles = FilesModel::findMultipleByUuids($this->multiSRC);

		if ($this->objFiles === null)
		{
			return '';
		}

		// Make sure we have at least one item per row to prevent division by zero
		if ($this->perRow < 1)
		{
			$this->perRow = 1;
		}

		return parent::generate();
	}

	/**
	 * Generate the content element
	 */
	protected function compile()
	{
		$images = array();
		$auxDate = array();
		$objFiles = $this->objFiles;

		// Get all images
		while ($objFiles->next())
		{
			// Continue if the files has been processed or does not exist
			if (isset($images[$objFiles->path]) || !file_exists(System::getContainer()->getParameter('kernel.project_dir') . '/' . $objFiles->path))
			{
				continue;
			}

			// Single files
			if ($objFiles->type == 'file')
			{
				$objFile = new File($objFiles->path);

				if (!$objFile->isImage)
				{
					continue;
				}

				// Add the image
				$images[$objFiles->path] = array
				(
					'id'         => $objFiles->id,
					'uuid'       => $objFiles->uuid,
					'name'       => $objFile->basename,
					'singleSRC'  => $objFiles->path,
					'filesModel' => $objFiles->current()
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

					if (!$objFile->isImage)
					{
						continue;
					}

					// Add the image
					$images[$objSubfiles->path] = array
					(
						'id'         => $objSubfiles->id,
						'uuid'       => $objSubfiles->uuid,
						'name'       => $objFile->basename,
						'singleSRC'  => $objSubfiles->path,
						'filesModel' => $objSubfiles->current()
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
				uksort($images, 'basename_natcasecmp');
				break;

			case 'name_desc':
				uksort($images, 'basename_natcasercmp');
				break;

			case 'date_asc':
				array_multisort($images, SORT_NUMERIC, $auxDate, SORT_ASC);
				break;

			case 'date_desc':
				array_multisort($images, SORT_NUMERIC, $auxDate, SORT_DESC);
				break;

			// Deprecated since Contao 4.0, to be removed in Contao 5.0
			case 'meta':
				@trigger_error('The "meta" key in ContentGallery::compile() has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);
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
						foreach ($images as $k=>$v)
						{
							if (\array_key_exists($v['uuid'], $arrOrder))
							{
								$arrOrder[$v['uuid']] = $v;
								unset($images[$k]);
							}
						}

						// Append the left-over images at the end
						if (!empty($images))
						{
							$arrOrder = array_merge($arrOrder, array_values($images));
						}

						// Remove empty (unreplaced) entries
						$images = array_values(array_filter($arrOrder));
						unset($arrOrder);
					}
				}
				break;

			case 'random':
				shuffle($images);
				$this->Template->isRandomOrder = true;
				break;
		}

		$images = array_values($images);

		// Limit the total number of items (see #2652)
		if ($this->numberOfItems > 0)
		{
			$images = \array_slice($images, 0, $this->numberOfItems);
		}

		$offset = 0;
		$total = \count($images);
		$limit = $total;

		// Paginate the result of not randomly sorted (see #8033)
		if ($this->perPage > 0 && $this->sortBy != 'random')
		{
			// Get the current page
			$id = 'page_g' . $this->id;
			$page = Input::get($id) ?? 1;

			// Do not index or cache the page if the page number is outside the range
			if ($page < 1 || $page > max(ceil($total/$this->perPage), 1))
			{
				throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
			}

			// Set limit and offset
			$offset = ($page - 1) * $this->perPage;
			$limit = min($this->perPage + $offset, $total);

			$objPagination = new Pagination($total, $this->perPage, Config::get('maxPaginationLinks'), $id);
			$this->Template->pagination = $objPagination->generate("\n  ");
		}

		$rowcount = 0;
		$colwidth = floor(100/$this->perRow);
		$strLightboxId = 'lb' . $this->id;
		$body = array();

		// Rows
		for ($i=$offset; $i<$limit; $i+=$this->perRow)
		{
			$class_tr = '';

			if ($rowcount == 0)
			{
				$class_tr .= ' row_first';
			}

			if (($i + $this->perRow) >= $limit)
			{
				$class_tr .= ' row_last';
			}

			$class_eo = (($rowcount % 2) == 0) ? ' even' : ' odd';

			// Columns
			for ($j=0; $j<$this->perRow; $j++)
			{
				$class_td = '';

				if ($j == 0)
				{
					$class_td .= ' col_first';
				}

				if ($j == ($this->perRow - 1))
				{
					$class_td .= ' col_last';
				}

				$objCell = new \stdClass();
				$key = 'row_' . $rowcount . $class_tr . $class_eo;

				// Empty cell
				if (($j+$i) >= $limit || !\is_array($images[($i+$j)]))
				{
					$objCell->colWidth = $colwidth . '%';
					$objCell->class = 'col_' . $j . $class_td;
				}
				else
				{
					// Add size and margin
					$images[($i+$j)]['size'] = $this->size;
					$images[($i+$j)]['imagemargin'] = $this->imagemargin;
					$images[($i+$j)]['fullsize'] = $this->fullsize;

					$this->addImageToTemplate($objCell, $images[($i+$j)], null, $strLightboxId, $images[($i+$j)]['filesModel']);

					// Add column width and class
					$objCell->colWidth = $colwidth . '%';
					$objCell->class = 'col_' . $j . $class_td;
				}

				$body[$key][$j] = $objCell;
			}

			++$rowcount;
		}

		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		// Always use the default template in the back end
		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$this->galleryTpl = '';
		}

		$objTemplate = new FrontendTemplate($this->galleryTpl ?: 'gallery_default');
		$objTemplate->setData($this->arrData);
		$objTemplate->body = $body;
		$objTemplate->headline = $this->headline; // see #1603

		$this->Template->images = $objTemplate->parse();
	}
}

class_alias(ContentGallery::class, 'ContentGallery');
