<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;


/**
 * Reads and writes newsletter channels
 *
 * @property integer $id
 * @property integer $pid
 * @property string  $hash
 *
 * @method static NewsletterChannelModel|null findById($id, $opt=array())
 * @method static NewsletterChannelModel|null findByPk($id, $opt=array())
 * @method static NewsletterChannelModel|null findOneBy($col, $val, $opt=array())
 * @method static NewsletterChannelModel|null findOneByPid($val, $opt=array())
 * @method static NewsletterChannelModel|null findOneByHash($val, $opt=array())
 *
 * @method static Model\Collection|NewsletterChannelModel|null findByPid($val, $opt=array())
 * @method static Model\Collection|NewsletterChannelModel|null findByHash($val, $opt=array())
 * @method static Model\Collection|NewsletterChannelModel|null findMultipleByIds($val, $opt=array())
 * @method static Model\Collection|NewsletterChannelModel|null findBy($col, $val, $opt=array())
 * @method static Model\Collection|NewsletterChannelModel|null findAll($opt=array())
 *
 * @method static integer countById($id, $opt=array())
 * @method static integer countByPid($val, $opt=array())
 * @method static integer countByHash($val, $opt=array())
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class NewsletterBlacklistModel extends \Model
{

	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_newsletter_blacklist';

	/**
	 * Find a blacklist entry by its hash and PID.
	 *
	 * @param string  $strHash    The hash
	 * @param integer $intPid     The page ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return static The model or null if there is no article
	 */
	public static function findByHashAndPid($strHash, $intPid, array $arrOptions=array())
	{
		$t = static::$strTable;

		return static::findOneBy(array("($t.hash=? AND $t.pid=?)"), array($strHash, $intPid), $arrOptions);
	}
}
