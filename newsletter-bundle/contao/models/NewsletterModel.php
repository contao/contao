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
 * Reads and writes newsletters
 *
 * @property integer     $id
 * @property integer     $pid
 * @property integer     $tstamp
 * @property string      $subject
 * @property string      $alias
 * @property string|null $content
 * @property string|null $text
 * @property boolean     $addFile
 * @property string|null $files
 * @property string      $template
 * @property boolean     $sendText
 * @property boolean     $externalImages
 * @property string      $mailerTransport
 * @property string      $sender
 * @property string      $senderName
 * @property boolean     $sent
 * @property integer     $date
 *
 * @method static NewsletterModel|null findById($id, array $opt=array())
 * @method static NewsletterModel|null findByPk($id, array $opt=array())
 * @method static NewsletterModel|null findByIdOrAlias($val, array $opt=array())
 * @method static NewsletterModel|null findOneBy($col, $val, array $opt=array())
 * @method static NewsletterModel|null findOneByPid($val, array $opt=array())
 * @method static NewsletterModel|null findOneByTstamp($val, array $opt=array())
 * @method static NewsletterModel|null findOneBySubject($val, array $opt=array())
 * @method static NewsletterModel|null findOneByAlias($val, array $opt=array())
 * @method static NewsletterModel|null findOneByContent($val, array $opt=array())
 * @method static NewsletterModel|null findOneByText($val, array $opt=array())
 * @method static NewsletterModel|null findOneByAddFile($val, array $opt=array())
 * @method static NewsletterModel|null findOneByFiles($val, array $opt=array())
 * @method static NewsletterModel|null findOneByTemplate($val, array $opt=array())
 * @method static NewsletterModel|null findOneBySendText($val, array $opt=array())
 * @method static NewsletterModel|null findOneByExternalImages($val, array $opt=array())
 * @method static NewsletterModel|null findOneByMailerTransport($val, array $opt=array())
 * @method static NewsletterModel|null findOneBySender($val, array $opt=array())
 * @method static NewsletterModel|null findOneBySenderName($val, array $opt=array())
 * @method static NewsletterModel|null findOneBySent($val, array $opt=array())
 * @method static NewsletterModel|null findOneByDate($val, array $opt=array())
 *
 * @method static Collection|NewsletterModel[]|NewsletterModel|null findByPid($val, array $opt=array())
 * @method static Collection|NewsletterModel[]|NewsletterModel|null findByTstamp($val, array $opt=array())
 * @method static Collection|NewsletterModel[]|NewsletterModel|null findBySubject($val, array $opt=array())
 * @method static Collection|NewsletterModel[]|NewsletterModel|null findByAlias($val, array $opt=array())
 * @method static Collection|NewsletterModel[]|NewsletterModel|null findByContent($val, array $opt=array())
 * @method static Collection|NewsletterModel[]|NewsletterModel|null findByText($val, array $opt=array())
 * @method static Collection|NewsletterModel[]|NewsletterModel|null findByAddFile($val, array $opt=array())
 * @method static Collection|NewsletterModel[]|NewsletterModel|null findByFiles($val, array $opt=array())
 * @method static Collection|NewsletterModel[]|NewsletterModel|null findByTemplate($val, array $opt=array())
 * @method static Collection|NewsletterModel[]|NewsletterModel|null findBySendText($val, array $opt=array())
 * @method static Collection|NewsletterModel[]|NewsletterModel|null findByExternalImages($val, array $opt=array())
 * @method static Collection|NewsletterModel[]|NewsletterModel|null findByMailerTransport($val, array $opt=array())
 * @method static Collection|NewsletterModel[]|NewsletterModel|null findBySender($val, array $opt=array())
 * @method static Collection|NewsletterModel[]|NewsletterModel|null findBySenderName($val, array $opt=array())
 * @method static Collection|NewsletterModel[]|NewsletterModel|null findBySent($val, array $opt=array())
 * @method static Collection|NewsletterModel[]|NewsletterModel|null findByDate($val, array $opt=array())
 * @method static Collection|NewsletterModel[]|NewsletterModel|null findMultipleByIds($val, array $opt=array())
 * @method static Collection|NewsletterModel[]|NewsletterModel|null findBy($col, $val, array $opt=array())
 * @method static Collection|NewsletterModel[]|NewsletterModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByPid($val, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countBySubject($val, array $opt=array())
 * @method static integer countByAlias($val, array $opt=array())
 * @method static integer countByContent($val, array $opt=array())
 * @method static integer countByText($val, array $opt=array())
 * @method static integer countByAddFile($val, array $opt=array())
 * @method static integer countByFiles($val, array $opt=array())
 * @method static integer countByTemplate($val, array $opt=array())
 * @method static integer countBySendText($val, array $opt=array())
 * @method static integer countByExternalImages($val, array $opt=array())
 * @method static integer countByMailerTransport($val, array $opt=array())
 * @method static integer countBySender($val, array $opt=array())
 * @method static integer countBySenderName($val, array $opt=array())
 * @method static integer countBySent($val, array $opt=array())
 * @method static integer countByDate($val, array $opt=array())
 */
class NewsletterModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_newsletter';

	/**
	 * Find a sent newsletter by its parent IDs and its ID or alias
	 *
	 * @param integer $varId      The numeric ID or alias name
	 * @param array   $arrPids    An array of newsletter channel IDs
	 * @param array   $arrOptions An optional options array
	 *
	 * @return NewsletterModel|null The model or null if there are no sent newsletters
	 */
	public static function findSentByParentAndIdOrAlias($varId, $arrPids, array $arrOptions=array())
	{
		if (empty($arrPids) || !\is_array($arrPids))
		{
			return null;
		}

		$t = static::$strTable;
		$arrColumns = !preg_match('/^[1-9]\d*$/', $varId) ? array("BINARY $t.alias=?") : array("$t.id=?");
		$arrColumns[] = "$t.pid IN(" . implode(',', array_map('\intval', $arrPids)) . ")";

		if (!static::isPreviewMode($arrOptions))
		{
			$arrColumns[] = "$t.sent=1";
		}

		return static::findOneBy($arrColumns, array($varId), $arrOptions);
	}

	/**
	 * Find sent newsletters by their parent ID
	 *
	 * @param integer $intPid     The newsletter channel ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return Collection|NewsletterModel[]|NewsletterModel|null A collection of models or null if there are no sent newsletters
	 */
	public static function findSentByPid($intPid, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.pid=?");

		if (!static::isPreviewMode($arrOptions))
		{
			$arrColumns[] = "$t.sent=1";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.date DESC";
		}

		return static::findBy($arrColumns, array($intPid), $arrOptions);
	}

	/**
	 * Find sent newsletters by multiple parent IDs
	 *
	 * @param array $arrPids    An array of newsletter channel IDs
	 * @param array $arrOptions An optional options array
	 *
	 * @return Collection|NewsletterModel[]|NewsletterModel|null A collection of models or null if there are no sent newsletters
	 */
	public static function findSentByPids($arrPids, array $arrOptions=array())
	{
		if (empty($arrPids) || !\is_array($arrPids))
		{
			return null;
		}

		$t = static::$strTable;
		$arrColumns = array("$t.pid IN(" . implode(',', array_map('\intval', $arrPids)) . ")");

		if (!static::isPreviewMode($arrOptions))
		{
			$arrColumns[] = "$t.sent=1";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.date DESC";
		}

		return static::findBy($arrColumns, null, $arrOptions);
	}
}
