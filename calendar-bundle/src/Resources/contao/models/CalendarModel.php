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
 * Reads and writes calendars
 *
 * @property integer $id
 * @property integer $tstamp
 * @property string  $title
 * @property integer $jumpTo
 * @property boolean $protected
 * @property string  $groups
 * @property boolean $allowComments
 * @property string  $notify
 * @property string  $sortOrder
 * @property integer $perPage
 * @property boolean $moderate
 * @property boolean $bbcode
 * @property boolean $requireLogin
 * @property boolean $disableCaptcha
 *
 * @method static CalendarModel|null findById($id, array $opt=array())
 * @method static CalendarModel|null findByPk($id, array $opt=array())
 * @method static CalendarModel|null findByIdOrAlias($val, array $opt=array())
 * @method static CalendarModel|null findOneBy($col, $val, array $opt=array())
 * @method static CalendarModel|null findOneByTstamp($val, array $opt=array())
 * @method static CalendarModel|null findOneByTitle($val, array $opt=array())
 * @method static CalendarModel|null findOneByJumpTo($val, array $opt=array())
 * @method static CalendarModel|null findOneByProtected($val, array $opt=array())
 * @method static CalendarModel|null findOneByGroups($val, array $opt=array())
 * @method static CalendarModel|null findOneByAllowComments($val, array $opt=array())
 * @method static CalendarModel|null findOneByNotify($val, array $opt=array())
 * @method static CalendarModel|null findOneBySortOrder($val, array $opt=array())
 * @method static CalendarModel|null findOneByPerPage($val, array $opt=array())
 * @method static CalendarModel|null findOneByModerate($val, array $opt=array())
 * @method static CalendarModel|null findOneByBbcode($val, array $opt=array())
 * @method static CalendarModel|null findOneByRequireLogin($val, array $opt=array())
 * @method static CalendarModel|null findOneByDisableCaptcha($val, array $opt=array())
 *
 * @method static Model\Collection|CalendarModel[]|CalendarModel|null findByTstamp($val, array $opt=array())
 * @method static Model\Collection|CalendarModel[]|CalendarModel|null findByTitle($val, array $opt=array())
 * @method static Model\Collection|CalendarModel[]|CalendarModel|null findByJumpTo($val, array $opt=array())
 * @method static Model\Collection|CalendarModel[]|CalendarModel|null findByProtected($val, array $opt=array())
 * @method static Model\Collection|CalendarModel[]|CalendarModel|null findByGroups($val, array $opt=array())
 * @method static Model\Collection|CalendarModel[]|CalendarModel|null findByAllowComments($val, array $opt=array())
 * @method static Model\Collection|CalendarModel[]|CalendarModel|null findByNotify($val, array $opt=array())
 * @method static Model\Collection|CalendarModel[]|CalendarModel|null findBySortOrder($val, array $opt=array())
 * @method static Model\Collection|CalendarModel[]|CalendarModel|null findByPerPage($val, array $opt=array())
 * @method static Model\Collection|CalendarModel[]|CalendarModel|null findByModerate($val, array $opt=array())
 * @method static Model\Collection|CalendarModel[]|CalendarModel|null findByBbcode($val, array $opt=array())
 * @method static Model\Collection|CalendarModel[]|CalendarModel|null findByRequireLogin($val, array $opt=array())
 * @method static Model\Collection|CalendarModel[]|CalendarModel|null findByDisableCaptcha($val, array $opt=array())
 * @method static Model\Collection|CalendarModel[]|CalendarModel|null findMultipleByIds($val, array $opt=array())
 * @method static Model\Collection|CalendarModel[]|CalendarModel|null findBy($col, $val, array $opt=array())
 * @method static Model\Collection|CalendarModel[]|CalendarModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByTitle($val, array $opt=array())
 * @method static integer countByJumpTo($val, array $opt=array())
 * @method static integer countByProtected($val, array $opt=array())
 * @method static integer countByGroups($val, array $opt=array())
 * @method static integer countByAllowComments($val, array $opt=array())
 * @method static integer countByNotify($val, array $opt=array())
 * @method static integer countBySortOrder($val, array $opt=array())
 * @method static integer countByPerPage($val, array $opt=array())
 * @method static integer countByModerate($val, array $opt=array())
 * @method static integer countByBbcode($val, array $opt=array())
 * @method static integer countByRequireLogin($val, array $opt=array())
 * @method static integer countByDisableCaptcha($val, array $opt=array())
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class CalendarModel extends Model
{
	public const BREADCRUMB_MODE_EXTEND = 'extend';

	public const BREADCRUMB_MODE_OVERRIDE = 'override';

	public const BREADCRUMB_MODES = [self::BREADCRUMB_MODE_OVERRIDE, self::BREADCRUMB_MODE_EXTEND];

	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_calendar';

}

class_alias(CalendarModel::class, 'CalendarModel');
