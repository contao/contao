<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\Model\Collection;

/**
 * Reads and writes form fields
 *
 * @property integer           $id
 * @property integer           $pid
 * @property integer           $sorting
 * @property integer           $tstamp
 * @property string            $type
 * @property string            $label
 * @property string            $name
 * @property string|null       $text
 * @property string|null       $html
 * @property string|array|null $options
 * @property boolean           $mandatory
 * @property string            $rgxp
 * @property string            $placeholder
 * @property string            $customRgxp
 * @property string            $errorMsg
 * @property integer           $minlength
 * @property integer           $maxlength
 * @property integer           $minval
 * @property integer           $maxval
 * @property integer           $step
 * @property string|array      $size
 * @property boolean           $multiple
 * @property integer           $mSize
 * @property string            $extensions
 * @property boolean           $storeFile
 * @property string|null       $uploadFolder
 * @property boolean           $useHomeDir
 * @property boolean           $doNotOverwrite
 * @property string            $class
 * @property string            $value
 * @property boolean           $accesskey
 * @property integer           $fSize
 * @property string            $customTpl
 * @property string            $slabel
 * @property boolean           $imageSubmit
 * @property string|null       $singleSRC
 * @property boolean           $invisible
 *
 * @method static FormFieldModel|null findById($id, array $opt=array())
 * @method static FormFieldModel|null findByPk($id, array $opt=array())
 * @method static FormFieldModel|null findByIdOrAlias($val, array $opt=array())
 * @method static FormFieldModel|null findOneBy($col, $val, array $opt=array())
 * @method static FormFieldModel|null findOneByPid($val, array $opt=array())
 * @method static FormFieldModel|null findOneBySorting($val, array $opt=array())
 * @method static FormFieldModel|null findOneByTstamp($val, array $opt=array())
 * @method static FormFieldModel|null findOneByType($val, array $opt=array())
 * @method static FormFieldModel|null findOneByLabel($val, array $opt=array())
 * @method static FormFieldModel|null findOneByName($val, array $opt=array())
 * @method static FormFieldModel|null findOneByText($val, array $opt=array())
 * @method static FormFieldModel|null findOneByHtml($val, array $opt=array())
 * @method static FormFieldModel|null findOneByOptions($val, array $opt=array())
 * @method static FormFieldModel|null findOneByMandatory($val, array $opt=array())
 * @method static FormFieldModel|null findOneByRgxp($val, array $opt=array())
 * @method static FormFieldModel|null findOneByPlaceholder($val, array $opt=array())
 * @method static FormFieldModel|null findOneByCustomRgxp($val, array $opt=array())
 * @method static FormFieldModel|null findOneByErrorMsg($val, array $opt=array())
 * @method static FormFieldModel|null findOneByMinlength($val, array $opt=array())
 * @method static FormFieldModel|null findOneByMaxlength($val, array $opt=array())
 * @method static FormFieldModel|null findOneByMinval($val, array $opt=array())
 * @method static FormFieldModel|null findOneByMaxval($val, array $opt=array())
 * @method static FormFieldModel|null findOneByStep($val, array $opt=array())
 * @method static FormFieldModel|null findOneBySize($val, array $opt=array())
 * @method static FormFieldModel|null findOneByMultiple($val, array $opt=array())
 * @method static FormFieldModel|null findOneByMSize($val, array $opt=array())
 * @method static FormFieldModel|null findOneByExtensions($val, array $opt=array())
 * @method static FormFieldModel|null findOneByStoreFile($val, array $opt=array())
 * @method static FormFieldModel|null findOneByUploadFolder($val, array $opt=array())
 * @method static FormFieldModel|null findOneByUseHomeDir($val, array $opt=array())
 * @method static FormFieldModel|null findOneByDoNotOverwrite($val, array $opt=array())
 * @method static FormFieldModel|null findOneByClass($val, array $opt=array())
 * @method static FormFieldModel|null findOneByValue($val, array $opt=array())
 * @method static FormFieldModel|null findOneByAccesskey($val, array $opt=array())
 * @method static FormFieldModel|null findOneByFSize($val, array $opt=array())
 * @method static FormFieldModel|null findOneByCustomTpl($val, array $opt=array())
 * @method static FormFieldModel|null findOneByAddSubmit($val, array $opt=array())
 * @method static FormFieldModel|null findOneBySlabel($val, array $opt=array())
 * @method static FormFieldModel|null findOneByImageSubmit($val, array $opt=array())
 * @method static FormFieldModel|null findOneBySingleSRC($val, array $opt=array())
 * @method static FormFieldModel|null findOneByInvisible($val, array $opt=array())
 *
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByPid($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findBySorting($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByTstamp($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByType($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByLabel($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByName($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByText($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByHtml($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByOptions($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByMandatory($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByRgxp($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByPlaceholder($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByCustomRgxp($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByErrorMsg($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByMinlength($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByMaxlength($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByMinval($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByMaxval($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByStep($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findBySize($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByMultiple($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByMSize($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByExtensions($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByStoreFile($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByUploadFolder($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByUseHomeDir($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByDoNotOverwrite($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByClass($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByValue($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByAccesskey($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByFSize($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByCustomTpl($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByAddSubmit($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findBySlabel($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByImageSubmit($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findBySingleSRC($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findMultipleByIds($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findByInvisible($val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findBy($col, $val, array $opt=array())
 * @method static Collection<FormFieldModel>|FormFieldModel[]|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByPid($val, array $opt=array())
 * @method static integer countBySorting($val, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByType($val, array $opt=array())
 * @method static integer countByLabel($val, array $opt=array())
 * @method static integer countByName($val, array $opt=array())
 * @method static integer countByText($val, array $opt=array())
 * @method static integer countByHtml($val, array $opt=array())
 * @method static integer countByOptions($val, array $opt=array())
 * @method static integer countByMandatory($val, array $opt=array())
 * @method static integer countByRgxp($val, array $opt=array())
 * @method static integer countByPlaceholder($val, array $opt=array())
 * @method static integer countByCustomRgxp($val, array $opt=array())
 * @method static integer countByErrorMsg($val, array $opt=array())
 * @method static integer countByMinlength($val, array $opt=array())
 * @method static integer countByMaxlength($val, array $opt=array())
 * @method static integer countByMinval($val, array $opt=array())
 * @method static integer countByMaxval($val, array $opt=array())
 * @method static integer countByStep($val, array $opt=array())
 * @method static integer countBySize($val, array $opt=array())
 * @method static integer countByMultiple($val, array $opt=array())
 * @method static integer countByMSize($val, array $opt=array())
 * @method static integer countByExtensions($val, array $opt=array())
 * @method static integer countByStoreFile($val, array $opt=array())
 * @method static integer countByUploadFolder($val, array $opt=array())
 * @method static integer countByUseHomeDir($val, array $opt=array())
 * @method static integer countByDoNotOverwrite($val, array $opt=array())
 * @method static integer countByClass($val, array $opt=array())
 * @method static integer countByValue($val, array $opt=array())
 * @method static integer countByAccesskey($val, array $opt=array())
 * @method static integer countByFSize($val, array $opt=array())
 * @method static integer countByCustomTpl($val, array $opt=array())
 * @method static integer countByAddSubmit($val, array $opt=array())
 * @method static integer countBySlabel($val, array $opt=array())
 * @method static integer countByImageSubmit($val, array $opt=array())
 * @method static integer countBySingleSRC($val, array $opt=array())
 * @method static integer countByInvisible($val, array $opt=array())
 */
class FormFieldModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_form_field';

	/**
	 * Find published form fields by their parent ID
	 *
	 * @param integer $intPid     The form ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return Collection<FormFieldModel>|FormFieldModel[]|null A collection of models or null if there are no form fields
	 */
	public static function findPublishedByPid($intPid, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.pid=?");

		if (!static::isPreviewMode($arrOptions))
		{
			$arrColumns[] = "$t.invisible=0";
		}

		// Skip unsaved elements (see #2708)
		$arrColumns[] = "$t.tstamp!=0";

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.sorting";
		}

		return static::findBy($arrColumns, array($intPid), $arrOptions);
	}
}
