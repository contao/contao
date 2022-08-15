<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Provide methods regarding calendars.
 */
class Calendar extends Frontend
{
	/**
	 * Current events
	 * @var array
	 */
	protected $arrEvents = array();

	/**
	 * Page cache array
	 * @var array
	 */
	private static $arrPageCache = array();

	/**
	 * Update a particular RSS feed
	 *
	 * @param integer $intId
	 */
	public function generateFeed($intId)
	{
		$objCalendar = CalendarFeedModel::findByPk($intId);

		if ($objCalendar === null)
		{
			return;
		}

		$objCalendar->feedName = $objCalendar->alias ?: 'calendar' . $objCalendar->id;

		// Delete XML file
		if (Input::get('act') == 'delete')
		{
			$webDir = StringUtil::stripRootDir(System::getContainer()->getParameter('contao.web_dir'));

			$this->import(Files::class, 'Files');
			$this->Files->delete($webDir . '/share/' . $objCalendar->feedName . '.xml');
		}

		// Update XML file
		else
		{
			$this->generateFiles($objCalendar->row());

			System::getContainer()->get('monolog.logger.contao.cron')->info('Generated calendar feed "' . $objCalendar->feedName . '.xml"');
		}
	}

	/**
	 * Delete old files and generate all feeds
	 */
	public function generateFeeds()
	{
		$this->import(Automator::class, 'Automator');
		$this->Automator->purgeXmlFiles();

		$objCalendar = CalendarFeedModel::findAll();

		if ($objCalendar !== null)
		{
			while ($objCalendar->next())
			{
				$objCalendar->feedName = $objCalendar->alias ?: 'calendar' . $objCalendar->id;
				$this->generateFiles($objCalendar->row());

				System::getContainer()->get('monolog.logger.contao.cron')->info('Generated calendar feed "' . $objCalendar->feedName . '.xml"');
			}
		}
	}

	/**
	 * Generate all feeds including a certain calendar
	 *
	 * @param integer $intId
	 */
	public function generateFeedsByCalendar($intId)
	{
		$objFeed = CalendarFeedModel::findByCalendar($intId);

		if ($objFeed !== null)
		{
			while ($objFeed->next())
			{
				$objFeed->feedName = $objFeed->alias ?: 'calendar' . $objFeed->id;

				// Update the XML file
				$this->generateFiles($objFeed->row());

				System::getContainer()->get('monolog.logger.contao.cron')->info('Generated calendar feed "' . $objFeed->feedName . '.xml"');
			}
		}
	}

	/**
	 * Generate an XML file and save it to the root directory
	 *
	 * @param array $arrFeed
	 */
	protected function generateFiles($arrFeed)
	{
		$arrCalendars = StringUtil::deserialize($arrFeed['calendars']);

		if (empty($arrCalendars) || !\is_array($arrCalendars))
		{
			return;
		}

		$strType = ($arrFeed['format'] == 'atom') ? 'generateAtom' : 'generateRss';
		$strLink = $arrFeed['feedBase'] ?: Environment::get('base');
		$strFile = $arrFeed['feedName'];

		$objFeed = new Feed($strFile);
		$objFeed->link = $strLink;
		$objFeed->title = $arrFeed['title'];
		$objFeed->description = $arrFeed['description'];
		$objFeed->language = $arrFeed['language'];
		$objFeed->published = $arrFeed['tstamp'];

		$this->arrEvents = array();
		$time = time();

		// Get the upcoming events
		$objArticle = CalendarEventsModel::findUpcomingByPids($arrCalendars, $arrFeed['maxItems']);

		// Parse the items
		if ($objArticle !== null)
		{
			while ($objArticle->next())
			{
				// Never add unpublished elements to the RSS feeds
				if (!$objArticle->published || ($objArticle->start && $objArticle->start > $time) || ($objArticle->stop && $objArticle->stop <= $time))
				{
					continue;
				}

				$jumpTo = $objArticle->getRelated('pid')->jumpTo;

				// No jumpTo page set (see #4784)
				if (!$jumpTo)
				{
					continue;
				}

				$objParent = $this->getPageWithDetails($jumpTo);

				// A jumpTo page is set but does no longer exist (see #5781)
				if ($objParent === null)
				{
					continue;
				}

				$this->addEvent($objArticle, $objArticle->startTime, $objArticle->endTime, $objParent);

				// Recurring events
				if ($objArticle->recurring)
				{
					$arrRepeat = StringUtil::deserialize($objArticle->repeatEach);

					if (!isset($arrRepeat['unit'], $arrRepeat['value']) || $arrRepeat['value'] < 1)
					{
						continue;
					}

					$count = 0;
					$intStartTime = $objArticle->startTime;
					$intEndTime = $objArticle->endTime;
					$strtotime = '+ ' . $arrRepeat['value'] . ' ' . $arrRepeat['unit'];

					// Do not include more than 20 recurrences
					while ($count++ < 20)
					{
						if ($objArticle->recurrences > 0 && $count >= $objArticle->recurrences)
						{
							break;
						}

						$intStartTime = strtotime($strtotime, $intStartTime);
						$intEndTime = strtotime($strtotime, $intEndTime);

						if ($intStartTime >= $time)
						{
							$this->addEvent($objArticle, $intStartTime, $intEndTime, $objParent, true);
						}
					}
				}
			}
		}

		$count = 0;
		ksort($this->arrEvents);

		$container = System::getContainer();

		/** @var RequestStack $requestStack */
		$requestStack = System::getContainer()->get('request_stack');
		$currentRequest = $requestStack->getCurrentRequest();

		$origObjPage = $GLOBALS['objPage'] ?? null;

		// Add the feed items
		foreach ($this->arrEvents as $days)
		{
			foreach ($days as $events)
			{
				foreach ($events as $event)
				{
					if ($arrFeed['maxItems'] > 0 && $count++ >= $arrFeed['maxItems'])
					{
						break 3;
					}

					// Override the global page object (#2946)
					$GLOBALS['objPage'] = $this->getPageWithDetails(CalendarModel::findByPk($event['pid'])->jumpTo);

					// Push a new request to the request stack (#3856)
					$request = $this->createSubRequest($event['link'], $currentRequest);
					$request->attributes->set('_scope', 'frontend');
					$requestStack->push($request);

					$objItem = new FeedItem();
					$objItem->title = $event['title'];
					$objItem->link = $event['link'];
					$objItem->published = $event['tstamp'];
					$objItem->begin = $event['startTime'];
					$objItem->end = $event['endTime'];

					if ($event['isRepeated'] ?? null)
					{
						$objItem->guid = $event['link'] . '#' . date('Y-m-d', $event['startTime']);
					}

					if (($objAuthor = UserModel::findById($event['author'])) !== null)
					{
						$objItem->author = $objAuthor->name;
					}

					// Prepare the description
					if ($arrFeed['source'] == 'source_text')
					{
						$strDescription = '';
						$objElement = ContentModel::findPublishedByPidAndTable($event['id'], 'tl_calendar_events');

						if ($objElement !== null)
						{
							// Overwrite the request (see #7756)
							$strRequest = Environment::get('requestUri');
							Environment::set('requestUri', $objItem->link);

							while ($objElement->next())
							{
								$strDescription .= $this->getContentElement($objElement->current());
							}

							Environment::set('requestUri', $strRequest);
						}
					}
					else
					{
						$strDescription = $event['teaser'] ?? '';
					}

					$strDescription = System::getContainer()->get('contao.insert_tag.parser')->replaceInline($strDescription);
					$objItem->description = $this->convertRelativeUrls($strDescription, $strLink);

					if (\is_array($event['media:content']))
					{
						foreach ($event['media:content'] as $enclosure)
						{
							$objItem->addEnclosure($enclosure, $strLink, 'media:content', $arrFeed['imgSize']);
						}
					}

					if (\is_array($event['enclosure']))
					{
						foreach ($event['enclosure'] as $enclosure)
						{
							$objItem->addEnclosure($enclosure, $strLink);
						}
					}

					$objFeed->addItem($objItem);

					$requestStack->pop();
				}
			}
		}

		$GLOBALS['objPage'] = $origObjPage;

		$webDir = StringUtil::stripRootDir($container->getParameter('contao.web_dir'));

		// Create the file
		File::putContent($webDir . '/share/' . $strFile . '.xml', System::getContainer()->get('contao.insert_tag.parser')->replaceInline($objFeed->$strType()));
	}

	/**
	 * Add an event to the array of active events
	 *
	 * @param CalendarEventsModel $objEvent
	 * @param integer             $intStart
	 * @param integer             $intEnd
	 * @param PageModel           $objParent
	 * @param boolean             $isRepeated
	 */
	private function addEvent($objEvent, $intStart, $intEnd, $objParent, $isRepeated=false)
	{
		if ($intEnd < time())
		{
			return; // see #3917
		}

		$intKey = date('Ymd', $intStart);
		$span = self::calculateSpan($intStart, $intEnd);
		$format = $objEvent->addTime ? 'datimFormat' : 'dateFormat';

		/** @var PageModel $objPage */
		global $objPage;

		if ($objPage instanceof PageModel)
		{
			$date = $objPage->$format;
			$dateFormat = $objPage->dateFormat;
			$timeFormat = $objPage->timeFormat;
		}
		else
		{
			// Called in the back end (see #4026)
			$date = Config::get($format);
			$dateFormat = Config::get('dateFormat');
			$timeFormat = Config::get('timeFormat');
		}

		// Add date
		if ($span > 0)
		{
			$title = Date::parse($date, $intStart) . $GLOBALS['TL_LANG']['MSC']['cal_timeSeparator'] . Date::parse($date, $intEnd);
		}
		else
		{
			$title = Date::parse($dateFormat, $intStart) . ($objEvent->addTime ? ' (' . Date::parse($timeFormat, $intStart) . (($intStart < $intEnd) ? $GLOBALS['TL_LANG']['MSC']['cal_timeSeparator'] . Date::parse($timeFormat, $intEnd) : '') . ')' : '');
		}

		// Add title and link
		$title .= ' ' . $objEvent->title;
		$link = '';

		switch ($objEvent->source)
		{
			case 'external':
				$url = $objEvent->url;

				if (Validator::isRelativeUrl($url))
				{
					$url = Environment::get('path') . '/' . $url;
				}

				$link = $url;
				break;

			case 'internal':
				if (($objTarget = $objEvent->getRelated('jumpTo')) instanceof PageModel)
				{
					/** @var PageModel $objTarget */
					$link = $objTarget->getAbsoluteUrl();
				}
				break;

			case 'article':
				if (($objArticle = ArticleModel::findByPk($objEvent->articleId)) instanceof ArticleModel && ($objPid = $objArticle->getRelated('pid')) instanceof PageModel)
				{
					/** @var PageModel $objPid */
					$link = StringUtil::ampersand($objPid->getAbsoluteUrl('/articles/' . ($objArticle->alias ?: $objArticle->id)));
				}
				break;

			default:
				$link = $objParent->getAbsoluteUrl('/' . ($objEvent->alias ?: $objEvent->id));

				break;
		}

		// Store the whole row (see #5085)
		$arrEvent = $objEvent->row();

		// Override link and title
		$arrEvent['link'] = $link;
		$arrEvent['title'] = $title;

		// Set the current start and end date
		$arrEvent['startDate'] = $intStart;
		$arrEvent['endDate'] = $intEnd;
		$arrEvent['isRepeated'] = $isRepeated;

		// Reset the enclosures (see #5685)
		$arrEvent['enclosure'] = array();
		$arrEvent['media:content'] = array();

		// Add the article image as enclosure
		if ($objEvent->addImage)
		{
			$objFile = FilesModel::findByUuid($objEvent->singleSRC);

			if ($objFile !== null)
			{
				$arrEvent['media:content'][] = $objFile->path;
			}
		}

		// Enclosures
		if ($objEvent->addEnclosure)
		{
			$arrEnclosure = StringUtil::deserialize($objEvent->enclosure, true);

			if (\is_array($arrEnclosure))
			{
				$objFile = FilesModel::findMultipleByUuids($arrEnclosure);

				if ($objFile !== null)
				{
					while ($objFile->next())
					{
						$arrEvent['enclosure'][] = $objFile->path;
					}
				}
			}
		}

		$this->arrEvents[$intKey][$intStart][] = $arrEvent;
	}

	/**
	 * Calculate the span between two timestamps in days
	 *
	 * @param integer $intStart
	 * @param integer $intEnd
	 *
	 * @return integer
	 */
	public static function calculateSpan($intStart, $intEnd)
	{
		return self::unixToJd($intEnd) - self::unixToJd($intStart);
	}

	/**
	 * Convert a UNIX timestamp to a Julian day
	 *
	 * @param integer $tstamp
	 *
	 * @return integer
	 */
	public static function unixToJd($tstamp)
	{
		list($year, $month, $day) = explode(',', date('Y,m,d', $tstamp));

		// Make year a positive number
		$year += ($year < 0 ? 4801 : 4800);

		// Adjust the start of the year
		if ($month > 2)
		{
			$month -= 3;
		}
		else
		{
			$month += 9;
			--$year;
		}

		$sdn  = floor((floor($year / 100) * 146097) / 4);
		$sdn += floor((($year % 100) * 1461) / 4);
		$sdn += floor(($month * 153 + 2) / 5);
		$sdn += $day - 32045;

		return $sdn;
	}

	/**
	 * Return the names of the existing feeds so they are not removed
	 *
	 * @return array
	 */
	public function purgeOldFeeds()
	{
		$arrFeeds = array();
		$objFeeds = CalendarFeedModel::findAll();

		if ($objFeeds !== null)
		{
			while ($objFeeds->next())
			{
				$arrFeeds[] = $objFeeds->alias ?: 'calendar' . $objFeeds->id;
			}
		}

		return $arrFeeds;
	}

	/**
	 * Return the page object with loaded details for the given page ID
	 *
	 * @param  integer        $intPageId
	 * @return PageModel|null
	 */
	private function getPageWithDetails($intPageId)
	{
		if (!isset(self::$arrPageCache[$intPageId]))
		{
			self::$arrPageCache[$intPageId] = PageModel::findWithDetails($intPageId);
		}

		return self::$arrPageCache[$intPageId];
	}

	/**
	 * Creates a sub request for the given URI.
	 */
	private function createSubRequest(string $uri, Request $request = null): Request
	{
		$cookies = null !== $request ? $request->cookies->all() : array();
		$server = null !== $request ? $request->server->all() : array();

		unset($server['HTTP_IF_MODIFIED_SINCE'], $server['HTTP_IF_NONE_MATCH']);

		$subRequest = Request::create($uri, 'get', array(), $cookies, array(), $server);

		if (null !== $request)
		{
			if ($request->get('_format'))
			{
				$subRequest->attributes->set('_format', $request->get('_format'));
			}

			if ($request->getDefaultLocale() !== $request->getLocale())
			{
				$subRequest->setLocale($request->getLocale());
			}
		}

		// Always set a session (#3856)
		$subRequest->setSession(new Session(new MockArraySessionStorage()));

		return $subRequest;
	}
}
