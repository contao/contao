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
use Contao\CoreBundle\Image\Preview\MissingPreviewProviderException;
use Contao\CoreBundle\Image\Preview\UnableToGeneratePreviewException;
use Contao\Image\PictureConfiguration;
use Contao\Image\PictureConfigurationItem;
use Contao\Image\ResizeConfiguration;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pop-up file preview (file manager).
 */
class BackendPopup extends Backend
{
	/**
	 * File
	 * @var string
	 */
	protected $strFile;

	/**
	 * Initialize the controller
	 *
	 * 1. Import the user
	 * 2. Call the parent constructor
	 * 3. Authenticate the user
	 * 4. Load the language files
	 * DO NOT CHANGE THIS ORDER!
	 */
	public function __construct()
	{
		parent::__construct();

		if (!System::getContainer()->get('security.authorization_checker')->isGranted('ROLE_USER'))
		{
			throw new AccessDeniedException('Access denied');
		}

		System::loadLanguageFile('default');

		$strFile = Input::get('src', true);
		$strFile = base64_decode($strFile);
		$strFile = ltrim(rawurldecode($strFile), '/');

		$this->strFile = $strFile;
	}

	/**
	 * Run the controller and parse the template
	 *
	 * @return Response
	 */
	public function run()
	{
		if (!$this->strFile)
		{
			exit('No file given');
		}

		// Make sure there are no attempts to hack the file system
		if (preg_match('@^\.+@', $this->strFile) || preg_match('@\.+/@', $this->strFile) || preg_match('@(://)+@', $this->strFile))
		{
			exit('Invalid file name');
		}

		$container = System::getContainer();

		// Limit preview to the files directory
		if (!preg_match('@^' . preg_quote($container->getParameter('contao.upload_path'), '@') . '@i', $this->strFile))
		{
			exit('Invalid path');
		}

		$projectDir = $container->getParameter('kernel.project_dir');

		// Check whether the file exists
		if (!file_exists($projectDir . '/' . $this->strFile))
		{
			exit('File not found');
		}

		// Check whether the file is mounted (thanks to Marko Cupic)
		if (!BackendUser::getInstance()->hasAccess($this->strFile, 'filemounts'))
		{
			exit('Permission denied');
		}

		// Open the download dialogue
		if (Input::get('download'))
		{
			$objFile = new File($this->strFile);
			$objFile->sendToBrowser();
		}

		$objTemplate = new BackendTemplate('be_popup');

		// Add the resource (see #6880)
		if (($objModel = FilesModel::findByPath($this->strFile)) === null && Dbafs::shouldBeSynchronized($this->strFile))
		{
			$objModel = Dbafs::addResource($this->strFile);
		}

		if ($objModel !== null)
		{
			$objTemplate->uuid = StringUtil::binToUuid($objModel->uuid); // see #5211
		}

		// Add the file info
		if (is_dir($projectDir . '/' . $this->strFile))
		{
			$objFile = new Folder($this->strFile);
			$objTemplate->filesize = $this->getReadableSize($objFile->size) . ' (' . number_format($objFile->size, 0, $GLOBALS['TL_LANG']['MSC']['decimalSeparator'], $GLOBALS['TL_LANG']['MSC']['thousandsSeparator']) . ' Byte)';
		}
		else
		{
			$objFile = new File($this->strFile);

			// Image
			if ($objFile->isImage)
			{
				$objTemplate->isImage = true;
				$objTemplate->width = $objFile->width;
				$objTemplate->height = $objFile->height;
				$objTemplate->src = $this->urlEncode($this->strFile);
				$objTemplate->dataUri = $objFile->dataUri;

				try
				{
					$readerWriter = System::getContainer()->get('contao.image.metadata');
					$objTemplate->metadata = $readerWriter->toReadable($readerWriter->parse($projectDir . '/' . $this->strFile));
				}
				catch (\Throwable)
				{
					// Ignore
				}
			}
			else
			{
				$objTemplate->hasPreview = true;

				try
				{
					$pictureSize = (new PictureConfiguration())
						->setSize(
							(new PictureConfigurationItem())
								->setResizeConfig((new ResizeConfiguration())->setWidth(864))
								->setDensities('1x, 2x')
						)
					;

					$picture = $container->get('contao.image.preview_factory')->createPreviewPicture($projectDir . '/' . $this->strFile, $pictureSize);
					$staticUrl = $container->get('contao.assets.files_context')->getStaticUrl();

					$objTemplate->previewPictures = array(
						array(
							'img' => $picture->getImg($projectDir, $staticUrl),
							'sources' => $picture->getSources($projectDir, $staticUrl),
						)
					);
				}
				catch (UnableToGeneratePreviewException|MissingPreviewProviderException $exception)
				{
					$objTemplate->hasPreview = false;
				}
			}

			// Metadata
			if ($objModel = FilesModel::findByPath($this->strFile))
			{
				$arrMeta = StringUtil::deserialize($objModel->meta);

				if (\is_array($arrMeta))
				{
					$objTemplate->meta = $arrMeta;
					$objTemplate->languages = $container->get('contao.intl.locales')->getLocales();
				}
			}

			$objTemplate->href = StringUtil::ampersand(Environment::get('requestUri')) . '&amp;download=1';
			$objTemplate->filesize = $this->getReadableSize($objFile->filesize) . ' (' . number_format($objFile->filesize, 0, $GLOBALS['TL_LANG']['MSC']['decimalSeparator'], $GLOBALS['TL_LANG']['MSC']['thousandsSeparator']) . ' Byte)';
		}

		$objTemplate->icon = $objFile->icon;
		$objTemplate->mime = $objFile->mime;
		$objTemplate->ctime = Date::parse(Config::get('datimFormat'), $objFile->ctime);
		$objTemplate->mtime = Date::parse(Config::get('datimFormat'), $objFile->mtime);
		$objTemplate->atime = Date::parse(Config::get('datimFormat'), $objFile->atime);
		$objTemplate->path = StringUtil::specialchars($this->strFile);
		$objTemplate->theme = Backend::getTheme();
		$objTemplate->language = $GLOBALS['TL_LANGUAGE'];
		$objTemplate->title = StringUtil::specialchars($this->strFile);
		$objTemplate->host = Backend::getDecodedHostname();
		$objTemplate->charset = $container->getParameter('kernel.charset');
		$objTemplate->labels = (object) $GLOBALS['TL_LANG']['MSC'];
		$objTemplate->download = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['fileDownload']);

		return $objTemplate->getResponse();
	}
}
