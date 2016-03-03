<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;


/**
 * Reads and writes newsletter channels
 *
 * @property integer $id
 * @property integer $tstamp
 * @property string  $title
 * @property integer $jumpTo
 * @property string  $template
 * @property string  $sender
 * @property string  $senderName
 *
 * @method static NewsletterChannelModel|null findById($id, $opt=array())
 * @method static NewsletterChannelModel|null findByPk($id, $opt=array())
 * @method static NewsletterChannelModel|null findByIdOrAlias($val, $opt=array())
 * @method static NewsletterChannelModel|null findOneBy($col, $val, $opt=array())
 * @method static NewsletterChannelModel|null findOneByTstamp($val, $opt=array())
 * @method static NewsletterChannelModel|null findOneByTitle($val, $opt=array())
 * @method static NewsletterChannelModel|null findOneByJumpTo($val, $opt=array())
 * @method static NewsletterChannelModel|null findOneByTemplate($val, $opt=array())
 * @method static NewsletterChannelModel|null findOneBySender($val, $opt=array())
 * @method static NewsletterChannelModel|null findOneBySenderName($val, $opt=array())
 *
 * @method static Model\Collection|NewsletterChannelModel[]|NewsletterChannelModel|null findByTstamp($val, $opt=array())
 * @method static Model\Collection|NewsletterChannelModel[]|NewsletterChannelModel|null findByTitle($val, $opt=array())
 * @method static Model\Collection|NewsletterChannelModel[]|NewsletterChannelModel|null findByJumpTo($val, $opt=array())
 * @method static Model\Collection|NewsletterChannelModel[]|NewsletterChannelModel|null findByTemplate($val, $opt=array())
 * @method static Model\Collection|NewsletterChannelModel[]|NewsletterChannelModel|null findBySender($val, $opt=array())
 * @method static Model\Collection|NewsletterChannelModel[]|NewsletterChannelModel|null findBySenderName($val, $opt=array())
 * @method static Model\Collection|NewsletterChannelModel[]|NewsletterChannelModel|null findMultipleByIds($val, $opt=array())
 * @method static Model\Collection|NewsletterChannelModel[]|NewsletterChannelModel|null findBy($col, $val, $opt=array())
 * @method static Model\Collection|NewsletterChannelModel[]|NewsletterChannelModel|null findAll($opt=array())
 *
 * @method static integer countById($id, $opt=array())
 * @method static integer countByTstamp($val, $opt=array())
 * @method static integer countByTitle($val, $opt=array())
 * @method static integer countByJumpTo($val, $opt=array())
 * @method static integer countByTemplate($val, $opt=array())
 * @method static integer countBySender($val, $opt=array())
 * @method static integer countBySenderName($val, $opt=array())
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class NewsletterChannelModel extends \Model
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
	 * @return Model\Collection|NewsletterChannelModel[]|NewsletterChannelModel|null A collection of models or null if there are no newsletter channels
	 */
	public static function findByIds($arrIds, array $arrOptions=array())
	{
		if (!is_array($arrIds) || empty($arrIds))
		{
			return null;
		}

		$t = static::$strTable;

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.title";
		}

		return static::findBy(array("$t.id IN(" . implode(',', array_map('intval', $arrIds)) . ")"), null, $arrOptions);
	}
}
