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
 * Reads and writes image sizes
 *
 * @property integer      $id
 * @property integer      $pid
 * @property integer      $tstamp
 * @property string|null  $name
 * @property string       $cssClass
 * @property string       $densities
 * @property string       $sizes
 * @property integer|null $width
 * @property integer|null $height
 * @property string       $resizeMode
 * @property integer|null $zoom
 * @property string       $formats
 * @property boolean      $preserveMetadata
 * @property string       $metadata
 * @property boolean      $skipIfDimensionsMatch
 * @property boolean      $lazyLoading
 *
 * @method static ImageSizeModel|null findById($id, array $opt=array())
 * @method static ImageSizeModel|null findByPk($id, array $opt=array())
 * @method static ImageSizeModel|null findByIdOrAlias($val, array $opt=array())
 * @method static ImageSizeModel|null findOneBy($col, $val, array $opt=array())
 * @method static ImageSizeModel|null findOneByPid($val, array $opt=array())
 * @method static ImageSizeModel|null findOneByTstamp($val, array $opt=array())
 * @method static ImageSizeModel|null findOneByName($val, array $opt=array())
 * @method static ImageSizeModel|null findOneByCssClass($val, array $opt=array())
 * @method static ImageSizeModel|null findOneByDensities($val, array $opt=array())
 * @method static ImageSizeModel|null findOneBySizes($val, array $opt=array())
 * @method static ImageSizeModel|null findOneByWidth($val, array $opt=array())
 * @method static ImageSizeModel|null findOneByHeight($val, array $opt=array())
 * @method static ImageSizeModel|null findOneByResizeMode($val, array $opt=array())
 * @method static ImageSizeModel|null findOneByZoom($val, array $opt=array())
 * @method static ImageSizeModel|null findOneByFormats($val, array $opt=array())
 * @method static ImageSizeModel|null findOneBySkipIfDimensionsMatch($val, array $opt=array())
 * @method static ImageSizeModel|null findOneByLazyLoading($val, array $opt=array())
 *
 * @method static Collection|ImageSizeModel[]|ImageSizeModel|null findByPid($val, array $opt=array())
 * @method static Collection|ImageSizeModel[]|ImageSizeModel|null findByTstamp($val, array $opt=array())
 * @method static Collection|ImageSizeModel[]|ImageSizeModel|null findByName($val, array $opt=array())
 * @method static Collection|ImageSizeModel[]|ImageSizeModel|null findByCssClass($val, array $opt=array())
 * @method static Collection|ImageSizeModel[]|ImageSizeModel|null findByDensities($val, array $opt=array())
 * @method static Collection|ImageSizeModel[]|ImageSizeModel|null findBySizes($val, array $opt=array())
 * @method static Collection|ImageSizeModel[]|ImageSizeModel|null findByWidth($val, array $opt=array())
 * @method static Collection|ImageSizeModel[]|ImageSizeModel|null findByHeight($val, array $opt=array())
 * @method static Collection|ImageSizeModel[]|ImageSizeModel|null findByResizeMode($val, array $opt=array())
 * @method static Collection|ImageSizeModel[]|ImageSizeModel|null findByZoom($val, array $opt=array())
 * @method static Collection|ImageSizeModel[]|ImageSizeModel|null findByFormats($val, array $opt=array())
 * @method static Collection|ImageSizeModel[]|ImageSizeModel|null findBySkipIfDimensionsMatch($val, array $opt=array())
 * @method static Collection|ImageSizeModel[]|ImageSizeModel|null findByLazyLoading($val, array $opt=array())
 * @method static Collection|ImageSizeModel[]|ImageSizeModel|null findMultipleByIds($val, array $opt=array())
 * @method static Collection|ImageSizeModel[]|ImageSizeModel|null findBy($col, $val, array $opt=array())
 * @method static Collection|ImageSizeModel[]|ImageSizeModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByPid($val, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByName($val, array $opt=array())
 * @method static integer countByCssClass($val, array $opt=array())
 * @method static integer countByDensities($val, array $opt=array())
 * @method static integer countBySizes($val, array $opt=array())
 * @method static integer countByWidth($val, array $opt=array())
 * @method static integer countByHeight($val, array $opt=array())
 * @method static integer countByResizeMode($val, array $opt=array())
 * @method static integer countByZoom($val, array $opt=array())
 * @method static integer countByFormats($val, array $opt=array())
 * @method static integer countBySkipIfDimensionsMatch($val, array $opt=array())
 * @method static integer countByLazyLoading($val, array $opt=array())
 */
class ImageSizeModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_image_size';
}
