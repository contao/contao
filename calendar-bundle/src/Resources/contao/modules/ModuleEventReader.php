<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Patchwork\Utf8;


/**
 * Front end module "event reader".
 *
 * @property Comments $Comments
 * @property string   $com_template
 * @property string   $cal_template
 * @property array    $cal_calendar
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ModuleEventReader extends \Events
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_eventreader';


	/**
	 * Display a wildcard in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			/** @var BackendTemplate|object $objTemplate */
			$objTemplate = new \BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['eventreader'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		// Set the item from the auto_item parameter
		if (!isset($_GET['events']) && \Config::get('useAutoItem') && isset($_GET['auto_item']))
		{
			\Input::setGet('events', \Input::get('auto_item'));
		}

		// Do not index or cache the page if no event has been specified
		if (!\Input::get('events'))
		{
			/** @var PageModel $objPage */
			global $objPage;

			$objPage->noSearch = 1;
			$objPage->cache = 0;

			return '';
		}

		$this->cal_calendar = $this->sortOutProtected(\StringUtil::deserialize($this->cal_calendar));

		// Do not index or cache the page if there are no calendars
		if (!is_array($this->cal_calendar) || empty($this->cal_calendar))
		{
			/** @var PageModel $objPage */
			global $objPage;

			$objPage->noSearch = 1;
			$objPage->cache = 0;

			return '';
		}

		return parent::generate();
	}


	/**
	 * Generate the module
	 */
	protected function compile()
	{
		/** @var PageModel $objPage */
		global $objPage;

		$this->Template->event = '';
		$this->Template->referer = 'javascript:history.go(-1)';
		$this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];

		// Get the current event
		$objEvent = \CalendarEventsModel::findPublishedByParentAndIdOrAlias(\Input::get('events'), $this->cal_calendar);

		if (null === $objEvent)
		{
			throw new PageNotFoundException('Page not found: ' . \Environment::get('uri'));
		}

		// Overwrite the page title (see #2853 and #4955)
		if ($objEvent->title != '')
		{
			$objPage->pageTitle = strip_tags(\StringUtil::stripInsertTags($objEvent->title));
		}

		// Overwrite the page description
		if ($objEvent->teaser != '')
		{
			$objPage->description = $this->prepareMetaDescription($objEvent->teaser);
		}

		$intStartTime = $objEvent->startTime;
		$intEndTime = $objEvent->endTime;
		$span = \Calendar::calculateSpan($intStartTime, $intEndTime);

		// Do not show dates in the past if the event is recurring (see #923)
		if ($objEvent->recurring)
		{
			$arrRange = \StringUtil::deserialize($objEvent->repeatEach);

			if (is_array($arrRange) && isset($arrRange['unit']) && isset($arrRange['value']))
			{
				while ($intStartTime < time() && $intEndTime < $objEvent->repeatEnd)
				{
					$intStartTime = strtotime('+' . $arrRange['value'] . ' ' . $arrRange['unit'], $intStartTime);
					$intEndTime = strtotime('+' . $arrRange['value'] . ' ' . $arrRange['unit'], $intEndTime);
				}
			}
		}

		$strDate = \Date::parse($objPage->dateFormat, $intStartTime);

		if ($span > 0)
		{
			$strDate = \Date::parse($objPage->dateFormat, $intStartTime) . ' – ' . \Date::parse($objPage->dateFormat, $intEndTime);
		}

		$strTime = '';

		if ($objEvent->addTime)
		{
			if ($span > 0)
			{
				$strDate = \Date::parse($objPage->datimFormat, $intStartTime) . ' – ' . \Date::parse($objPage->datimFormat, $intEndTime);
			}
			elseif ($intStartTime == $intEndTime)
			{
				$strTime = \Date::parse($objPage->timeFormat, $intStartTime);
			}
			else
			{
				$strTime = \Date::parse($objPage->timeFormat, $intStartTime) . ' – ' . \Date::parse($objPage->timeFormat, $intEndTime);
			}
		}

		$until = '';
		$recurring = '';

		// Recurring event
		if ($objEvent->recurring)
		{
			$arrRange = \StringUtil::deserialize($objEvent->repeatEach);

			if (is_array($arrRange) && isset($arrRange['unit']) && isset($arrRange['value']))
			{
				$strKey = 'cal_' . $arrRange['unit'];
				$recurring = sprintf($GLOBALS['TL_LANG']['MSC'][$strKey], $arrRange['value']);

				if ($objEvent->recurrences > 0)
				{
					$until = sprintf($GLOBALS['TL_LANG']['MSC']['cal_until'], \Date::parse($objPage->dateFormat, $objEvent->repeatEnd));
				}
			}
		}

		/** @var FrontendTemplate|object $objTemplate */
		$objTemplate = new \FrontendTemplate($this->cal_template);
		$objTemplate->setData($objEvent->row());

		$objTemplate->date = $strDate;
		$objTemplate->time = $strTime;
		$objTemplate->datetime = $objEvent->addTime ? date('Y-m-d\TH:i:sP', $intStartTime) : date('Y-m-d', $intStartTime);
		$objTemplate->begin = $intStartTime;
		$objTemplate->end = $intEndTime;
		$objTemplate->class = ($objEvent->cssClass != '') ? ' ' . $objEvent->cssClass : '';
		$objTemplate->recurring = $recurring;
		$objTemplate->until = $until;
		$objTemplate->locationLabel = $GLOBALS['TL_LANG']['MSC']['location'];
		$objTemplate->details = '';
		$objTemplate->hasDetails = false;
		$objTemplate->hasTeaser = false;

		// Clean the RTE output
		if ($objEvent->teaser != '')
		{
			$objTemplate->hasTeaser = true;
			$objTemplate->teaser = \StringUtil::toHtml5($objEvent->teaser);
			$objTemplate->teaser = \StringUtil::encodeEmail($objTemplate->teaser);
		}

		// Display the "read more" button for external/article links
		if ($objEvent->source != 'default')
		{
			$objTemplate->details = true;
			$objTemplate->hasDetails = true;
		}

		// Compile the event text
		else
		{
			$id = $objEvent->id;

			$objTemplate->details = function () use ($id)
			{
				$strDetails = '';
				$objElement = \ContentModel::findPublishedByPidAndTable($id, 'tl_calendar_events');

				if ($objElement !== null)
				{
					while ($objElement->next())
					{
						$strDetails .= $this->getContentElement($objElement->current());
					}
				}

				return $strDetails;
			};

			$objTemplate->hasDetails = (\ContentModel::countPublishedByPidAndTable($id, 'tl_calendar_events') > 0);
		}

		$objTemplate->addImage = false;

		// Add an image
		if ($objEvent->addImage && $objEvent->singleSRC != '')
		{
			$objModel = \FilesModel::findByUuid($objEvent->singleSRC);

			if ($objModel !== null && is_file(TL_ROOT . '/' . $objModel->path))
			{
				// Do not override the field now that we have a model registry (see #6303)
				$arrEvent = $objEvent->row();

				// Override the default image size
				if ($this->imgSize != '')
				{
					$size = \StringUtil::deserialize($this->imgSize);

					if ($size[0] > 0 || $size[1] > 0 || is_numeric($size[2]))
					{
						$arrEvent['size'] = $this->imgSize;
					}
				}

				$arrEvent['singleSRC'] = $objModel->path;
				$this->addImageToTemplate($objTemplate, $arrEvent);
			}
		}

		$objTemplate->enclosure = array();

		// Add enclosures
		if ($objEvent->addEnclosure)
		{
			$this->addEnclosuresToTemplate($objTemplate, $objEvent->row());
		}

		$this->Template->event = $objTemplate->parse();

		$bundles = \System::getContainer()->getParameter('kernel.bundles');

		// HOOK: comments extension required
		if ($objEvent->noComments || !isset($bundles['ContaoCommentsBundle']))
		{
			$this->Template->allowComments = false;

			return;
		}

		/** @var CalendarModel $objCalendar */
		$objCalendar = $objEvent->getRelated('pid');
		$this->Template->allowComments = $objCalendar->allowComments;

		// Comments are not allowed
		if (!$objCalendar->allowComments)
		{
			return;
		}

		// Adjust the comments headline level
		$intHl = min(intval(str_replace('h', '', $this->hl)), 5);
		$this->Template->hlc = 'h' . ($intHl + 1);

		$this->import('Comments');
		$arrNotifies = array();

		// Notify the system administrator
		if ($objCalendar->notify != 'notify_author')
		{
			$arrNotifies[] = $GLOBALS['TL_ADMIN_EMAIL'];
		}

		// Notify the author
		if ($objCalendar->notify != 'notify_admin')
		{
			/** @var UserModel $objAuthor */
			if (($objAuthor = $objEvent->getRelated('author')) instanceof UserModel && $objAuthor->email != '')
			{
				$arrNotifies[] = $objAuthor->email;
			}
		}

		$objConfig = new \stdClass();

		$objConfig->perPage = $objCalendar->perPage;
		$objConfig->order = $objCalendar->sortOrder;
		$objConfig->template = $this->com_template;
		$objConfig->requireLogin = $objCalendar->requireLogin;
		$objConfig->disableCaptcha = $objCalendar->disableCaptcha;
		$objConfig->bbcode = $objCalendar->bbcode;
		$objConfig->moderate = $objCalendar->moderate;

		$this->Comments->addCommentsToTemplate($this->Template, $objConfig, 'tl_calendar_events', $objEvent->id, $arrNotifies);
	}
}
