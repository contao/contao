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
 * Reads and writes FAQ categories
 *
 * @property integer $id
 * @property integer $tstamp
 * @property string  $title
 * @property string  $headline
 * @property integer $jumpTo
 * @property boolean $allowComments
 * @property string  $notify
 * @property string  $sortOrder
 * @property integer $perPage
 * @property boolean $moderate
 * @property boolean $bbcode
 * @property boolean $requireLogin
 * @property boolean $disableCaptcha
 *
 * @method static FaqCategoryModel|null findById($id, array $opt=array())
 * @method static FaqCategoryModel|null findByPk($id, array $opt=array())
 * @method static FaqCategoryModel|null findByIdOrAlias($val, array $opt=array())
 * @method static FaqCategoryModel|null findOneBy($col, $val, array $opt=array())
 * @method static FaqCategoryModel|null findOneByTstamp($val, array $opt=array())
 * @method static FaqCategoryModel|null findOneByTitle($val, array $opt=array())
 * @method static FaqCategoryModel|null findOneByHeadline($val, array $opt=array())
 * @method static FaqCategoryModel|null findOneByJumpTo($val, array $opt=array())
 * @method static FaqCategoryModel|null findOneByAllowComments($val, array $opt=array())
 * @method static FaqCategoryModel|null findOneByNotify($val, array $opt=array())
 * @method static FaqCategoryModel|null findOneBySortOrder($val, array $opt=array())
 * @method static FaqCategoryModel|null findOneByPerPage($val, array $opt=array())
 * @method static FaqCategoryModel|null findOneByModerate($val, array $opt=array())
 * @method static FaqCategoryModel|null findOneByBbcode($val, array $opt=array())
 * @method static FaqCategoryModel|null findOneByRequireLogin($val, array $opt=array())
 * @method static FaqCategoryModel|null findOneByDisableCaptcha($val, array $opt=array())
 *
 * @method static Collection|FaqCategoryModel[]|FaqCategoryModel|null findByTstamp($val, array $opt=array())
 * @method static Collection|FaqCategoryModel[]|FaqCategoryModel|null findByTitle($val, array $opt=array())
 * @method static Collection|FaqCategoryModel[]|FaqCategoryModel|null findByHeadline($val, array $opt=array())
 * @method static Collection|FaqCategoryModel[]|FaqCategoryModel|null findByJumpTo($val, array $opt=array())
 * @method static Collection|FaqCategoryModel[]|FaqCategoryModel|null findByAllowComments($val, array $opt=array())
 * @method static Collection|FaqCategoryModel[]|FaqCategoryModel|null findByNotify($val, array $opt=array())
 * @method static Collection|FaqCategoryModel[]|FaqCategoryModel|null findBySortOrder($val, array $opt=array())
 * @method static Collection|FaqCategoryModel[]|FaqCategoryModel|null findByPerPage($val, array $opt=array())
 * @method static Collection|FaqCategoryModel[]|FaqCategoryModel|null findByModerate($val, array $opt=array())
 * @method static Collection|FaqCategoryModel[]|FaqCategoryModel|null findByBbcode($val, array $opt=array())
 * @method static Collection|FaqCategoryModel[]|FaqCategoryModel|null findByRequireLogin($val, array $opt=array())
 * @method static Collection|FaqCategoryModel[]|FaqCategoryModel|null findByDisableCaptcha($val, array $opt=array())
 * @method static Collection|FaqCategoryModel[]|FaqCategoryModel|null findMultipleByIds($val, array $opt=array())
 * @method static Collection|FaqCategoryModel[]|FaqCategoryModel|null findBy($col, $val, array $opt=array())
 * @method static Collection|FaqCategoryModel[]|FaqCategoryModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByTitle($val, array $opt=array())
 * @method static integer countByHeadline($val, array $opt=array())
 * @method static integer countByJumpTo($val, array $opt=array())
 * @method static integer countByAllowComments($val, array $opt=array())
 * @method static integer countByNotify($val, array $opt=array())
 * @method static integer countBySortOrder($val, array $opt=array())
 * @method static integer countByPerPage($val, array $opt=array())
 * @method static integer countByModerate($val, array $opt=array())
 * @method static integer countByBbcode($val, array $opt=array())
 * @method static integer countByRequireLogin($val, array $opt=array())
 * @method static integer countByDisableCaptcha($val, array $opt=array())
 */
class FaqCategoryModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_faq_category';
}
