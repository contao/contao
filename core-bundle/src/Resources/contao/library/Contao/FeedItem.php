<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Symfony\Component\Filesystem\Path;

/**
 * Creates items to be appended to RSS or Atom feeds
 *
 * The class provides an interface to create RSS or Atom feed items. You can
 * then add the items to a Feed object.
 *
 * Usage:
 *
 *     $feed = new Feed('news');
 *     $feed->title = 'News feed';
 *
 *     $item = new FeedItem();
 *     $item->title = 'Latest news';
 *     $item->author = 'Leo Feyer';
 *
 *     $feed->addItem($item);
 *     echo $feed->generateRss();
 *
 * @property string  $title       The item title
 * @property string  $link        The item link
 * @property integer $published   The publication status
 * @property integer $begin       The start date
 * @property integer $end         The end date
 * @property string  $author      The item author
 * @property string  $description The item description
 */
class FeedItem
{
	/**
	 * Data
	 * @var array
	 */
	protected $arrData = array();

	/**
	 * Set the data from an array
	 *
	 * @param array $arrData An optional data array
	 */
	public function __construct($arrData=null)
	{
		if (\is_array($arrData))
		{
			$this->arrData = $arrData;
		}
	}

	/**
	 * Set an object property
	 *
	 * @param string $strKey   The property name
	 * @param mixed  $varValue The property value
	 */
	public function __set($strKey, $varValue)
	{
		$this->arrData[$strKey] = str_replace(array('[-]', '&shy;', '[nbsp]', '&nbsp;'), array('', '', ' ', ' '), $varValue);
	}

	/**
	 * Return an object property
	 *
	 * @param string $strKey The property name
	 *
	 * @return mixed|null The property value
	 */
	public function __get($strKey)
	{
		return $this->arrData[$strKey] ?? null;
	}

	/**
	 * Check whether a property is set
	 *
	 * @param string $strKey The property name
	 *
	 * @return boolean True if the property is set
	 */
	public function __isset($strKey)
	{
		return isset($this->arrData[$strKey]);
	}

	/**
	 * Add an enclosure
	 *
	 * @param string $strFile   The file path
	 * @param null   $strUrl    The base URL
	 * @param string $strMedia  The media type
	 * @param mixed  $imageSize The image size
	 */
	public function addEnclosure($strFile, $strUrl=null, $strMedia='enclosure', $imageSize = null)
	{
		$rootDir = System::getContainer()->getParameter('kernel.project_dir');

		if (!$strFile || !file_exists(Path::join($rootDir, $strFile)))
		{
			return;
		}

		if ($strUrl === null)
		{
			$strUrl = Environment::get('base');
		}

		$fileUrl = $strUrl . System::urlEncode($strFile);
		$objFile = new File($strFile);
		$size = StringUtil::deserialize($imageSize, true);

		if ($size && $objFile->isImage)
		{
			$image = System::getContainer()->get('contao.image.image_factory')->create(Path::join($rootDir, $strFile), $size);
			$fileUrl = $strUrl . System::urlEncode($image->getUrl($rootDir));
			$objFile = new File(Path::makeRelative($image->getPath(), $rootDir));
		}

		$mediaData = array(
			'media' => $strMedia,
			'url' => $fileUrl,
			'type' => $objFile->mime
		);

		if ($objFile->exists())
		{
			$mediaData['length'] = $objFile->size;
		}

		$this->arrData['enclosure'][] = $mediaData;
	}
}
