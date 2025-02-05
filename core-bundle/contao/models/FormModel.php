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
 * Reads and writes forms
 *
 * @property integer $id
 * @property integer $tstamp
 * @property string  $title
 * @property string  $alias
 * @property integer $jumpTo
 * @property boolean $sendViaEmail
 * @property string  $mailerTransport
 * @property string  $recipient
 * @property string  $subject
 * @property string  $format
 * @property boolean $skipEmpty
 * @property boolean $storeValues
 * @property string  $targetTable
 * @property string  $customTpl
 * @property string  $method
 * @property boolean $novalidate
 * @property string  $attributes
 * @property string  $formID
 * @property boolean $allowTags
 *
 * @method static FormModel|null findById($id, array $opt=array())
 * @method static FormModel|null findByPk($id, array $opt=array())
 * @method static FormModel|null findByIdOrAlias($val, array $opt=array())
 * @method static FormModel|null findOneBy($col, $val, array $opt=array())
 * @method static FormModel|null findOneByTstamp($val, array $opt=array())
 * @method static FormModel|null findOneByTitle($val, array $opt=array())
 * @method static FormModel|null findOneByAlias($val, array $opt=array())
 * @method static FormModel|null findOneByJumpTo($val, array $opt=array())
 * @method static FormModel|null findOneBySendViaEmail($val, array $opt=array())
 * @method static FormModel|null findOneByMailerTransport($val, array $opt=array())
 * @method static FormModel|null findOneByRecipient($val, array $opt=array())
 * @method static FormModel|null findOneBySubject($val, array $opt=array())
 * @method static FormModel|null findOneByFormat($val, array $opt=array())
 * @method static FormModel|null findOneBySkipEmpty($val, array $opt=array())
 * @method static FormModel|null findOneByStoreValues($val, array $opt=array())
 * @method static FormModel|null findOneByTargetTable($val, array $opt=array())
 * @method static FormModel|null findOneByCustomTpl($val, array $opt=array())
 * @method static FormModel|null findOneByMethod($val, array $opt=array())
 * @method static FormModel|null findOneByNovalidate($val, array $opt=array())
 * @method static FormModel|null findOneByAttributes($val, array $opt=array())
 * @method static FormModel|null findOneByFormID($val, array $opt=array())
 * @method static FormModel|null findOneByTableless($val, array $opt=array())
 * @method static FormModel|null findOneByAllowTags($val, array $opt=array())
 *
 * @method static Collection<FormModel>|FormModel[]|null findByTstamp($val, array $opt=array())
 * @method static Collection<FormModel>|FormModel[]|null findByTitle($val, array $opt=array())
 * @method static Collection<FormModel>|FormModel[]|null findByAlias($val, array $opt=array())
 * @method static Collection<FormModel>|FormModel[]|null findByJumpTo($val, array $opt=array())
 * @method static Collection<FormModel>|FormModel[]|null findBySendViaEmail($val, array $opt=array())
 * @method static Collection<FormModel>|FormModel[]|null findByMailerTransport($val, array $opt=array())
 * @method static Collection<FormModel>|FormModel[]|null findByRecipient($val, array $opt=array())
 * @method static Collection<FormModel>|FormModel[]|null findBySubject($val, array $opt=array())
 * @method static Collection<FormModel>|FormModel[]|null findByFormat($val, array $opt=array())
 * @method static Collection<FormModel>|FormModel[]|null findBySkipEmpty($val, array $opt=array())
 * @method static Collection<FormModel>|FormModel[]|null findByStoreValues($val, array $opt=array())
 * @method static Collection<FormModel>|FormModel[]|null findByTargetTable($val, array $opt=array())
 * @method static Collection<FormModel>|FormModel[]|null findByCustomTpl($val, array $opt=array())
 * @method static Collection<FormModel>|FormModel[]|null findByMethod($val, array $opt=array())
 * @method static Collection<FormModel>|FormModel[]|null findByNovalidate($val, array $opt=array())
 * @method static Collection<FormModel>|FormModel[]|null findByAttributes($val, array $opt=array())
 * @method static Collection<FormModel>|FormModel[]|null findByFormID($val, array $opt=array())
 * @method static Collection<FormModel>|FormModel[]|null findByTableless($val, array $opt=array())
 * @method static Collection<FormModel>|FormModel[]|null findByAllowTags($val, array $opt=array())
 * @method static Collection<FormModel>|FormModel[]|null findMultipleByIds($val, array $opt=array())
 * @method static Collection<FormModel>|FormModel[]|null findBy($col, $val, array $opt=array())
 * @method static Collection<FormModel>|FormModel[]|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByTitle($val, array $opt=array())
 * @method static integer countByAlias($val, array $opt=array())
 * @method static integer countByJumpTo($val, array $opt=array())
 * @method static integer countBySendViaEmail($val, array $opt=array())
 * @method static integer countByMailerTransport($val, array $opt=array())
 * @method static integer countByRecipient($val, array $opt=array())
 * @method static integer countBySubject($val, array $opt=array())
 * @method static integer countByFormat($val, array $opt=array())
 * @method static integer countBySkipEmpty($val, array $opt=array())
 * @method static integer countByStoreValues($val, array $opt=array())
 * @method static integer countByTargetTable($val, array $opt=array())
 * @method static integer countByCustomTpl($val, array $opt=array())
 * @method static integer countByMethod($val, array $opt=array())
 * @method static integer countByNovalidate($val, array $opt=array())
 * @method static integer countByAttributes($val, array $opt=array())
 * @method static integer countByFormID($val, array $opt=array())
 * @method static integer countByTableless($val, array $opt=array())
 * @method static integer countByAllowTags($val, array $opt=array())
 */
class FormModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_form';

	/**
	 * Get the maximum file size that is allowed for file uploads
	 *
	 * @return integer The maximum file size in bytes
	 */
	public function getMaxUploadFileSize()
	{
		$objResult = Database::getInstance()->prepare("SELECT MAX(maxlength) AS maxlength FROM tl_form_field WHERE pid=? AND invisible=0 AND type='upload' AND maxlength>0")
											 ->execute($this->id);

		if ($objResult->numRows > 0 && $objResult->maxlength > 0)
		{
			return $objResult->maxlength;
		}

		return Config::get('maxFileSize');
	}
}
