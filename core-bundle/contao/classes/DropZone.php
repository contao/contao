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
 * Provide methods to handle file uploads in the back end.
 */
class DropZone extends FileUpload
{
	/**
	 * Generate the markup for the DropZone uploader
	 *
	 * @return string
	 */
	public function generateMarkup()
	{
		return System::getContainer()->get('twig')->render('@Contao/backend/component/_upload.html.twig', array(
			'name' => $this->strName,
			// Maximum file size in MB
			'maxSize' => round(static::getMaxUploadSize() / 1024 / 1024),
			// String of accepted file extensions
			'acceptedFiles' => implode(',', array_map(static function ($a) { return '.' . $a; }, StringUtil::trimsplit(',', strtolower(Config::get('uploadTypes'))))),
			'maxUploadSize' => System::getReadableSize(static::getMaxUploadSize()),
			'imgWidth' => Config::get('imageWidth'),
			'imgHeight' => Config::get('imageHeight'),
		));
	}
}
