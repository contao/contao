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
 * Reads and writes member groups
 *
 * @property integer        $id
 * @property integer        $tstamp
 * @property string         $name
 * @property boolean        $redirect
 * @property integer        $jumpTo
 * @property boolean        $disable
 * @property string|integer $start
 * @property string|integer $stop
 *
 * @method static MemberGroupModel|null findById($id, array $opt=array())
 * @method static MemberGroupModel|null findByPk($id, array $opt=array())
 * @method static MemberGroupModel|null findByIdOrAlias($val, array $opt=array())
 * @method static MemberGroupModel|null findOneBy($col, $val, array $opt=array())
 * @method static MemberGroupModel|null findOneByTstamp($val, array $opt=array())
 * @method static MemberGroupModel|null findOneByName($val, array $opt=array())
 * @method static MemberGroupModel|null findOneByRedirect($val, array $opt=array())
 * @method static MemberGroupModel|null findOneByJumpTo($val, array $opt=array())
 * @method static MemberGroupModel|null findOneByDisable($val, array $opt=array())
 * @method static MemberGroupModel|null findOneByStart($val, array $opt=array())
 * @method static MemberGroupModel|null findOneByStop($val, array $opt=array())
 *
 * @method static Collection|MemberGroupModel[]|MemberGroupModel|null findByTstamp($val, array $opt=array())
 * @method static Collection|MemberGroupModel[]|MemberGroupModel|null findByName($val, array $opt=array())
 * @method static Collection|MemberGroupModel[]|MemberGroupModel|null findByRedirect($val, array $opt=array())
 * @method static Collection|MemberGroupModel[]|MemberGroupModel|null findByJumpTo($val, array $opt=array())
 * @method static Collection|MemberGroupModel[]|MemberGroupModel|null findByDisable($val, array $opt=array())
 * @method static Collection|MemberGroupModel[]|MemberGroupModel|null findByStart($val, array $opt=array())
 * @method static Collection|MemberGroupModel[]|MemberGroupModel|null findByStop($val, array $opt=array())
 * @method static Collection|MemberGroupModel[]|MemberGroupModel|null findMultipleByIds($val, array $opt=array())
 * @method static Collection|MemberGroupModel[]|MemberGroupModel|null findBy($col, $val, array $opt=array())
 * @method static Collection|MemberGroupModel[]|MemberGroupModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByName($val, array $opt=array())
 * @method static integer countByRedirect($val, array $opt=array())
 * @method static integer countByJumpTo($val, array $opt=array())
 * @method static integer countByDisable($val, array $opt=array())
 * @method static integer countByStart($val, array $opt=array())
 * @method static integer countByStop($val, array $opt=array())
 */
class MemberGroupModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_member_group';

	/**
	 * Find a published group by its ID
	 *
	 * @param integer $intId      The member group ID
	 * @param array   $arrOptions An optional options array
	 *
	 * @return MemberGroupModel|null The model or null if there is no member group
	 */
	public static function findPublishedById($intId, array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = array("$t.id=?");

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.disable=0 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		return static::findOneBy($arrColumns, array($intId), $arrOptions);
	}

	/**
	 * Find all active groups
	 *
	 * @param array $arrOptions An optional options array
	 *
	 * @return Collection|MemberGroupModel|null A collection of models or null if there are no member groups
	 */
	public static function findAllActive(array $arrOptions=array())
	{
		$t = static::$strTable;
		$time = Date::floorToMinute();

		return static::findBy(array("$t.disable=0 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)"), null, $arrOptions);
	}
}
