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
 * Creates RSS or Atom feeds
 *
 * The class provides an interface to create RSS or Atom feeds. You can add the
 * feed item objects and the class will generate the XML markup.
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
 * @property string  $title       The feed title
 * @property string  $description The feed description
 * @property string  $language    The feed language
 * @property string  $link        The feed link
 * @property integer $published   The publication date
 */
class Feed
{
	/**
	 * Feed name
	 * @var string
	 */
	protected $strName;

	/**
	 * Data
	 * @var array
	 */
	protected $arrData = array();

	/**
	 * Items
	 * @var array
	 */
	protected $arrItems = array();

	/**
	 * Store the feed name (without file extension)
	 *
	 * @param string $strName The feed name
	 */
	public function __construct($strName)
	{
		$this->strName = $strName;
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
	 * Add an item
	 *
	 * @param FeedItem $objItem The feed item object
	 */
	public function addItem(FeedItem $objItem)
	{
		$this->arrItems[] = $objItem;
	}

	/**
	 * Generate an RSS 2.0 feed and return it as XML string
	 *
	 * @return string The RSS feed markup
	 */
	public function generateRss()
	{
		$this->adjustPublicationDate();

		$xml  = '<?xml version="1.0" encoding="' . System::getContainer()->getParameter('kernel.charset') . '"?>';
		$xml .= '<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/" xmlns:atom="http://www.w3.org/2005/Atom">';
		$xml .= '<channel>';
		$xml .= '<title>' . StringUtil::specialchars($this->title) . '</title>';
		$xml .= '<description>' . StringUtil::specialchars($this->description) . '</description>';
		$xml .= '<link>' . StringUtil::specialchars($this->link) . '</link>';
		$xml .= '<language>' . $this->language . '</language>';
		$xml .= '<pubDate>' . date('r', $this->published) . '</pubDate>';
		$xml .= '<generator>Contao Open Source CMS</generator>';
		$xml .= '<atom:link href="' . StringUtil::specialchars(Environment::get('base') . 'share/' . $this->strName) . '.xml" rel="self" type="application/rss+xml" />';

		foreach ($this->arrItems as $objItem)
		{
			$xml .= '<item>';
			$xml .= '<title>' . StringUtil::specialchars(strip_tags(StringUtil::stripInsertTags($objItem->title))) . '</title>';
			$xml .= '<description><![CDATA[' . preg_replace('/[\n\r]+/', ' ', $objItem->description) . ']]></description>';
			$xml .= '<link>' . StringUtil::specialchars($objItem->link) . '</link>';
			$xml .= '<pubDate>' . date('r', $objItem->published) . '</pubDate>';

			// Add the GUID
			if ($objItem->guid)
			{
				$xml .= '<guid isPermaLink="false">' . $objItem->guid . '</guid>';
			}
			else
			{
				$xml .= '<guid>' . StringUtil::specialchars($objItem->link) . '</guid>';
			}

			// Enclosures
			if (\is_array($objItem->enclosure))
			{
				foreach ($objItem->enclosure as $arrEnclosure)
				{
					if (!empty($arrEnclosure['media']) && $arrEnclosure['media'] == 'media:content')
					{
						$xml .= '<media:content url="' . $arrEnclosure['url'] . '" type="' . $arrEnclosure['type'] . '" />';
					}
					else
					{
						$xml .= '<enclosure url="' . $arrEnclosure['url'] . '" length="' . $arrEnclosure['length'] . '" type="' . $arrEnclosure['type'] . '" />';
					}
				}
			}

			$xml .= '</item>';
		}

		$xml .= '</channel>';
		$xml .= '</rss>';

		return $xml;
	}

	/**
	 * Generate an Atom feed and return it as XML string
	 *
	 * @return string The Atom feed markup
	 */
	public function generateAtom()
	{
		$this->adjustPublicationDate();

		$xml  = '<?xml version="1.0" encoding="' . System::getContainer()->getParameter('kernel.charset') . '"?>';
		$xml .= '<feed xmlns="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/" xml:lang="' . $this->language . '">';
		$xml .= '<title>' . StringUtil::specialchars($this->title) . '</title>';
		$xml .= '<subtitle>' . StringUtil::specialchars($this->description) . '</subtitle>';
		$xml .= '<link rel="alternate" href="' . StringUtil::specialchars($this->link) . '" />';
		$xml .= '<id>' . StringUtil::specialchars($this->link) . '</id>';
		$xml .= '<updated>' . date('Y-m-d\TH:i:sP', $this->published) . '</updated>';
		$xml .= '<generator>Contao Open Source CMS</generator>';
		$xml .= '<link href="' . StringUtil::specialchars(Environment::get('base') . 'share/' . $this->strName) . '.xml" rel="self" />';

		foreach ($this->arrItems as $objItem)
		{
			$xml .= '<entry>';
			$xml .= '<title>' . StringUtil::specialchars(strip_tags(StringUtil::stripInsertTags($objItem->title))) . '</title>';
			$xml .= '<content type="html">' . preg_replace('/[\n\r]+/', ' ', htmlspecialchars($objItem->description, ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1, 'UTF-8')) . '</content>';
			$xml .= '<link rel="alternate" href="' . StringUtil::specialchars($objItem->link) . '" />';
			$xml .= '<updated>' . date('Y-m-d\TH:i:sP', $objItem->published) . '</updated>';
			$xml .= '<id>' . ($objItem->guid ?: StringUtil::specialchars($objItem->link)) . '</id>';

			if ($objItem->author)
			{
				$xml .= '<author><name>' . $objItem->author . '</name></author>';
			}

			// Enclosures
			if (\is_array($objItem->enclosure))
			{
				foreach ($objItem->enclosure as $arrEnclosure)
				{
					if (!empty($arrEnclosure['media']) && $arrEnclosure['media'] == 'media:content')
					{
						$xml .= '<media:content url="' . $arrEnclosure['url'] . '" type="' . $arrEnclosure['type'] . '" />';
					}
					else
					{
						$xml .= '<link rel="enclosure" type="' . $arrEnclosure['type'] . '" href="' . $arrEnclosure['url'] . '" length="' . $arrEnclosure['length'] . '" />';
					}
				}
			}

			$xml .= '</entry>';
		}

		return $xml . '</feed>';
	}

	/**
	 * Adjust the publication date
	 */
	protected function adjustPublicationDate()
	{
		if (!empty($this->arrItems) && $this->arrItems[0]->published > $this->published)
		{
			$this->published = $this->arrItems[0]->published;
		}
	}
}
