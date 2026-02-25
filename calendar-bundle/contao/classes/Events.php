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
use Symfony\Component\Routing\Exception\ExceptionInterface;

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
	 *
	 * @deprecated Deprecated sind Contao 6.0, to be removed in Contao 7;
	 */
	protected $intTodayBegin;

	/**
	 * Today 23:59:59
	 * @var string
	 *
	 * @deprecated Deprecated sind Contao 6.0, to be removed in Contao 7;
	 */
	protected $intTodayEnd;

	/**
	 * Current events
	 * @var array
	 */
	protected $arrEvents = array();

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
		$calendarEventsGenerator = System::getContainer()->get('contao_calendar.generator.calendar_events');

		return $this->arrEvents = $calendarEventsGenerator->getAllEvents($arrCalendars, (new \DateTime())->setTimestamp($intStart), (new \DateTime())->setTimestamp($intEnd), $blnFeatured, $this->cal_noSpan, null, $this);
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
		$urlGenerator = System::getContainer()->get('contao.routing.content_url_generator');

		$jsonLd = array(
			'@type' => 'Event',
			'identifier' => '#/schema/events/' . $objEvent->id,
			'name' => $htmlDecoder->inputEncodedToPlainText($objEvent->title),
			'startDate' => $objEvent->addTime ? date('Y-m-d\TH:i:sP', $objEvent->startTime) : date('Y-m-d', $objEvent->startTime)
		);

		try
		{
			$jsonLd['url'] = $urlGenerator->generate($objEvent);
		}
		catch (ExceptionInterface)
		{
			// noop
		}

		if ($objEvent->startTime !== $objEvent->endTime)
		{
			$jsonLd['endDate'] = $objEvent->addTime ? date('Y-m-d\TH:i:sP', $objEvent->endTime) : date('Y-m-d', $objEvent->endTime);
		}

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
