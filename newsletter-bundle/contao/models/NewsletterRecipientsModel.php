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
 * Reads and writes newsletter recipients
 *
 * @property integer $id
 * @property integer $pid
 * @property integer $tstamp
 * @property string  $email
 * @property boolean $active
 * @property integer $addedOn
 *
 * @method static NewsletterRecipientsModel|null findById($id, array $opt=array())
 * @method static NewsletterRecipientsModel|null findByPk($id, array $opt=array())
 * @method static NewsletterRecipientsModel|null findByIdOrAlias($val, array $opt=array())
 * @method static NewsletterRecipientsModel|null findOneBy($col, $val, array $opt=array())
 * @method static NewsletterRecipientsModel|null findOneByPid($val, array $opt=array())
 * @method static NewsletterRecipientsModel|null findOneByTstamp($val, array $opt=array())
 * @method static NewsletterRecipientsModel|null findOneByEmail($val, array $opt=array())
 * @method static NewsletterRecipientsModel|null findOneByActive($val, array $opt=array())
 * @method static NewsletterRecipientsModel|null findOneByAddedOn($val, array $opt=array())
 *
 * @method static Collection<NewsletterRecipientsModel>|NewsletterRecipientsModel[]|null findByPid($val, array $opt=array())
 * @method static Collection<NewsletterRecipientsModel>|NewsletterRecipientsModel[]|null findByTstamp($val, array $opt=array())
 * @method static Collection<NewsletterRecipientsModel>|NewsletterRecipientsModel[]|null findByEmail($val, array $opt=array())
 * @method static Collection<NewsletterRecipientsModel>|NewsletterRecipientsModel[]|null findByActive($val, array $opt=array())
 * @method static Collection<NewsletterRecipientsModel>|NewsletterRecipientsModel[]|null findByAddedOn($val, array $opt=array())
 * @method static Collection<NewsletterRecipientsModel>|NewsletterRecipientsModel[]|null findMultipleByIds($val, array $opt=array())
 * @method static Collection<NewsletterRecipientsModel>|NewsletterRecipientsModel[]|null findBy($col, $val, array $opt=array())
 * @method static Collection<NewsletterRecipientsModel>|NewsletterRecipientsModel[]|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByPid($val, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByEmail($val, array $opt=array())
 * @method static integer countByActive($val, array $opt=array())
 * @method static integer countByAddedOn($val, array $opt=array())
 */
class NewsletterRecipientsModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_newsletter_recipients';

	/**
	 * Find recipients by their e-mail address and parent ID
	 *
	 * @param string $strEmail   The e-mail address
	 * @param array  $arrPids    An array of newsletter channel IDs
	 * @param array  $arrOptions An optional options array
	 *
	 * @return Collection<NewsletterRecipientsModel>|NewsletterRecipientsModel[]|null A collection of models or null if there are no recipients
	 */
	public static function findByEmailAndPids($strEmail, $arrPids, array $arrOptions=array())
	{
		if (empty($arrPids) || !\is_array($arrPids))
		{
			return null;
		}

		$t = static::$strTable;

		return static::findBy(array("$t.email=? AND $t.pid IN(" . implode(',', array_map('\intval', $arrPids)) . ")"), array($strEmail), $arrOptions);
	}

	/**
	 * Find old subscriptions by e-mail address and channels
	 *
	 * @param string $strEmail   The e-mail address
	 * @param array  $arrPids    An array of newsletter channel IDs
	 * @param array  $arrOptions An optional options array
	 *
	 * @return Collection<NewsletterRecipientsModel>|NewsletterRecipientsModel[]|null A collection of models or null if there are no old subscriptions
	 */
	public static function findOldSubscriptionsByEmailAndPids($strEmail, $arrPids, array $arrOptions=array())
	{
		if (empty($arrPids) || !\is_array($arrPids))
		{
			return null;
		}

		$t = static::$strTable;

		return static::findBy(array("$t.email=? AND $t.pid IN(" . implode(',', array_map('\intval', $arrPids)) . ") AND $t.active=0"), array($strEmail), $arrOptions);
	}

	/**
	 * Find subscriptions that have not been activated for more than 24 hours
	 *
	 * @param array $arrOptions An optional options array
	 *
	 * @return Collection<NewsletterRecipientsModel>|NewsletterRecipientsModel[]|null A collection of models or null if there are no expired subscriptions
	 */
	public static function findExpiredSubscriptions(array $arrOptions=array())
	{
		$t = static::$strTable;
		$objDatabase = Database::getInstance();

		$objResult = $objDatabase->prepare("SELECT * FROM $t WHERE active=0 AND EXISTS (SELECT * FROM tl_opt_in_related r LEFT JOIN tl_opt_in o ON r.pid=o.id WHERE r.relTable='$t' AND r.relId=$t.id AND o.createdOn<=? AND o.confirmedOn=0 AND o.token LIKE 'nl-%')")
								 ->execute(strtotime('-24 hours'));

		if ($objResult->numRows < 1)
		{
			return null;
		}

		return static::createCollectionFromDbResult($objResult, $t);
	}
}
