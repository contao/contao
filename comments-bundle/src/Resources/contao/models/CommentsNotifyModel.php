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
 * Reads and writes comments subscriptions
 *
 * @property integer $id
 * @property integer $tstamp
 * @property string  $source
 * @property integer $parent
 * @property string  $name
 * @property string  $email
 * @property string  $url
 * @property string  $addedOn
 * @property boolean $confirmed
 * @property string  $tokenRemove
 *
 * @method static CommentsNotifyModel|null findById($id, array $opt=array())
 * @method static CommentsNotifyModel|null findByPk($id, array $opt=array())
 * @method static CommentsNotifyModel|null findByIdOrAlias($val, array $opt=array())
 * @method static CommentsNotifyModel|null findOneBy($col, $val, array $opt=array())
 * @method static CommentsNotifyModel|null findOneByTstamp($val, array $opt=array())
 * @method static CommentsNotifyModel|null findOneBySource($val, array $opt=array())
 * @method static CommentsNotifyModel|null findOneByParent($val, array $opt=array())
 * @method static CommentsNotifyModel|null findOneByName($val, array $opt=array())
 * @method static CommentsNotifyModel|null findOneByEmail($val, array $opt=array())
 * @method static CommentsNotifyModel|null findOneByUrl($val, array $opt=array())
 * @method static CommentsNotifyModel|null findOneByAddedOn($val, array $opt=array())
 * @method static CommentsNotifyModel|null findOneByConfirmed($val, array $opt=array())
 * @method static CommentsNotifyModel|null findOneByTokenRemove($val, array $opt=array())
 *
 * @method static Model\Collection|CommentsNotifyModel[]|CommentsNotifyModel|null findByTstamp($val, array $opt=array())
 * @method static Model\Collection|CommentsNotifyModel[]|CommentsNotifyModel|null findBySource($val, array $opt=array())
 * @method static Model\Collection|CommentsNotifyModel[]|CommentsNotifyModel|null findByParent($val, array $opt=array())
 * @method static Model\Collection|CommentsNotifyModel[]|CommentsNotifyModel|null findByName($val, array $opt=array())
 * @method static Model\Collection|CommentsNotifyModel[]|CommentsNotifyModel|null findByEmail($val, array $opt=array())
 * @method static Model\Collection|CommentsNotifyModel[]|CommentsNotifyModel|null findByUrl($val, array $opt=array())
 * @method static Model\Collection|CommentsNotifyModel[]|CommentsNotifyModel|null findByAddedOn($val, array $opt=array())
 * @method static Model\Collection|CommentsNotifyModel[]|CommentsNotifyModel|null findByConfirmed($val, array $opt=array())
 * @method static Model\Collection|CommentsNotifyModel[]|CommentsNotifyModel|null findByTokenRemove($val, array $opt=array())
 * @method static Model\Collection|CommentsNotifyModel[]|CommentsNotifyModel|null findMultipleByIds($val, array $opt=array())
 * @method static Model\Collection|CommentsNotifyModel[]|CommentsNotifyModel|null findBy($col, $val, array $opt=array())
 * @method static Model\Collection|CommentsNotifyModel[]|CommentsNotifyModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countBySource($val, array $opt=array())
 * @method static integer countByParent($val, array $opt=array())
 * @method static integer countByName($val, array $opt=array())
 * @method static integer countByEmail($val, array $opt=array())
 * @method static integer countByUrl($val, array $opt=array())
 * @method static integer countByAddedOn($val, array $opt=array())
 * @method static integer countByConfirmed($val, array $opt=array())
 * @method static integer countByTokenRemove($val, array $opt=array())
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class CommentsNotifyModel extends Model
{

	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_comments_notify';

	/**
	 * Find a subscription by its source table, parent ID and e-mail address
	 *
	 * @param string  $strSource  The source element
	 * @param integer $intParent  The parent ID
	 * @param string  $strEmail   The e-mail address
	 * @param array   $arrOptions An optional options array
	 *
	 * @return CommentsNotifyModel|null The model or null if there are no subscriptions
	 */
	public static function findBySourceParentAndEmail($strSource, $intParent, $strEmail, array $arrOptions=array())
	{
		$t = static::$strTable;

		return static::findOneBy(array("$t.source=? AND $t.parent=? AND $t.email=?"), array($strSource, $intParent, $strEmail), $arrOptions);
	}

	/**
	 * Find active subscriptions by their source table and parent ID
	 *
	 * @param string  $strSource  The source element
	 * @param integer $intParent  The parent ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return Model\Collection|CommentsNotifyModel[]|CommentsNotifyModel|null A collection of models or null if there are no active subscriptions
	 */
	public static function findActiveBySourceAndParent($strSource, $intParent, array $arrOptions=array())
	{
		$t = static::$strTable;

		return static::findBy(array("$t.source=? AND $t.parent=? AND $t.confirmed='1'"), array($strSource, $intParent), $arrOptions);
	}

	/**
	 * Find subscriptions that have not been activated for more than 24 hours
	 *
	 * @param array $arrOptions An optional options array
	 *
	 * @return Model\Collection|CommentsNotifyModel[]|CommentsNotifyModel|null A collection of models or null if there are no expired subscriptions
	 */
	public static function findExpiredSubscriptions(array $arrOptions=array())
	{
		$t = static::$strTable;
		$time = time();
		$objDatabase = \Database::getInstance();

		$objResult = $objDatabase->query("SELECT * FROM $t WHERE confirmed='' AND EXISTS (SELECT * FROM tl_opt_in WHERE relatedTable='$t' AND relatedId=$t.id AND confirmedOn=0 AND validUntil<=$time)");

		if ($objResult->numRows < 1)
		{
			return null;
		}

		return static::createCollectionFromDbResult($objResult, $t);
	}
}

class_alias(CommentsNotifyModel::class, 'CommentsNotifyModel');
