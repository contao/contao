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
 * Class Upload
 *
 * Provide methods to use the FileUpload class in a back end widget. The widget
 * will only upload the files to the server. Use a submit_callback to process
 * the files or use the class as base for your own upload widget.
 */
class Upload extends Widget implements UploadableWidgetInterface
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

	/**
	 * Uploader
	 * @var FileUpload
	 */
	protected $objUploader;

	/**
	 * Initialize the FileUpload object
	 *
	 * @param array $arrAttributes
	 */
	public function __construct($arrAttributes=null)
	{
		parent::__construct($arrAttributes);

		$this->objUploader = new FileUpload();
		$this->objUploader->setName($this->strName);
	}

	/**
	 * Trim values
	 *
	 * @param mixed $varInput
	 *
	 * @return mixed
	 */
	protected function validator($varInput)
	{
		// Specify the target folder in the DCA (eval)
		$strUploadTo = $this->arrConfiguration['uploadFolder'] ?? 'system/tmp';

		return $this->objUploader->uploadTo($strUploadTo);
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		return ltrim($this->objUploader->generateMarkup());
	}
}

class_alias(Upload::class, 'Upload');
