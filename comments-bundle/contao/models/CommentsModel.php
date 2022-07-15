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
 * Reads and writes comments
 *
 * @property integer     $id
 * @property integer     $tstamp
 * @property string      $source
 * @property integer     $parent
 * @property string      $date
 * @property string      $name
 * @property string      $email
 * @property string      $website
 * @property integer     $member
 * @property string|null $comment
 * @property boolean     $addReply
 * @property integer     $author
 * @property string|null $reply
 * @property boolean     $published
 * @property string      $ip
 * @property boolean     $notified
 * @property boolean     $notifiedReply
 *
 * @method static CommentsModel|null findById($id, array $opt=array())
 * @method static CommentsModel|null findByPk($id, array $opt=array())
 * @method static CommentsModel|null findByIdOrAlias($val, array $opt=array())
 * @method static CommentsModel|null findOneBy($col, $val, array $opt=array())
 * @method static CommentsModel|null findOneByTstamp($val, array $opt=array())
 * @method static CommentsModel|null findOneBySource($val, array $opt=array())
 * @method static CommentsModel|null findOneByParent($val, array $opt=array())
 * @method static CommentsModel|null findOneByDate($val, array $opt=array())
 * @method static CommentsModel|null findOneByName($val, array $opt=array())
 * @method static CommentsModel|null findOneByEmail($val, array $opt=array())
 * @method static CommentsModel|null findOneByWebsite($val, array $opt=array())
 * @method static CommentsModel|null findOneByMember($val, array $opt=array())
 * @method static CommentsModel|null findOneByComment($val, array $opt=array())
 * @method static CommentsModel|null findOneByAddReply($val, array $opt=array())
 * @method static CommentsModel|null findOneByAuthor($val, array $opt=array())
 * @method static CommentsModel|null findOneByReply($val, array $opt=array())
 * @method static CommentsModel|null findOneByPublished($val, array $opt=array())
 * @method static CommentsModel|null findOneByIp($val, array $opt=array())
 * @method static CommentsModel|null findOneByNotified($val, array $opt=array())
 * @method static CommentsModel|null findOneByNotifiedReply($val, array $opt=array())
 *
 * @method static Collection|CommentsModel[]|CommentsModel|null findByTstamp($val, array $opt=array())
 * @method static Collection|CommentsModel[]|CommentsModel|null findBySource($val, array $opt=array())
 * @method static Collection|CommentsModel[]|CommentsModel|null findByParent($val, array $opt=array())
 * @method static Collection|CommentsModel[]|CommentsModel|null findByDate($val, array $opt=array())
 * @method static Collection|CommentsModel[]|CommentsModel|null findByName($val, array $opt=array())
 * @method static Collection|CommentsModel[]|CommentsModel|null findByEmail($val, array $opt=array())
 * @method static Collection|CommentsModel[]|CommentsModel|null findByWebsite($val, array $opt=array())
 * @method static Collection|CommentsModel[]|CommentsModel|null findByMember($val, array $opt=array())
 * @method static Collection|CommentsModel[]|CommentsModel|null findByComment($val, array $opt=array())
 * @method static Collection|CommentsModel[]|CommentsModel|null findByAddReply($val, array $opt=array())
 * @method static Collection|CommentsModel[]|CommentsModel|null findByAuthor($val, array $opt=array())
 * @method static Collection|CommentsModel[]|CommentsModel|null findByReply($val, array $opt=array())
 * @method static Collection|CommentsModel[]|CommentsModel|null findByPublished($val, array $opt=array())
 * @method static Collection|CommentsModel[]|CommentsModel|null findByIp($val, array $opt=array())
 * @method static Collection|CommentsModel[]|CommentsModel|null findByNotified($val, array $opt=array())
 * @method static Collection|CommentsModel[]|CommentsModel|null findByNotifiedReply($val, array $opt=array())
 * @method static Collection|CommentsModel[]|CommentsModel|null findMultipleByIds($val, array $opt=array())
 * @method static Collection|CommentsModel[]|CommentsModel|null findBy($col, $val, array $opt=array())
 * @method static Collection|CommentsModel[]|CommentsModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countBySource($val, array $opt=array())
 * @method static integer countByParent($val, array $opt=array())
 * @method static integer countByDate($val, array $opt=array())
 * @method static integer countByName($val, array $opt=array())
 * @method static integer countByEmail($val, array $opt=array())
 * @method static integer countByWebsite($val, array $opt=array())
 * @method static integer countByMember($val, array $opt=array())
 * @method static integer countByComment($val, array $opt=array())
 * @method static integer countByAddReply($val, array $opt=array())
 * @method static integer countByAuthor($val, array $opt=array())
 * @method static integer countByReply($val, array $opt=array())
 * @method static integer countByPublished($val, array $opt=array())
 * @method static integer countByIp($val, array $opt=array())
 * @method static integer countByNotified($val, array $opt=array())
 * @method static integer countByNotifiedReply($val, array $opt=array())
 */
class CommentsModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_comments';

	/**
	 * Find published comments by their source table and parent ID
	 *
	 * @param string  $strSource  The source element
	 * @param integer $intParent  The parent ID
	 * @param boolean $blnDesc    If true, comments will be sorted descending
	 * @param integer $intLimit   An optional limit
	 * @param integer $intOffset  An optional offset
	 * @param array   $arrOptions An optional options array
	 *
	 * @return Collection|CommentsModel[]|CommentsModel|null A collection of models or null if there are no comments
	 */
	public static function findPublishedBySourceAndParent($strSource, $intParent, $blnDesc=false, $intLimit=0, $intOffset=0, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.source=? AND $t.parent=?");

		if (!static::isPreviewMode($arrOptions))
		{
			$arrColumns[] = "$t.published=1";
		}

		$arrOptions['limit']  = $intLimit;
		$arrOptions['offset'] = $intOffset;

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order']  = ($blnDesc ? "$t.date DESC" : "$t.date");
		}

		return static::findBy($arrColumns, array($strSource, (int) $intParent), $arrOptions);
	}

	/**
	 * Count published comments by their source table and parent ID
	 *
	 * @param string  $strSource  The source element
	 * @param integer $intParent  The parent ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return integer The number of comments
	 */
	public static function countPublishedBySourceAndParent($strSource, $intParent, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.source=? AND $t.parent=?");

		if (!static::isPreviewMode($arrOptions))
		{
			$arrColumns[] = "$t.published=1";
		}

		return static::countBy($arrColumns, array($strSource, (int) $intParent));
	}
}
