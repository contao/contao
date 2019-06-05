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
 * Reads and writes newsletter channels
 *
 * @property integer $id
 * @property integer $pid
 * @property string  $hash
 *
 * @method static NewsletterBlacklistModel|null findById($id, array $opt=array())
 * @method static NewsletterBlacklistModel|null findByPk($id, array $opt=array())
 * @method static NewsletterBlacklistModel|null findOneBy($col, $val, array $opt=array())
 * @method static NewsletterBlacklistModel|null findOneByPid($val, array $opt=array())
 * @method static NewsletterBlacklistModel|null findOneByHash($val, array $opt=array())
 *
 * @method static Collection|NewsletterBlacklistModel|null findByPid($val, array $opt=array())
 * @method static Collection|NewsletterBlacklistModel|null findByHash($val, array $opt=array())
 * @method static Collection|NewsletterBlacklistModel|null findMultipleByIds($val, array $opt=array())
 * @method static Collection|NewsletterBlacklistModel|null findBy($col, $val, array $opt=array())
 * @method static Collection|NewsletterBlacklistModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByPid($val, array $opt=array())
 * @method static integer countByHash($val, array $opt=array())
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class NewsletterBlacklistModel extends Model
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

class_alias(NewsletterBlacklistModel::class, 'NewsletterBlacklistModel');
