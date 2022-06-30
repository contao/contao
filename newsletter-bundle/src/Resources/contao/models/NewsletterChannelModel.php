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
 * @property integer $tstamp
 * @property string  $title
 * @property integer $jumpTo
 * @property string  $template
 * @property string  $mailerTransport
 * @property string  $sender
 * @property string  $senderName
 *
 * @method static NewsletterChannelModel|null findById($id, array $opt=array())
 * @method static NewsletterChannelModel|null findByPk($id, array $opt=array())
 * @method static NewsletterChannelModel|null findByIdOrAlias($val, array $opt=array())
 * @method static NewsletterChannelModel|null findOneBy($col, $val, array $opt=array())
 * @method static NewsletterChannelModel|null findOneByTstamp($val, array $opt=array())
 * @method static NewsletterChannelModel|null findOneByTitle($val, array $opt=array())
 * @method static NewsletterChannelModel|null findOneByJumpTo($val, array $opt=array())
 * @method static NewsletterChannelModel|null findOneByTemplate($val, array $opt=array())
 * @method static NewsletterChannelModel|null findOneByMailerTransport($val, array $opt=array())
 * @method static NewsletterChannelModel|null findOneBySender($val, array $opt=array())
 * @method static NewsletterChannelModel|null findOneBySenderName($val, array $opt=array())
 *
 * @method static Collection|NewsletterChannelModel[]|NewsletterChannelModel|null findByTstamp($val, array $opt=array())
 * @method static Collection|NewsletterChannelModel[]|NewsletterChannelModel|null findByTitle($val, array $opt=array())
 * @method static Collection|NewsletterChannelModel[]|NewsletterChannelModel|null findByJumpTo($val, array $opt=array())
 * @method static Collection|NewsletterChannelModel[]|NewsletterChannelModel|null findByTemplate($val, array $opt=array())
 * @method static Collection|NewsletterChannelModel[]|NewsletterChannelModel|null findByMailerTransport($val, array $opt=array())
 * @method static Collection|NewsletterChannelModel[]|NewsletterChannelModel|null findBySender($val, array $opt=array())
 * @method static Collection|NewsletterChannelModel[]|NewsletterChannelModel|null findBySenderName($val, array $opt=array())
 * @method static Collection|NewsletterChannelModel[]|NewsletterChannelModel|null findMultipleByIds($val, array $opt=array())
 * @method static Collection|NewsletterChannelModel[]|NewsletterChannelModel|null findBy($col, $val, array $opt=array())
 * @method static Collection|NewsletterChannelModel[]|NewsletterChannelModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByTitle($val, array $opt=array())
 * @method static integer countByJumpTo($val, array $opt=array())
 * @method static integer countByTemplate($val, array $opt=array())
 * @method static integer countByMailerTransport($val, array $opt=array())
 * @method static integer countBySender($val, array $opt=array())
 * @method static integer countBySenderName($val, array $opt=array())
 */
class NewsletterChannelModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_newsletter_channel';

	/**
	 * Find multiple newsletter channels by their IDs
	 *
	 * @param array $arrIds     An array of newsletter channel IDs
	 * @param array $arrOptions An optional options array
	 *
	 * @return Collection|NewsletterChannelModel[]|NewsletterChannelModel|null A collection of models or null if there are no newsletter channels
	 */
	public static function findByIds($arrIds, array $arrOptions=array())
	{
		if (empty($arrIds) || !\is_array($arrIds))
		{
			return null;
		}

		$t = static::$strTable;

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.title";
		}

		return static::findBy(array("$t.id IN(" . implode(',', array_map('\intval', $arrIds)) . ")"), null, $arrOptions);
	}
}
