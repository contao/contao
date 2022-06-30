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
 * Reads and writes news archives
 *
 * @property integer           $id
 * @property integer           $tstamp
 * @property string            $title
 * @property integer           $jumpTo
 * @property boolean           $protected
 * @property string|array|null $groups
 * @property boolean           $allowComments
 * @property string            $notify
 * @property string            $sortOrder
 * @property integer           $perPage
 * @property boolean           $moderate
 * @property boolean           $bbcode
 * @property boolean           $requireLogin
 * @property boolean           $disableCaptcha
 *
 * @method static NewsArchiveModel|null findById($id, array $opt=array())
 * @method static NewsArchiveModel|null findByPk($id, array $opt=array())
 * @method static NewsArchiveModel|null findByIdOrAlias($val, array $opt=array())
 * @method static NewsArchiveModel|null findOneBy($col, $val, array $opt=array())
 * @method static NewsArchiveModel|null findOneByTstamp($val, array $opt=array())
 * @method static NewsArchiveModel|null findOneByTitle($val, array $opt=array())
 * @method static NewsArchiveModel|null findOneByJumpTo($val, array $opt=array())
 * @method static NewsArchiveModel|null findOneByProtected($val, array $opt=array())
 * @method static NewsArchiveModel|null findOneByGroups($val, array $opt=array())
 * @method static NewsArchiveModel|null findOneByAllowComments($val, array $opt=array())
 * @method static NewsArchiveModel|null findOneByNotify($val, array $opt=array())
 * @method static NewsArchiveModel|null findOneBySortOrder($val, array $opt=array())
 * @method static NewsArchiveModel|null findOneByPerPage($val, array $opt=array())
 * @method static NewsArchiveModel|null findOneByModerate($val, array $opt=array())
 * @method static NewsArchiveModel|null findOneByBbcode($val, array $opt=array())
 * @method static NewsArchiveModel|null findOneByRequireLogin($val, array $opt=array())
 * @method static NewsArchiveModel|null findOneByDisableCaptcha($val, array $opt=array())
 *
 * @method static Collection|NewsArchiveModel[]|NewsArchiveModel|null findByTstamp($val, array $opt=array())
 * @method static Collection|NewsArchiveModel[]|NewsArchiveModel|null findByTitle($val, array $opt=array())
 * @method static Collection|NewsArchiveModel[]|NewsArchiveModel|null findByJumpTo($val, array $opt=array())
 * @method static Collection|NewsArchiveModel[]|NewsArchiveModel|null findByProtected($val, array $opt=array())
 * @method static Collection|NewsArchiveModel[]|NewsArchiveModel|null findByGroups($val, array $opt=array())
 * @method static Collection|NewsArchiveModel[]|NewsArchiveModel|null findByAllowComments($val, array $opt=array())
 * @method static Collection|NewsArchiveModel[]|NewsArchiveModel|null findByNotify($val, array $opt=array())
 * @method static Collection|NewsArchiveModel[]|NewsArchiveModel|null findBySortOrder($val, array $opt=array())
 * @method static Collection|NewsArchiveModel[]|NewsArchiveModel|null findByPerPage($val, array $opt=array())
 * @method static Collection|NewsArchiveModel[]|NewsArchiveModel|null findByModerate($val, array $opt=array())
 * @method static Collection|NewsArchiveModel[]|NewsArchiveModel|null findByBbcode($val, array $opt=array())
 * @method static Collection|NewsArchiveModel[]|NewsArchiveModel|null findByRequireLogin($val, array $opt=array())
 * @method static Collection|NewsArchiveModel[]|NewsArchiveModel|null findByDisableCaptcha($val, array $opt=array())
 * @method static Collection|NewsArchiveModel[]|NewsArchiveModel|null findMultipleByIds($val, array $opt=array())
 * @method static Collection|NewsArchiveModel[]|NewsArchiveModel|null findBy($col, $val, array $opt=array())
 * @method static Collection|NewsArchiveModel[]|NewsArchiveModel|null findAll(array $opt=array())
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
 */
class NewsArchiveModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_news_archive';
}
