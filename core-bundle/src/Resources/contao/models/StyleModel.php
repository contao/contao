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
 * Reads and writes format definitions
 *
 * @property string|integer $id
 * @property string|integer $pid
 * @property string|integer $sorting
 * @property string|integer $tstamp
 * @property string         $selector
 * @property string         $category
 * @property string         $comment
 * @property string|boolean $size
 * @property string|array   $width
 * @property string|array   $height
 * @property string|array   $minwidth
 * @property string|array   $minheight
 * @property string|array   $maxwidth
 * @property string|array   $maxheight
 * @property string|boolean $positioning
 * @property string|array   $trbl
 * @property string         $position
 * @property string         $floating
 * @property string         $clear
 * @property string         $overflow
 * @property string         $display
 * @property string|boolean $alignment
 * @property string|array   $margin
 * @property string|array   $padding
 * @property string         $align
 * @property string         $verticalalign
 * @property string         $textalign
 * @property string         $whitespace
 * @property string|boolean $background
 * @property string         $bgcolor
 * @property string         $bgimage
 * @property string         $bgposition
 * @property string         $bgrepeat
 * @property string|array   $shadowsize
 * @property string|array   $shadowcolor
 * @property string         $gradientAngle
 * @property string|array   $gradientColors
 * @property string|boolean $border
 * @property string|array   $borderwidth
 * @property string         $borderstyle
 * @property string|array   $bordercolor
 * @property string|array   $borderradius
 * @property string         $bordercollapse
 * @property string|array   $borderspacing
 * @property string|boolean $font
 * @property string         $fontfamily
 * @property string|array   $fontsize
 * @property string|array   $fontcolor
 * @property string|array   $lineheight
 * @property string         $fontstyle
 * @property string         $texttransform
 * @property string|array   $textindent
 * @property string|array   $letterspacing
 * @property string|array   $wordspacing
 * @property string|boolean $list
 * @property string         $liststyletype
 * @property string         $liststyleimage
 * @property string|null    $own
 * @property string|boolean $invisible
 *
 * @method static StyleModel|null findById($id, array $opt=array())
 * @method static StyleModel|null findByPk($id, array $opt=array())
 * @method static StyleModel|null findByIdOrAlias($val, array $opt=array())
 * @method static StyleModel|null findOneBy($col, $val, array $opt=array())
 * @method static StyleModel|null findOneByPid($val, array $opt=array())
 * @method static StyleModel|null findOneBySorting($val, array $opt=array())
 * @method static StyleModel|null findOneByTstamp($val, array $opt=array())
 * @method static StyleModel|null findOneBySelector($val, array $opt=array())
 * @method static StyleModel|null findOneByCategory($val, array $opt=array())
 * @method static StyleModel|null findOneByComment($val, array $opt=array())
 * @method static StyleModel|null findOneBySize($val, array $opt=array())
 * @method static StyleModel|null findOneByWidth($val, array $opt=array())
 * @method static StyleModel|null findOneByHeight($val, array $opt=array())
 * @method static StyleModel|null findOneByMinwidth($val, array $opt=array())
 * @method static StyleModel|null findOneByMinheight($val, array $opt=array())
 * @method static StyleModel|null findOneByMaxwidth($val, array $opt=array())
 * @method static StyleModel|null findOneByMaxheight($val, array $opt=array())
 * @method static StyleModel|null findOneByPositioning($val, array $opt=array())
 * @method static StyleModel|null findOneByTrbl($val, array $opt=array())
 * @method static StyleModel|null findOneByPosition($val, array $opt=array())
 * @method static StyleModel|null findOneByFloating($val, array $opt=array())
 * @method static StyleModel|null findOneByClear($val, array $opt=array())
 * @method static StyleModel|null findOneByOverflow($val, array $opt=array())
 * @method static StyleModel|null findOneByDisplay($val, array $opt=array())
 * @method static StyleModel|null findOneByAlignment($val, array $opt=array())
 * @method static StyleModel|null findOneByMargin($val, array $opt=array())
 * @method static StyleModel|null findOneByPadding($val, array $opt=array())
 * @method static StyleModel|null findOneByAlign($val, array $opt=array())
 * @method static StyleModel|null findOneByVerticalalign($val, array $opt=array())
 * @method static StyleModel|null findOneByTextalign($val, array $opt=array())
 * @method static StyleModel|null findOneByWhitespace($val, array $opt=array())
 * @method static StyleModel|null findOneByBackground($val, array $opt=array())
 * @method static StyleModel|null findOneByBgcolor($val, array $opt=array())
 * @method static StyleModel|null findOneByBgimage($val, array $opt=array())
 * @method static StyleModel|null findOneByBgposition($val, array $opt=array())
 * @method static StyleModel|null findOneByBgrepeat($val, array $opt=array())
 * @method static StyleModel|null findOneByShadowsize($val, array $opt=array())
 * @method static StyleModel|null findOneByShadowcolor($val, array $opt=array())
 * @method static StyleModel|null findOneByGradientAngle($val, array $opt=array())
 * @method static StyleModel|null findOneByGradientColors($val, array $opt=array())
 * @method static StyleModel|null findOneByBorder($val, array $opt=array())
 * @method static StyleModel|null findOneByBorderwidth($val, array $opt=array())
 * @method static StyleModel|null findOneByBorderstyle($val, array $opt=array())
 * @method static StyleModel|null findOneByBordercolor($val, array $opt=array())
 * @method static StyleModel|null findOneByBorderradius($val, array $opt=array())
 * @method static StyleModel|null findOneByBordercollapse($val, array $opt=array())
 * @method static StyleModel|null findOneByBorderspacing($val, array $opt=array())
 * @method static StyleModel|null findOneByFont($val, array $opt=array())
 * @method static StyleModel|null findOneByFontfamily($val, array $opt=array())
 * @method static StyleModel|null findOneByFontsize($val, array $opt=array())
 * @method static StyleModel|null findOneByFontcolor($val, array $opt=array())
 * @method static StyleModel|null findOneByLineheight($val, array $opt=array())
 * @method static StyleModel|null findOneByFontstyle($val, array $opt=array())
 * @method static StyleModel|null findOneByTexttransform($val, array $opt=array())
 * @method static StyleModel|null findOneByTextindent($val, array $opt=array())
 * @method static StyleModel|null findOneByLetterspacing($val, array $opt=array())
 * @method static StyleModel|null findOneByWordspacing($val, array $opt=array())
 * @method static StyleModel|null findOneByList($val, array $opt=array())
 * @method static StyleModel|null findOneByListstyletype($val, array $opt=array())
 * @method static StyleModel|null findOneByListstyleimage($val, array $opt=array())
 * @method static StyleModel|null findOneByOwn($val, array $opt=array())
 * @method static StyleModel|null findOneByInvisible($val, array $opt=array())
 *
 * @method static Collection|StyleModel[]|StyleModel|null findByPid($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findBySorting($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByTstamp($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findBySelector($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByCategory($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByComment($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findBySize($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByWidth($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByHeight($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByMinwidth($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByMinheight($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByMaxwidth($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByMaxheight($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByPositioning($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByTrbl($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByPosition($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByFloating($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByClear($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByOverflow($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByDisplay($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByAlignment($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByMargin($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByPadding($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByAlign($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByVerticalalign($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByTextalign($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByWhitespace($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByBackground($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByBgcolor($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByBgimage($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByBgposition($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByBgrepeat($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByShadowsize($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByShadowcolor($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByGradientAngle($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByGradientColors($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByBorder($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByBorderwidth($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByBorderstyle($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByBordercolor($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByBorderradius($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByBordercollapse($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByBorderspacing($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByFont($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByFontfamily($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByFontsize($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByFontcolor($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByLineheight($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByFontstyle($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByTexttransform($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByTextindent($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByLetterspacing($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByWordspacing($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByList($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByListstyletype($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByListstyleimage($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByOwn($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findByInvisible($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findMultipleByIds($val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findBy($col, $val, array $opt=array())
 * @method static Collection|StyleModel[]|StyleModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByPid($val, array $opt=array())
 * @method static integer countBySorting($val, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countBySelector($val, array $opt=array())
 * @method static integer countByCategory($val, array $opt=array())
 * @method static integer countByComment($val, array $opt=array())
 * @method static integer countBySize($val, array $opt=array())
 * @method static integer countByWidth($val, array $opt=array())
 * @method static integer countByHeight($val, array $opt=array())
 * @method static integer countByMinwidth($val, array $opt=array())
 * @method static integer countByMinheight($val, array $opt=array())
 * @method static integer countByMaxwidth($val, array $opt=array())
 * @method static integer countByMaxheight($val, array $opt=array())
 * @method static integer countByPositioning($val, array $opt=array())
 * @method static integer countByTrbl($val, array $opt=array())
 * @method static integer countByPosition($val, array $opt=array())
 * @method static integer countByFloating($val, array $opt=array())
 * @method static integer countByClear($val, array $opt=array())
 * @method static integer countByOverflow($val, array $opt=array())
 * @method static integer countByDisplay($val, array $opt=array())
 * @method static integer countByAlignment($val, array $opt=array())
 * @method static integer countByMargin($val, array $opt=array())
 * @method static integer countByPadding($val, array $opt=array())
 * @method static integer countByAlign($val, array $opt=array())
 * @method static integer countByVerticalalign($val, array $opt=array())
 * @method static integer countByTextalign($val, array $opt=array())
 * @method static integer countByWhitespace($val, array $opt=array())
 * @method static integer countByBackground($val, array $opt=array())
 * @method static integer countByBgcolor($val, array $opt=array())
 * @method static integer countByBgimage($val, array $opt=array())
 * @method static integer countByBgposition($val, array $opt=array())
 * @method static integer countByBgrepeat($val, array $opt=array())
 * @method static integer countByShadowsize($val, array $opt=array())
 * @method static integer countByShadowcolor($val, array $opt=array())
 * @method static integer countByGradientAngle($val, array $opt=array())
 * @method static integer countByGradientColors($val, array $opt=array())
 * @method static integer countByBorder($val, array $opt=array())
 * @method static integer countByBorderwidth($val, array $opt=array())
 * @method static integer countByBorderstyle($val, array $opt=array())
 * @method static integer countByBordercolor($val, array $opt=array())
 * @method static integer countByBorderradius($val, array $opt=array())
 * @method static integer countByBordercollapse($val, array $opt=array())
 * @method static integer countByBorderspacing($val, array $opt=array())
 * @method static integer countByFont($val, array $opt=array())
 * @method static integer countByFontfamily($val, array $opt=array())
 * @method static integer countByFontsize($val, array $opt=array())
 * @method static integer countByFontcolor($val, array $opt=array())
 * @method static integer countByLineheight($val, array $opt=array())
 * @method static integer countByFontstyle($val, array $opt=array())
 * @method static integer countByTexttransform($val, array $opt=array())
 * @method static integer countByTextindent($val, array $opt=array())
 * @method static integer countByLetterspacing($val, array $opt=array())
 * @method static integer countByWordspacing($val, array $opt=array())
 * @method static integer countByList($val, array $opt=array())
 * @method static integer countByListstyletype($val, array $opt=array())
 * @method static integer countByListstyleimage($val, array $opt=array())
 * @method static integer countByOwn($val, array $opt=array())
 * @method static integer countByInvisible($val, array $opt=array())
 */
class StyleModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_style';
}

class_alias(StyleModel::class, 'StyleModel');
