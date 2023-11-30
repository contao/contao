<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Security\ContaoCorePermissions;

/**
 * Provide methods to get all events of a certain period from the database.
 *
 * @property bool $cal_noSpan
 */
abstract class Events extends Module
{
	/**
	 * Current URL
	 * @var string
	 */
	protected $strUrl;

	/**
	 * Today 00:00:00
	 * @var string
	 */
	protected $intTodayBegin;

	/**
	 * Today 23:59:59
	 * @var string
	 */
	protected $intTodayEnd;

	/**
	 * Current events
	 * @var array
	 */
	protected $arrEvents = array();

	/**
	 * URL cache array
	 * @var array
	 */
	private static $arrUrlCache = array();

	/**
	 * Sort out protected archives
	 *
	 * @param array $arrCalendars
	 *
	 * @return array
	 */
	protected function sortOutProtected($arrCalendars)
	{
		if (empty($arrCalendars) || !\is_array($arrCalendars))
		{
			return $arrCalendars;
		}

		$objCalendar = CalendarModel::findMultipleByIds($arrCalendars);
		$arrCalendars = array();

		if ($objCalendar !== null)
		{
			$security = System::getContainer()->get('security.helper');

			while ($objCalendar->next())
			{
				if ($objCalendar->protected && !$security->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, $objCalendar->groups))
				{
					continue;
				}

				$arrCalendars[] = $objCalendar->id;
			}
		}

		return $arrCalendars;
	}

	/**
	 * Get all events of a certain period
	 *
	 * @param array   $arrCalendars
	 * @param integer $intStart
	 * @param integer $intEnd
	 * @param boolean $blnFeatured
	 *
	 * @return array
	 */
	protected function getAllEvents($arrCalendars, $intStart, $intEnd, $blnFeatured = null)
	{
		if (!\is_array($arrCalendars))
		{
			return array();
		}

		// Include all events of the day, expired events will be filtered out later
		$intStart = strtotime(date('Y-m-d', $intStart) . ' 00:00:00');

		$this->arrEvents = array();

		foreach ($arrCalendars as $id)
		{
			// Get the events of the current period
			$objEvents = CalendarEventsModel::findCurrentByPid($id, $intStart, $intEnd, array('showFeatured' => $blnFeatured));

			if ($objEvents === null)
			{
				continue;
			}

			while ($objEvents->next())
			{
				$objEvent = $objEvents->current();

				$this->addEvent($objEvent, $objEvent->startTime, $objEvent->endTime, $intStart, $intEnd, $id);

				// Recurring events
				if ($objEvent->recurring)
				{
					$arrRepeat = StringUtil::deserialize($objEvent->repeatEach);

					if (!isset($arrRepeat['unit'], $arrRepeat['value']) || $arrRepeat['value'] < 1)
					{
						continue;
					}

					$count = 0;
					$intStartTime = $objEvent->startTime;
					$intEndTime = $objEvent->endTime;
					$strtotime = '+ ' . $arrRepeat['value'] . ' ' . $arrRepeat['unit'];

					while ($intEndTime < $intEnd)
					{
						if ($objEvent->recurrences > 0 && $count++ >= $objEvent->recurrences)
						{
							break;
						}

						$intStartTime = strtotime($strtotime, $intStartTime);
						$intEndTime = strtotime($strtotime, $intEndTime);

						// Stop if the upper boundary is reached (see #8445)
						if ($intStartTime === false || $intEndTime === false)
						{
							break;
						}

						// Skip events outside the scope
						if ($intEndTime < $intStart || $intStartTime > $intEnd)
						{
							continue;
						}

						$this->addEvent($objEvent, $intStartTime, $intEndTime, $intStart, $intEnd, $id);
					}
				}
			}
		}

		// Sort the array
		foreach (array_keys($this->arrEvents) as $key)
		{
			ksort($this->arrEvents[$key]);
		}

		// HOOK: modify the result set
		if (isset($GLOBALS['TL_HOOKS']['getAllEvents']) && \is_array($GLOBALS['TL_HOOKS']['getAllEvents']))
		{
			foreach ($GLOBALS['TL_HOOKS']['getAllEvents'] as $callback)
			{
				$this->arrEvents = System::importStatic($callback[0])->{$callback[1]}($this->arrEvents, $arrCalendars, $intStart, $intEnd, $this);
			}
		}

		return $this->arrEvents;
	}

	/**
	 * Add an event to the array of active events
	 *
	 * @param CalendarEventsModel $objEvents
	 * @param integer             $intStart
	 * @param integer             $intEnd
	 * @param integer             $intBegin
	 * @param integer             $intLimit
	 * @param integer             $intCalendar
	 */
	protected function addEvent($objEvents, $intStart, $intEnd, $intBegin, $intLimit, $intCalendar)
	{
		/** @var PageModel $objPage */
		global $objPage;

		$intDate = $intStart;
		$intKey = date('Ymd', $intStart);
		$strDate = Date::parse($objPage->dateFormat, $intStart);
		$strDay = $GLOBALS['TL_LANG']['DAYS'][date('w', $intStart)];
		$strMonth = $GLOBALS['TL_LANG']['MONTHS'][date('n', $intStart) - 1];
		$span = Calendar::calculateSpan($intStart, $intEnd);

		if ($span > 0)
		{
			$strDate = Date::parse($objPage->dateFormat, $intStart) . $GLOBALS['TL_LANG']['MSC']['cal_timeSeparator'] . Date::parse($objPage->dateFormat, $intEnd);
			$strDay = '';
		}

		$strTime = '';

		if ($objEvents->addTime)
		{
			if ($span > 0)
			{
				$strDate = Date::parse($objPage->datimFormat, $intStart) . $GLOBALS['TL_LANG']['MSC']['cal_timeSeparator'] . Date::parse($objPage->datimFormat, $intEnd);
			}
			elseif ($intStart == $intEnd)
			{
				$strTime = Date::parse($objPage->timeFormat, $intStart);
			}
			else
			{
				$strTime = Date::parse($objPage->timeFormat, $intStart) . $GLOBALS['TL_LANG']['MSC']['cal_timeSeparator'] . Date::parse($objPage->timeFormat, $intEnd);
			}
		}

		$until = '';
		$recurring = '';

		// Recurring event
		if ($objEvents->recurring)
		{
			$arrRange = StringUtil::deserialize($objEvents->repeatEach);

			if (isset($arrRange['unit'], $arrRange['value']))
			{
				if ($arrRange['value'] == 1)
				{
					$repeat = $GLOBALS['TL_LANG']['MSC']['cal_single_' . $arrRange['unit']];
				}
				else
				{
					$repeat = sprintf($GLOBALS['TL_LANG']['MSC']['cal_multiple_' . $arrRange['unit']], $arrRange['value']);
				}

				if ($objEvents->recurrences > 0)
				{
					$until = ' ' . sprintf($GLOBALS['TL_LANG']['MSC']['cal_until'], Date::parse($objPage->dateFormat, $objEvents->repeatEnd));
				}

				if ($objEvents->recurrences > 0 && $intEnd < time())
				{
					$recurring = sprintf($GLOBALS['TL_LANG']['MSC']['cal_repeat_ended'], $repeat, $until);
				}
				elseif ($objEvents->addTime)
				{
					$recurring = sprintf($GLOBALS['TL_LANG']['MSC']['cal_repeat'], $repeat, $until, date('Y-m-d\TH:i:sP', $intStart), $strDate . ($strTime ? ' ' . $strTime : ''));
				}
				else
				{
					$recurring = sprintf($GLOBALS['TL_LANG']['MSC']['cal_repeat'], $repeat, $until, date('Y-m-d', $intStart), $strDate);
				}
			}
		}

		// Tag the event (see #2137)
		if (System::getContainer()->has('fos_http_cache.http.symfony_response_tagger'))
		{
			$responseTagger = System::getContainer()->get('fos_http_cache.http.symfony_response_tagger');
			$responseTagger->addTags(array('contao.db.tl_calendar_events.' . $objEvents->id));
		}

		// Store raw data
		$arrEvent = $objEvents->row();

		// Overwrite some settings
		$arrEvent['date'] = $strDate;
		$arrEvent['time'] = $strTime;
		$arrEvent['datetime'] = $objEvents->addTime ? date('Y-m-d\TH:i:sP', $intStart) : date('Y-m-d', $intStart);
		$arrEvent['day'] = $strDay;
		$arrEvent['month'] = $strMonth;
		$arrEvent['parent'] = $intCalendar;
		$arrEvent['calendar'] = $objEvents->getRelated('pid');
		$arrEvent['link'] = $objEvents->title;
		$arrEvent['target'] = '';
		$arrEvent['title'] = StringUtil::specialchars($objEvents->title, true);
		$arrEvent['href'] = $this->generateEventUrl($objEvents);
		$arrEvent['class'] = $objEvents->cssClass ? ' ' . $objEvents->cssClass : '';
		$arrEvent['recurring'] = $recurring;
		$arrEvent['until'] = $until;
		$arrEvent['begin'] = $intStart;
		$arrEvent['end'] = $intEnd;
		$arrEvent['details'] = '';
		$arrEvent['hasTeaser'] = false;

		// Set open-end events to 23:59:59, so they run until the end of the day (see #4476)
		if ($intStart == $intEnd && $objEvents->addTime)
		{
			$arrEvent['endTime'] = strtotime(date('Y-m-d', $arrEvent['endTime']) . ' 23:59:59');
		}

		// Override the link target
		if ($objEvents->source == 'external' && $objEvents->target)
		{
			$arrEvent['target'] = ' target="_blank" rel="noreferrer noopener"';
		}

		// Clean the RTE output
		if ($arrEvent['teaser'])
		{
			$arrEvent['hasTeaser'] = true;
			$arrEvent['teaser'] = StringUtil::encodeEmail($arrEvent['teaser']);
		}

		// Display the "read more" button for external/article links
		if ($objEvents->source != 'default')
		{
			$arrEvent['hasDetails'] = true;
		}

		// Compile the event text
		else
		{
			$id = $objEvents->id;

			$arrEvent['details'] = function () use ($id) {
				$strDetails = '';
				$objElement = ContentModel::findPublishedByPidAndTable($id, 'tl_calendar_events');

				if ($objElement !== null)
				{
					while ($objElement->next())
					{
						$strDetails .= $this->getContentElement($objElement->current());
					}
				}

				return $strDetails;
			};

			$arrEvent['hasDetails'] = static function () use ($id) {
				return ContentModel::countPublishedByPidAndTable($id, 'tl_calendar_events') > 0;
			};
		}

		// Get today's start and end timestamp
		if ($this->intTodayBegin === null)
		{
			$this->intTodayBegin = strtotime('00:00:00');
		}

		if ($this->intTodayEnd === null)
		{
			$this->intTodayEnd = strtotime('23:59:59');
		}

		// Mark past and upcoming events (see #3692)
		if ($intEnd < $this->intTodayBegin)
		{
			$arrEvent['class'] .= ' bygone';
		}
		elseif ($intStart > $this->intTodayEnd)
		{
			$arrEvent['class'] .= ' upcoming';
		}
		else
		{
			$arrEvent['class'] .= ' current';
		}

		if ($arrEvent['featured'] == 1)
		{
			$arrEvent['class'] .= ' featured';
		}

		$this->arrEvents[$intKey][$intStart][] = $arrEvent;

		// Multi-day event
		for ($i=1; $i<=$span; $i++)
		{
			// Only show first occurrence
			if ($this->cal_noSpan)
			{
				break;
			}

			$intDate = strtotime('+1 day', $intDate);

			if ($intDate > $intLimit)
			{
				break;
			}

			$this->arrEvents[date('Ymd', $intDate)][$intDate][] = $arrEvent;
		}
	}

	/**
	 * Generate a URL and return it as string
	 *
	 * @param CalendarEventsModel $objEvent
	 * @param boolean             $blnAbsolute
	 *
	 * @return string
	 */
	public static function generateEventUrl($objEvent, $blnAbsolute=false)
	{
		$strCacheKey = 'id_' . $objEvent->id . ($blnAbsolute ? '_absolute' : '');

		// Load the URL from cache
		if (isset(self::$arrUrlCache[$strCacheKey]))
		{
			return self::$arrUrlCache[$strCacheKey];
		}

		// Initialize the cache
		self::$arrUrlCache[$strCacheKey] = null;

		switch ($objEvent->source)
		{
			// Link to an external page
			case 'external':
				if (0 === strncmp($objEvent->url, 'mailto:', 7))
				{
					self::$arrUrlCache[$strCacheKey] = StringUtil::encodeEmail($objEvent->url);
				}
				else
				{
					$url = $objEvent->url;

					if (Validator::isRelativeUrl($url))
					{
						$url = Environment::get('path') . '/' . $url;
					}

					self::$arrUrlCache[$strCacheKey] = StringUtil::ampersand($url);
				}
				break;

			// Link to an internal page
			case 'internal':
				if (($objTarget = $objEvent->getRelated('jumpTo')) instanceof PageModel)
				{
					/** @var PageModel $objTarget */
					self::$arrUrlCache[$strCacheKey] = StringUtil::ampersand($blnAbsolute ? $objTarget->getAbsoluteUrl() : $objTarget->getFrontendUrl());
				}
				break;

			// Link to an article
			case 'article':
				if (($objArticle = ArticleModel::findByPk($objEvent->articleId)) instanceof ArticleModel && ($objPid = $objArticle->getRelated('pid')) instanceof PageModel)
				{
					$params = '/articles/' . ($objArticle->alias ?: $objArticle->id);

					/** @var PageModel $objPid */
					self::$arrUrlCache[$strCacheKey] = StringUtil::ampersand($blnAbsolute ? $objPid->getAbsoluteUrl($params) : $objPid->getFrontendUrl($params));
				}
				break;
		}

		// Link to the default page
		if (self::$arrUrlCache[$strCacheKey] === null)
		{
			$objPage = PageModel::findByPk($objEvent->getRelated('pid')->jumpTo);

			if (!$objPage instanceof PageModel)
			{
				self::$arrUrlCache[$strCacheKey] = StringUtil::ampersand(Environment::get('requestUri'));
			}
			else
			{
				$params = '/' . ($objEvent->alias ?: $objEvent->id);

				self::$arrUrlCache[$strCacheKey] = StringUtil::ampersand($blnAbsolute ? $objPage->getAbsoluteUrl($params) : $objPage->getFrontendUrl($params));
			}
		}

		return self::$arrUrlCache[$strCacheKey];
	}

	/**
	 * Return the schema.org data from an event
	 *
	 * @param CalendarEventsModel $objEvent
	 *
	 * @return array
	 */
	public static function getSchemaOrgData(CalendarEventsModel $objEvent): array
	{
		$htmlDecoder = System::getContainer()->get('contao.string.html_decoder');

		$jsonLd = array(
			'@type' => 'Event',
			'identifier' => '#/schema/events/' . $objEvent->id,
			'name' => $htmlDecoder->inputEncodedToPlainText($objEvent->title),
			'url' => self::generateEventUrl($objEvent),
			'startDate' => $objEvent->addTime ? date('Y-m-d\TH:i:sP', $objEvent->startTime) : date('Y-m-d', $objEvent->startTime)
		);

		if ($objEvent->teaser)
		{
			$jsonLd['description'] = $objEvent->teaser;
		}

		if ($objEvent->location)
		{
			$jsonLd['location'] = array(
				'@type' => 'Place',
				'name' => $htmlDecoder->inputEncodedToPlainText($objEvent->location)
			);

			if ($objEvent->address)
			{
				$jsonLd['location']['address'] = array(
					'@type' => 'PostalAddress',
					'description' => $htmlDecoder->inputEncodedToPlainText($objEvent->address)
				);
			}
		}

		return $jsonLd;
	}

	/**
	 * Return the beginning and end timestamp and an error message as array
	 *
	 * @param Date   $objDate
	 * @param string $strFormat
	 *
	 * @return array
	 */
	protected function getDatesFromFormat(Date $objDate, $strFormat)
	{
		switch ($strFormat)
		{
			case 'cal_day':
				return array($objDate->dayBegin, $objDate->dayEnd, $GLOBALS['TL_LANG']['MSC']['cal_emptyDay']);

			default:
			case 'cal_month':
				return array($objDate->monthBegin, $objDate->monthEnd, $GLOBALS['TL_LANG']['MSC']['cal_emptyMonth']);

			case 'cal_year':
				return array($objDate->yearBegin, $objDate->yearEnd, $GLOBALS['TL_LANG']['MSC']['cal_emptyYear']);

			case 'cal_all': // 1970-01-01 00:00:00 - 2106-02-07 07:28:15
				return array(0, min(4294967295, PHP_INT_MAX), $GLOBALS['TL_LANG']['MSC']['cal_empty']);

			case 'next_7':
				return array(time(), strtotime('+7 days'), $GLOBALS['TL_LANG']['MSC']['cal_empty']);

			case 'next_14':
				return array(time(), strtotime('+14 days'), $GLOBALS['TL_LANG']['MSC']['cal_empty']);

			case 'next_30':
				return array(time(), strtotime('+1 month'), $GLOBALS['TL_LANG']['MSC']['cal_empty']);

			case 'next_90':
				return array(time(), strtotime('+3 months'), $GLOBALS['TL_LANG']['MSC']['cal_empty']);

			case 'next_180':
				return array(time(), strtotime('+6 months'), $GLOBALS['TL_LANG']['MSC']['cal_empty']);

			case 'next_365':
				return array(time(), strtotime('+1 year'), $GLOBALS['TL_LANG']['MSC']['cal_empty']);

			case 'next_two':
				return array(time(), strtotime('+2 years'), $GLOBALS['TL_LANG']['MSC']['cal_empty']);

			case 'next_cur_month':
				return array(time(), strtotime('last day of this month 23:59:59'), $GLOBALS['TL_LANG']['MSC']['cal_empty']);

			case 'next_cur_year':
				return array(time(), strtotime('last day of december this year 23:59:59'), $GLOBALS['TL_LANG']['MSC']['cal_empty']);

			case 'next_next_month':
				return array(strtotime('first day of next month 00:00:00'), strtotime('last day of next month 23:59:59'), $GLOBALS['TL_LANG']['MSC']['cal_empty']);

			case 'next_next_year':
				return array(strtotime('first day of january next year 00:00:00'), strtotime('last day of december next year 23:59:59'), $GLOBALS['TL_LANG']['MSC']['cal_empty']);

			case 'next_all': // 2106-02-07 07:28:15
				return array(time(), min(4294967295, PHP_INT_MAX), $GLOBALS['TL_LANG']['MSC']['cal_empty']);

			case 'past_7':
				return array(strtotime('-7 days'), time(), $GLOBALS['TL_LANG']['MSC']['cal_empty']);

			case 'past_14':
				return array(strtotime('-14 days'), time(), $GLOBALS['TL_LANG']['MSC']['cal_empty']);

			case 'past_30':
				return array(strtotime('-1 month'), time(), $GLOBALS['TL_LANG']['MSC']['cal_empty']);

			case 'past_90':
				return array(strtotime('-3 months'), time(), $GLOBALS['TL_LANG']['MSC']['cal_empty']);

			case 'past_180':
				return array(strtotime('-6 months'), time(), $GLOBALS['TL_LANG']['MSC']['cal_empty']);

			case 'past_365':
				return array(strtotime('-1 year'), time(), $GLOBALS['TL_LANG']['MSC']['cal_empty']);

			case 'past_two':
				return array(strtotime('-2 years'), time(), $GLOBALS['TL_LANG']['MSC']['cal_empty']);

			case 'past_cur_month':
				return array(strtotime('first day of this month 00:00:00'), time(), $GLOBALS['TL_LANG']['MSC']['cal_empty']);

			case 'past_cur_year':
				return array(strtotime('first day of january this year 00:00:00'), time(), $GLOBALS['TL_LANG']['MSC']['cal_empty']);

			case 'past_prev_month':
				return array(strtotime('first day of last month 00:00:00'), strtotime('last day of last month 23:59:59'), $GLOBALS['TL_LANG']['MSC']['cal_empty']);

			case 'past_prev_year':
				return array(strtotime('first day of january last year 00:00:00'), strtotime('last day of december last year 23:59:59'), $GLOBALS['TL_LANG']['MSC']['cal_empty']);

			case 'past_all': // 1970-01-01 00:00:00
				return array(0, time(), $GLOBALS['TL_LANG']['MSC']['cal_empty']);
		}
	}
}
