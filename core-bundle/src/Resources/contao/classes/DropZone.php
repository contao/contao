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
		// Maximum file size in MB
		$intMaxSize = round(static::getMaxUploadSize() / 1024 / 1024);

		// String of accepted file extensions
		$strAccepted = implode(',', array_map(static function ($a) { return '.' . $a; }, StringUtil::trimsplit(',', strtolower(Config::get('uploadTypes')))));

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
        url: window.location.href,
        paramName: "' . $this->strName . '",
        maxFilesize: ' . $intMaxSize . ',
        acceptedFiles: "' . $strAccepted . '",
        timeout: 0,
        previewsContainer: ".dropzone-previews",
        clickable: ".dropzone",
        dictFileTooBig: ' . json_encode($GLOBALS['TL_LANG']['tl_files']['dropzoneFileTooBig']) . ',
        dictInvalidFileType: ' . json_encode($GLOBALS['TL_LANG']['tl_files']['dropzoneInvalidType']) . '
      }).on("addedfile", function() {
        $$(".dz-message").setStyle("display", "none");
      }).on("success", function(file, message) {
        if (!message) return;
        var container = $("tl_message");
        if (!container) {
          container = new Element("div", {
            "id": "tl_message",
            "class": "tl_message"
          }).inject($("tl_buttons"), "before");
        }
        container.appendHTML(message);
      });
      $$("div.tl_formbody_submit").setStyle("display", "none");
    });
  </script>';

		if (isset($GLOBALS['TL_LANG']['tl_files']['fileupload'][1]))
		{
			$return .= '
  <p class="tl_help tl_tip">' . sprintf($GLOBALS['TL_LANG']['tl_files']['fileupload'][1], System::getReadableSize(static::getMaxUploadSize()), Config::get('gdMaxImgWidth') . 'x' . Config::get('gdMaxImgHeight')) . '</p>';
		}

		return $return;
	}
}

class_alias(DropZone::class, 'DropZone');
