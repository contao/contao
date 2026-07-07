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

        $framework = $this->container->get('contao.framework');

        if (!$calendar = $framework->getAdapter(CalendarModel::class)->findById($record->pid)) {
            return null;
        }

        if (!$page = $framework->getAdapter(PageModel::class)->findById($calendar->jumpTo)) {
            return null;
        }

        return $page;
    }
}
