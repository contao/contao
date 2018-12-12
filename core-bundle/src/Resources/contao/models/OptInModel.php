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
 * Reads and writes double opt-in tokens
 *
 * @property integer $id
 * @property integer $tstamp
 * @property string  $token
 * @property integer $createdOn
 * @property integer $confirmedOn
 * @property integer $removeOn
 * @property string  $relatedTable
 * @property integer $relatedId
 * @property string  $email
 * @property string  $emailSubject
 * @property string  $emailText
 *
 * @method static OptInModel|null findById($id, array $opt=array())
 * @method static OptInModel|null findByPk($id, array $opt=array())
 * @method static OptInModel|null findByIdOrAlias($val, array $opt=array())
 * @method static OptInModel|null findOneBy($col, $val, array $opt=array())
 * @method static OptInModel|null findOneByTstamp($val, array $opt=array())
 * @method static OptInModel|null findOneByToken($val, array $opt=array())
 * @method static OptInModel|null findOneByCreatedOn($val, array $opt=array())
 * @method static OptInModel|null findOneByConfirmedOn($val, array $opt=array())
 * @method static OptInModel|null findOneByRemoveOn($val, array $opt=array())
 * @method static OptInModel|null findOneByRelatedTable($val, array $opt=array())
 * @method static OptInModel|null findOneByRelatedId($val, array $opt=array())
 * @method static OptInModel|null findOneByEmail($val, array $opt=array())
 * @method static OptInModel|null findOneByEmailSubject($val, array $opt=array())
 * @method static OptInModel|null findOneByEmailText($val, array $opt=array())
 *
 * @method static Model\Collection|OptInModel[]|OptInModel|null findByTstamp($val, array $opt=array())
 * @method static Model\Collection|OptInModel[]|OptInModel|null findByToken($val, array $opt=array())
 * @method static Model\Collection|OptInModel[]|OptInModel|null findByCreatedOn($val, array $opt=array())
 * @method static Model\Collection|OptInModel[]|OptInModel|null findByConfirmedOn($val, array $opt=array())
 * @method static Model\Collection|OptInModel[]|OptInModel|null findByRemoveOn($val, array $opt=array())
 * @method static Model\Collection|OptInModel[]|OptInModel|null findByRelatedTable($val, array $opt=array())
 * @method static Model\Collection|OptInModel[]|OptInModel|null findByRelatedId($val, array $opt=array())
 * @method static Model\Collection|OptInModel[]|OptInModel|null findByEmail($val, array $opt=array())
 * @method static Model\Collection|OptInModel[]|OptInModel|null findByEmailSubject($val, array $opt=array())
 * @method static Model\Collection|OptInModel[]|OptInModel|null findByEmailText($val, array $opt=array())
 * @method static Model\Collection|OptInModel[]|OptInModel|null findMultipleByIds($val, array $opt=array())
 * @method static Model\Collection|OptInModel[]|OptInModel|null findBy($col, $val, array $opt=array())
 * @method static Model\Collection|OptInModel[]|OptInModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByToken($val, array $opt=array())
 * @method static integer countByCreatedOn($val, array $opt=array())
 * @method static integer countByConfirmedOn($val, array $opt=array())
 * @method static integer countByRemoveOn($val, array $opt=array())
 * @method static integer countByRelatedTable($val, array $opt=array())
 * @method static integer countByRelatedId($val, array $opt=array())
 * @method static integer countByEmail($val, array $opt=array())
 * @method static integer countByEmailSubject($val, array $opt=array())
 * @method static integer countByEmailText($val, array $opt=array())
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class OptInModel extends Model
{

	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_opt_in';

	/**
	 * Find double opt-in tokens that have a "removeOn" date or have not been activated for more than 24 hours
	 *
	 * @param array $arrOptions An optional options array
	 *
	 * @return Model\Collection|OptInModel[]|OptInModel|null A collection of models or null if there are no expired tokens
	 */
	public static function findExpiredTokens(array $arrOptions=array())
	{
		$t = static::$strTable;

		return static::findBy(array("($t.removeOn>0 AND $t.removeOn<?) OR ($t.confirmedOn=0 AND $t.createdOn<?)"), array(strtotime('-3 years'), strtotime('-1 day')), $arrOptions);
	}
}

class_alias(OptInModel::class, 'OptInModel');
