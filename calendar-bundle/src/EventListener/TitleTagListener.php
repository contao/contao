<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\EventListener;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\EventListener\Widget\AbstractTitleTagCallback;
use Contao\Model;
use Contao\PageModel;

#[AsCallback('tl_calendar_events', 'fields.serpPreview.eval.title_tag')]
class TitleTagListener extends AbstractTitleTagCallback
{
    protected function getPageModel(Model $record): PageModel|null
    {
        if (!$record instanceof CalendarEventsModel) {
            return null;
        }

        if (!($calendar = $record->getRelated('pid')) instanceof CalendarModel) {
            return null;
        }

        if (!($page = $calendar->getRelated('jumpTo')) instanceof PageModel) {
            return null;
        }

        return $page;
    }
}
