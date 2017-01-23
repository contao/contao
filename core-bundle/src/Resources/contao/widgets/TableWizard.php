<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;


/**
 * Provide methods to handle table fields.
 *
 * @property integer $rows
 * @property integer $cols
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class TableWizard extends \Widget
{

	/**
	 * Submit user input
	 * @var boolean
	 */
	protected $blnSubmitInput = true;

	/**
	 * Rows
	 * @var integer
	 */
	protected $intRows = 12;

	/**
	 * Columns
	 * @var integer
	 */
	protected $intCols = 80;

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'be_widget';


	/**
	 * Add specific attributes
	 *
	 * @param string $strKey
	 * @param mixed  $varValue
	 */
	public function __set($strKey, $varValue)
	{
		switch ($strKey)
		{
			case 'rows':
				$this->intRows = $varValue;
				break;

			case 'cols':
				$this->intCols = $varValue;
				break;

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}


	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		$arrColButtons = array('ccopy', 'cmovel', 'cmover', 'cdelete');
		$arrRowButtons = array('rcopy', 'rdelete', 'rdrag');

		// Make sure there is at least an empty array
		if (!is_array($this->varValue) || empty($this->varValue))
		{
			$this->varValue = array(array(''));
		}

		// Begin the table
		$return = '<div id="tl_tablewizard">
  <table id="ctrl_'.$this->strId.'" class="tl_tablewizard">
  <thead>
    <tr>';

		// Add column buttons
		for ($i=0, $c=count($this->varValue[0]); $i<$c; $i++)
		{
			$return .= '
      <td>';

			// Add column buttons
			foreach ($arrColButtons as $button)
			{
				$return .= ' <button type="button" data-command="' . $button . '" class="tl_tablewizard_img" title="' . \StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['tw_'.$button]) . '">' . \Image::getHtml(substr($button, 1).'.svg') . '</button>';
			}

			$return .= '</td>';
		}

		$return .= '
      <td></td>
    </tr>
  </thead>
  <tbody class="sortable">';

		// Add rows
		for ($i=0, $c=count($this->varValue); $i<$c; $i++)
		{
			$return .= '
    <tr>';

			// Add cells
			for ($j=0, $d=count($this->varValue[$i]); $j<$d; $j++)
			{
				$return .= '
      <td class="tcontainer"><textarea name="'.$this->strId.'['.$i.']['.$j.']" class="tl_textarea noresize" rows="'.$this->intRows.'" cols="'.$this->intCols.'"'.$this->getAttributes().'>'.\StringUtil::specialchars($this->varValue[$i][$j]).'</textarea></td>';
			}

			$return .= '
      <td>';

			// Add row buttons
			foreach ($arrRowButtons as $button)
			{
				if ($button == 'rdrag')
				{
					$return .= ' <button type="button" class="drag-handle" title="' . sprintf($GLOBALS['TL_LANG']['MSC']['move']) . '">' . \Image::getHtml('drag.svg') . '</button>';
				}
				else
				{
					$return .= ' <button type="button" data-command="' . $button . '" class="tl_tablewizard_img" title="' . \StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['tw_'.$button]) . '">' . \Image::getHtml(substr($button, 1).'.svg') . '</button>';
				}
			}

			$return .= '</td>
    </tr>';
		}

		$return .= '
  </tbody>
  </table>
  </div>
  <script>Backend.tableWizard("ctrl_'.$this->strId.'")</script>';

		return $return;
	}


	/**
	 * Return a form to choose a CSV file and import it
	 *
	 * @param DataContainer $dc
	 *
	 * @return string
	 */
	public function importTable(DataContainer $dc)
	{
		if (\Input::get('key') != 'table')
		{
			return '';
		}

		/** @var FileUpload $objUploader */
		$objUploader = new \FileUpload();

		// Import CSS
		if (\Input::post('FORM_SUBMIT') == 'tl_table_import')
		{
			$arrUploaded = $objUploader->uploadTo('system/tmp');

			if (empty($arrUploaded))
			{
				\Message::addError($GLOBALS['TL_LANG']['ERR']['all_fields']);
				$this->reload();
			}

			$this->import('Database');
			$arrTable = array();

			foreach ($arrUploaded as $strCsvFile)
			{
				$objFile = new \File($strCsvFile);

				if ($objFile->extension != 'csv')
				{
					\Message::addError(sprintf($GLOBALS['TL_LANG']['ERR']['filetype'], $objFile->extension));
					continue;
				}

				// Get separator
				switch (\Input::post('separator'))
				{
					case 'semicolon':
						$strSeparator = ';';
						break;

					case 'tabulator':
						$strSeparator = "\t";
						break;

					default:
						$strSeparator = ',';
						break;
				}

				$resFile = $objFile->handle;

				while(($arrRow = @fgetcsv($resFile, null, $strSeparator)) !== false)
				{
					$arrTable[] = $arrRow;
				}
			}

			$objVersions = new \Versions($dc->table, \Input::get('id'));
			$objVersions->create();

			$this->Database->prepare("UPDATE " . $dc->table . " SET tableitems=? WHERE id=?")
						   ->execute(serialize($arrTable), \Input::get('id'));

			\System::setCookie('BE_PAGE_OFFSET', 0, 0);
			$this->redirect(str_replace('&key=table', '', \Environment::get('request')));
		}

		// Return form
		return '
<div id="tl_buttons">
<a href="'.ampersand(str_replace('&key=table', '', \Environment::get('request'))).'" class="header_back" title="'.\StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a>
</div>
'.\Message::generate().'
<form action="'.ampersand(\Environment::get('request'), true).'" id="tl_table_import" class="tl_form" method="post" enctype="multipart/form-data">
<div class="tl_formbody_edit">
<input type="hidden" name="FORM_SUBMIT" value="tl_table_import">
<input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">

<fieldset class="tl_tbox nolegend">
<div>
  <h3><label for="separator">'.$GLOBALS['TL_LANG']['MSC']['separator'][0].'</label></h3>
  <select name="separator" id="separator" class="tl_select" onfocus="Backend.getScrollOffset()">
    <option value="comma">'.$GLOBALS['TL_LANG']['MSC']['comma'].'</option>
    <option value="semicolon">'.$GLOBALS['TL_LANG']['MSC']['semicolon'].'</option>
    <option value="tabulator">'.$GLOBALS['TL_LANG']['MSC']['tabulator'].'</option>
  </select>'.(($GLOBALS['TL_LANG']['MSC']['separator'][1] != '') ? '
  <p class="tl_help tl_tip">'.$GLOBALS['TL_LANG']['MSC']['separator'][1].'</p>' : '').'
  <h3>'.$GLOBALS['TL_LANG']['MSC']['source'][0].'</h3>'.$objUploader->generateMarkup().(isset($GLOBALS['TL_LANG']['MSC']['source'][1]) ? '
  <p class="tl_help tl_tip">'.$GLOBALS['TL_LANG']['MSC']['source'][1].'</p>' : '').'
</div>
</fieldset>

</div>

<div class="tl_formbody_submit">

<div class="tl_submit_container">
  <button type="submit" name="save" id="save" class="tl_submit" accesskey="s">'.$GLOBALS['TL_LANG']['MSC']['tw_import'][0].'</button>
</div>

</div>
</form>';
	}
}
