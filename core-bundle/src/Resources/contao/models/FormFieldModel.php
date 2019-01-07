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
 * @property integer $id
 * @property integer $pid
 * @property integer $sorting
 * @property integer $tstamp
 * @property boolean $invisible
 * @property string  $type
 * @property string  $name
 * @property string  $label
 * @property string  $text
 * @property string  $html
 * @property string  $options
 * @property boolean $mandatory
 * @property string  $rgxp
 * @property string  $placeholder
 * @property integer $minlength
 * @property integer $maxlength
 * @property string  $size
 * @property boolean $multiple
 * @property integer $mSize
 * @property string  $extensions
 * @property boolean $storeFile
 * @property string  $uploadFolder
 * @property boolean $useHomeDir
 * @property boolean $doNotOverwrite
 * @property string  $fsType
 * @property string  $class
 * @property string  $value
 * @property boolean $accesskey
 * @property integer $tabindex
 * @property integer $fSize
 * @property string  $customTpl
 * @property string  $slabel
 * @property boolean $imageSubmit
 * @property string  $singleSRC
 *
 * @method static FormFieldModel|null findById($id, array $opt=array())
 * @method static FormFieldModel|null findByPk($id, array $opt=array())
 * @method static FormFieldModel|null findByIdOrAlias($val, array $opt=array())
 * @method static FormFieldModel|null findOneBy($col, $val, array $opt=array())
 * @method static FormFieldModel|null findOneByPid($val, array $opt=array())
 * @method static FormFieldModel|null findOneBySorting($val, array $opt=array())
 * @method static FormFieldModel|null findOneByTstamp($val, array $opt=array())
 * @method static FormFieldModel|null findOneByInvisible($val, array $opt=array())
 * @method static FormFieldModel|null findOneByType($val, array $opt=array())
 * @method static FormFieldModel|null findOneByName($val, array $opt=array())
 * @method static FormFieldModel|null findOneByLabel($val, array $opt=array())
 * @method static FormFieldModel|null findOneByText($val, array $opt=array())
 * @method static FormFieldModel|null findOneByHtml($val, array $opt=array())
 * @method static FormFieldModel|null findOneByOptions($val, array $opt=array())
 * @method static FormFieldModel|null findOneByMandatory($val, array $opt=array())
 * @method static FormFieldModel|null findOneByRgxp($val, array $opt=array())
 * @method static FormFieldModel|null findOneByPlaceholder($val, array $opt=array())
 * @method static FormFieldModel|null findOneByMinlength($val, array $opt=array())
 * @method static FormFieldModel|null findOneByMaxlength($val, array $opt=array())
 * @method static FormFieldModel|null findOneBySize($val, array $opt=array())
 * @method static FormFieldModel|null findOneByMultiple($val, array $opt=array())
 * @method static FormFieldModel|null findOneByMSize($val, array $opt=array())
 * @method static FormFieldModel|null findOneByExtensions($val, array $opt=array())
 * @method static FormFieldModel|null findOneByStoreFile($val, array $opt=array())
 * @method static FormFieldModel|null findOneByUploadFolder($val, array $opt=array())
 * @method static FormFieldModel|null findOneByUseHomeDir($val, array $opt=array())
 * @method static FormFieldModel|null findOneByDoNotOverwrite($val, array $opt=array())
 * @method static FormFieldModel|null findOneByFsType($val, array $opt=array())
 * @method static FormFieldModel|null findOneByClass($val, array $opt=array())
 * @method static FormFieldModel|null findOneByValue($val, array $opt=array())
 * @method static FormFieldModel|null findOneByAccesskey($val, array $opt=array())
 * @method static FormFieldModel|null findOneByTabindex($val, array $opt=array())
 * @method static FormFieldModel|null findOneByFSize($val, array $opt=array())
 * @method static FormFieldModel|null findOneByCustomTpl($val, array $opt=array())
 * @method static FormFieldModel|null findOneByAddSubmit($val, array $opt=array())
 * @method static FormFieldModel|null findOneBySlabel($val, array $opt=array())
 * @method static FormFieldModel|null findOneByImageSubmit($val, array $opt=array())
 * @method static FormFieldModel|null findOneBySingleSRC($val, array $opt=array())
 *
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByPid($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findBySorting($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByTstamp($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByInvisible($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByType($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByName($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByLabel($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByText($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByHtml($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByOptions($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByMandatory($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByRgxp($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByPlaceholder($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByMinlength($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByMaxlength($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findBySize($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByMultiple($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByMSize($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByExtensions($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByStoreFile($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByUploadFolder($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByUseHomeDir($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByDoNotOverwrite($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByFsType($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByClass($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByValue($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByAccesskey($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByTabindex($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByFSize($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByCustomTpl($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByAddSubmit($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findBySlabel($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findByImageSubmit($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findBySingleSRC($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findMultipleByIds($val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findBy($col, $val, array $opt=array())
 * @method static Collection|FormFieldModel[]|FormFieldModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByPid($val, array $opt=array())
 * @method static integer countBySorting($val, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByInvisible($val, array $opt=array())
 * @method static integer countByType($val, array $opt=array())
 * @method static integer countByName($val, array $opt=array())
 * @method static integer countByLabel($val, array $opt=array())
 * @method static integer countByText($val, array $opt=array())
 * @method static integer countByHtml($val, array $opt=array())
 * @method static integer countByOptions($val, array $opt=array())
 * @method static integer countByMandatory($val, array $opt=array())
 * @method static integer countByRgxp($val, array $opt=array())
 * @method static integer countByPlaceholder($val, array $opt=array())
 * @method static integer countByMinlength($val, array $opt=array())
 * @method static integer countByMaxlength($val, array $opt=array())
 * @method static integer countBySize($val, array $opt=array())
 * @method static integer countByMultiple($val, array $opt=array())
 * @method static integer countByMSize($val, array $opt=array())
 * @method static integer countByExtensions($val, array $opt=array())
 * @method static integer countByStoreFile($val, array $opt=array())
 * @method static integer countByUploadFolder($val, array $opt=array())
 * @method static integer countByUseHomeDir($val, array $opt=array())
 * @method static integer countByDoNotOverwrite($val, array $opt=array())
 * @method static integer countByFsType($val, array $opt=array())
 * @method static integer countByClass($val, array $opt=array())
 * @method static integer countByValue($val, array $opt=array())
 * @method static integer countByAccesskey($val, array $opt=array())
 * @method static integer countByTabindex($val, array $opt=array())
 * @method static integer countByFSize($val, array $opt=array())
 * @method static integer countByCustomTpl($val, array $opt=array())
 * @method static integer countByAddSubmit($val, array $opt=array())
 * @method static integer countBySlabel($val, array $opt=array())
 * @method static integer countByImageSubmit($val, array $opt=array())
 * @method static integer countBySingleSRC($val, array $opt=array())
 *
 * @author Leo Feyer <https://github.com/leofeyer>
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
	 * @return Collection|FormFieldModel[]|FormFieldModel|null A collection of models or null if there are no form fields
	 */
	public static function findPublishedByPid($intPid, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.pid=?");

		if (!static::isPreviewMode($arrOptions))
		{
			$arrColumns[] = "$t.invisible=''";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.sorting";
		}

		return static::findBy($arrColumns, $intPid, $arrOptions);
	}
}

class_alias(FormFieldModel::class, 'FormFieldModel');
