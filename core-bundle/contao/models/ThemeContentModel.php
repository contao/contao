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
 * Reads and writes theme contents
 *
 * @property integer $id
 * @property integer $pid
 * @property integer $tstamp
 * @property string  $title
 *
 * @method static ThemeContentModel|null findById($id, array $opt=array())
 * @method static ThemeContentModel|null findByPk($id, array $opt=array())
 * @method static ThemeContentModel|null findByIdOrAlias($val, array $opt=array())
 * @method static ThemeContentModel|null findOneBy($col, $val, array $opt=array())
 * @method static ThemeContentModel|null findOneByTstamp($val, array $opt=array())
 *
 * @method static Collection<ThemeContentModel>|ThemeContentModel[]|null findMultipleByIds($val, array $opt=array())
 * @method static Collection<ThemeContentModel>|ThemeContentModel[]|null findBy($col, $val, array $opt=array())
 * @method static Collection<ThemeContentModel>|ThemeContentModel[]|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 */
class ThemeContentModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_theme_content';
}
