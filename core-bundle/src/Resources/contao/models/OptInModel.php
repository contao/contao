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
use Contao\Model\Registry;

/**
 * Reads and writes double opt-in tokens
 *
 * @property string|integer $id
 * @property string|integer $tstamp
 * @property string         $token
 * @property string|integer $createdOn
 * @property string|integer $confirmedOn
 * @property string|integer $removeOn
 * @property string         $invalidatedThrough
 * @property string         $email
 * @property string         $emailSubject
 * @property string|null    $emailText
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
 * @method static OptInModel|null findOneByInvalidatedThrough($val, array $opt=array())
 * @method static OptInModel|null findOneByEmail($val, array $opt=array())
 * @method static OptInModel|null findOneByEmailSubject($val, array $opt=array())
 * @method static OptInModel|null findOneByEmailText($val, array $opt=array())
 *
 * @method static Collection|OptInModel[]|OptInModel|null findByTstamp($val, array $opt=array())
 * @method static Collection|OptInModel[]|OptInModel|null findByToken($val, array $opt=array())
 * @method static Collection|OptInModel[]|OptInModel|null findByCreatedOn($val, array $opt=array())
 * @method static Collection|OptInModel[]|OptInModel|null findByConfirmedOn($val, array $opt=array())
 * @method static Collection|OptInModel[]|OptInModel|null findByRemoveOn($val, array $opt=array())
 * @method static Collection|OptInModel[]|OptInModel|null findByInvalidatedThrough($val, array $opt=array())
 * @method static Collection|OptInModel[]|OptInModel|null findByEmail($val, array $opt=array())
 * @method static Collection|OptInModel[]|OptInModel|null findByEmailSubject($val, array $opt=array())
 * @method static Collection|OptInModel[]|OptInModel|null findByEmailText($val, array $opt=array())
 * @method static Collection|OptInModel[]|OptInModel|null findMultipleByIds($val, array $opt=array())
 * @method static Collection|OptInModel[]|OptInModel|null findBy($col, $val, array $opt=array())
 * @method static Collection|OptInModel[]|OptInModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByToken($val, array $opt=array())
 * @method static integer countByCreatedOn($val, array $opt=array())
 * @method static integer countByConfirmedOn($val, array $opt=array())
 * @method static integer countByRemoveOn($val, array $opt=array())
 * @method static integer countByInvalidatedThrough($val, array $opt=array())
 * @method static integer countByEmail($val, array $opt=array())
 * @method static integer countByEmailSubject($val, array $opt=array())
 * @method static integer countByEmailText($val, array $opt=array())
 */
class OptInModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_opt_in';

	/**
	 * Find expired double opt-in tokens
	 *
	 * @param array $arrOptions
	 *
	 * @return Collection|OptInModel[]|OptInModel|null
	 */
	public static function findExpiredTokens(array $arrOptions=array())
	{
		$t = static::$strTable;

		return static::findBy(array("$t.removeOn>0 AND $t.removeOn<?"), time(), $arrOptions);
	}

	/**
	 * Find an opt-in token by its related table and ID
	 *
	 * @param string  $strTable
	 * @param integer $intId
	 * @param array   $arrOptions
	 *
	 * @return static|null
	 *
	 * @deprecated Deprecated since Contao 4.7, to be removed in Contao 5.0; use the
	 *             Contao\OptInModel::findByRelatedTableAndIds() method instead
	 */
	public static function findOneByRelatedTableAndId($strTable, $intId, array $arrOptions=array())
	{
		trigger_deprecation('contao/core-bundle', '4.7', 'Using "Contao\OptInModel::findOneByRelatedTableAndIds()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\OptInModel::findByRelatedTableAndIds()" instead.');

		$t = static::$strTable;
		$objDatabase = Database::getInstance();

		$objResult = $objDatabase->prepare("SELECT * FROM $t WHERE id IN (SELECT pid FROM tl_opt_in_related WHERE relTable=? AND relId=?)")
								 ->execute($strTable, $intId);

		if ($objResult->numRows < 1)
		{
			return null;
		}

		$objRegistry = Registry::getInstance();

		/** @var OptInModel|Model $objOptIn */
		if ($objOptIn = $objRegistry->fetch($t, $objResult->id))
		{
			return $objOptIn;
		}

		return new static($objResult);
	}

	/**
	 * Find opt-in tokens by their related table and ID
	 *
	 * @param string $strTable
	 * @param array  $arrIds
	 * @param array  $arrOptions
	 *
	 * @return Collection|OptInModel[]|OptInModel|null
	 */
	public static function findByRelatedTableAndIds($strTable, array $arrIds, array $arrOptions=array())
	{
		$t = static::$strTable;
		$objDatabase = Database::getInstance();

		$objResult = $objDatabase->prepare("SELECT * FROM $t WHERE $t.id IN (SELECT pid FROM tl_opt_in_related WHERE relTable=? AND relId IN(" . implode(',', array_map('\intval', $arrIds)) . ")) ORDER BY $t.createdOn DESC")
								 ->execute($strTable, $arrIds);

		if ($objResult->numRows < 1)
		{
			return null;
		}

		$arrModels = array();
		$objRegistry = Registry::getInstance();

		while ($objResult->next())
		{
			/** @var OptInModel|Model $objOptIn */
			if ($objOptIn = $objRegistry->fetch($t, $objResult->id))
			{
				$arrModels[] = $objOptIn;
			}
			else
			{
				$arrModels[] = new static($objResult->row());
			}
		}

		return static::createCollection($arrModels, $t);
	}

	/**
	 * Delete the related records if the model is deleted
	 *
	 * @return integer
	 */
	public function delete()
	{
		Database::getInstance()->prepare("DELETE FROM tl_opt_in_related WHERE pid=?")
								->execute($this->id);

		return parent::delete();
	}

	/**
	 * Returns the related records
	 *
	 * @return array
	 */
	public function getRelatedRecords()
	{
		$arrRelated = array();
		$objDatabase = Database::getInstance();

		$objRelated = $objDatabase->prepare("SELECT * FROM tl_opt_in_related WHERE pid=?")
								  ->execute($this->id);

		while ($objRelated->next())
		{
			$arrRelated[$objRelated->relTable][] = $objRelated->relId;
		}

		return $arrRelated;
	}

	/**
	 * Set the related records
	 *
	 * @param array $arrRelated
	 *
	 * @throws \LogicException
	 */
	public function setRelatedRecords(array $arrRelated)
	{
		$objDatabase = Database::getInstance();

		$objCount = $objDatabase->prepare("SELECT COUNT(*) AS count FROM tl_opt_in_related WHERE pid=?")
								->execute($this->id);

		if ($objCount->count > 0)
		{
			throw new \LogicException(sprintf('Token "%s" already contains related records', $this->token));
		}

		foreach ($arrRelated as $strTable=>$arrIds)
		{
			foreach ($arrIds as $intId)
			{
				$objDatabase->prepare("INSERT INTO tl_opt_in_related (pid, relTable, relId) VALUES (?, ?, ?)")
							->execute($this->id, $strTable, $intId);
			}
		}
	}
}
