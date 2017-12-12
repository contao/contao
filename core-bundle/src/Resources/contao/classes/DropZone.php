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
 * Provide methods to handle file uploads in the back end.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class DropZone extends \FileUpload
{

	/**
	 * Generate the markup for the DropZone uploader
	 *
	 * @return string
	 */
	public function generateMarkup()
	{
		// Maximum file size in MB
		$intMaxSize = round($this->getMaximumUploadSize() / 1024 / 1024);

		// String of accepted file extensions
		$strAccepted = implode(',', array_map(function($a) { return '.' . $a; }, \StringUtil::trimsplit(',', strtolower(\Config::get('uploadTypes')))));

		// Add the scripts
		$GLOBALS['TL_CSS'][] = 'assets/dropzone/css/dropzone.min.css';
		$GLOBALS['TL_JAVASCRIPT'][] = 'assets/dropzone/js/dropzone.min.js';

		// Generate the markup
		$return = '
  <input type="hidden" name="action" value="fileupload">
  <div class="fallback">
    <input type="file" name="' . $this->strName . '[]" class="tl_upload_field" onfocus="Backend.getScrollOffset()" multiple>
  </div>
  <div class="dropzone">
    <div class="dz-default dz-message">
      <span>' . $GLOBALS['TL_LANG']['tl_files']['dropzone'] . '</span>
    </div>
    <span class="dropzone-previews"></span>
  </div>
  <script>
    Dropzone.autoDiscover = false;
    window.addEvent("domready", function() {
      new Dropzone("#tl_files", {
        paramName: "' . $this->strName . '",
        maxFilesize: ' . $intMaxSize . ',
        acceptedFiles: "' . $strAccepted . '",
        previewsContainer: ".dropzone-previews",
        clickable: ".dropzone"
      }).on("addedfile", function() {
        $$(".dz-message").setStyle("display", "none");
      });
      $$("div.tl_formbody_submit").setStyle("display", "none");
    });
  </script>';

		if (isset($GLOBALS['TL_LANG']['tl_files']['fileupload'][1]))
		{
			$return .= '
  <p class="tl_help tl_tip">' . sprintf($GLOBALS['TL_LANG']['tl_files']['fileupload'][1], \System::getReadableSize($this->getMaximumUploadSize()), \Config::get('gdMaxImgWidth') . 'x' . \Config::get('gdMaxImgHeight')) . '</p>';
		}

		return $return;
	}
}
