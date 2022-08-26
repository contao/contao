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
 * Reads and writes newsletter deny list entries
 *
 * @property string|integer $id
 * @property string|integer $pid
 * @property string|null    $hash
 *
 * @method static NewsletterDenyListModel|null findById($id, array $opt=array())
 * @method static NewsletterDenyListModel|null findByPk($id, array $opt=array())
 * @method static NewsletterDenyListModel|null findOneBy($col, $val, array $opt=array())
 * @method static NewsletterDenyListModel|null findOneByPid($val, array $opt=array())
 * @method static NewsletterDenyListModel|null findOneByHash($val, array $opt=array())
 *
 * @method static Collection|NewsletterDenyListModel|null findByPid($val, array $opt=array())
 * @method static Collection|NewsletterDenyListModel|null findByHash($val, array $opt=array())
 * @method static Collection|NewsletterDenyListModel|null findMultipleByIds($val, array $opt=array())
 * @method static Collection|NewsletterDenyListModel|null findBy($col, $val, array $opt=array())
 * @method static Collection|NewsletterDenyListModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByPid($val, array $opt=array())
 * @method static integer countByHash($val, array $opt=array())
 */
class NewsletterDenyListModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_newsletter_deny_list';

	/**
	 * Find a deny list entry by its hash and PID.
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
