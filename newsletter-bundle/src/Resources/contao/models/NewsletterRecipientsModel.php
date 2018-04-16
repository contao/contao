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
 * Reads and writes newsletter recipients
 *
 * @property integer $id
 * @property integer $pid
 * @property integer $tstamp
 * @property string  $email
 * @property boolean $active
 * @property string  $source
 * @property string  $addedOn
 * @property string  $confirmed
 * @property string  $ip
 * @property string  $token
 *
 * @method static NewsletterRecipientsModel|null findById($id, array $opt=array())
 * @method static NewsletterRecipientsModel|null findByPk($id, array $opt=array())
 * @method static NewsletterRecipientsModel|null findByIdOrAlias($val, array $opt=array())
 * @method static NewsletterRecipientsModel|null findOneBy($col, $val, array $opt=array())
 * @method static NewsletterRecipientsModel|null findOneByPid($val, array $opt=array())
 * @method static NewsletterRecipientsModel|null findOneByTstamp($val, array $opt=array())
 * @method static NewsletterRecipientsModel|null findOneByEmail($val, array $opt=array())
 * @method static NewsletterRecipientsModel|null findOneByActive($val, array $opt=array())
 * @method static NewsletterRecipientsModel|null findOneBySource($val, array $opt=array())
 * @method static NewsletterRecipientsModel|null findOneByAddedOn($val, array $opt=array())
 * @method static NewsletterRecipientsModel|null findOneByConfirmed($val, array $opt=array())
 * @method static NewsletterRecipientsModel|null findOneByIp($val, array $opt=array())
 * @method static NewsletterRecipientsModel|null findOneByToken($val, array $opt=array())
 *
 * @method static Model\Collection|NewsletterRecipientsModel[]|NewsletterRecipientsModel|null findByPid($val, array $opt=array())
 * @method static Model\Collection|NewsletterRecipientsModel[]|NewsletterRecipientsModel|null findByTstamp($val, array $opt=array())
 * @method static Model\Collection|NewsletterRecipientsModel[]|NewsletterRecipientsModel|null findByEmail($val, array $opt=array())
 * @method static Model\Collection|NewsletterRecipientsModel[]|NewsletterRecipientsModel|null findByActive($val, array $opt=array())
 * @method static Model\Collection|NewsletterRecipientsModel[]|NewsletterRecipientsModel|null findBySource($val, array $opt=array())
 * @method static Model\Collection|NewsletterRecipientsModel[]|NewsletterRecipientsModel|null findByAddedOn($val, array $opt=array())
 * @method static Model\Collection|NewsletterRecipientsModel[]|NewsletterRecipientsModel|null findByConfirmed($val, array $opt=array())
 * @method static Model\Collection|NewsletterRecipientsModel[]|NewsletterRecipientsModel|null findByIp($val, array $opt=array())
 * @method static Model\Collection|NewsletterRecipientsModel[]|NewsletterRecipientsModel|null findByToken($val, array $opt=array())
 * @method static Model\Collection|NewsletterRecipientsModel[]|NewsletterRecipientsModel|null findMultipleByIds($val, array $opt=array())
 * @method static Model\Collection|NewsletterRecipientsModel[]|NewsletterRecipientsModel|null findBy($col, $val, array $opt=array())
 * @method static Model\Collection|NewsletterRecipientsModel[]|NewsletterRecipientsModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByPid($val, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByEmail($val, array $opt=array())
 * @method static integer countByActive($val, array $opt=array())
 * @method static integer countBySource($val, array $opt=array())
 * @method static integer countByAddedOn($val, array $opt=array())
 * @method static integer countByConfirmed($val, array $opt=array())
 * @method static integer countByIp($val, array $opt=array())
 * @method static integer countByToken($val, array $opt=array())
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class NewsletterRecipientsModel extends \Model
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
	 * @return Model\Collection|NewsletterRecipientsModel[]|NewsletterRecipientsModel|null A collection of models or null if there are no recipients
	 */
	public static function findByEmailAndPids($strEmail, $arrPids, array $arrOptions=array())
	{
		if (empty($arrPids) || !\is_array($arrPids))
		{
			return null;
		}

		$t = static::$strTable;

		return static::findBy(array("$t.email=? AND $t.pid IN(" . implode(',', array_map('\intval', $arrPids)) . ")"), $strEmail, $arrOptions);
	}

	/**
	 * Find old subscriptions by e-mail address and channels
	 *
	 * @param string $strEmail   The e-mail address
	 * @param array  $arrPids    An array of newsletter channel IDs
	 * @param array  $arrOptions An optional options array
	 *
	 * @return Model\Collection|NewsletterRecipientsModel[]|NewsletterRecipientsModel|null A collection of models or null if there are no subscriptions
	 */
	public static function findOldSubscriptionsByEmailAndPids($strEmail, $arrPids, array $arrOptions=array())
	{
		if (empty($arrPids) || !\is_array($arrPids))
		{
			return null;
		}

		$t = static::$strTable;

		return static::findBy(array("$t.email=? AND $t.pid IN(" . implode(',', array_map('\intval', $arrPids)) . ") AND $t.active=''"), $strEmail, $arrOptions);
	}
}
