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
 * Reads and writes newsletter denylist entries
 *
 * @property integer $id
 * @property integer $pid
 * @property string  $hash
 *
 * @method static NewsletterDenylistModel|null findById($id, array $opt=array())
 * @method static NewsletterDenylistModel|null findByPk($id, array $opt=array())
 * @method static NewsletterDenylistModel|null findOneBy($col, $val, array $opt=array())
 * @method static NewsletterDenylistModel|null findOneByPid($val, array $opt=array())
 * @method static NewsletterDenylistModel|null findOneByHash($val, array $opt=array())
 *
 * @method static Collection|NewsletterDenylistModel|null findByPid($val, array $opt=array())
 * @method static Collection|NewsletterDenylistModel|null findByHash($val, array $opt=array())
 * @method static Collection|NewsletterDenylistModel|null findMultipleByIds($val, array $opt=array())
 * @method static Collection|NewsletterDenylistModel|null findBy($col, $val, array $opt=array())
 * @method static Collection|NewsletterDenylistModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByPid($val, array $opt=array())
 * @method static integer countByHash($val, array $opt=array())
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class NewsletterDenylistModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_newsletter_denylist';

	/**
	 * Find a denylist entry by its hash and PID.
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
