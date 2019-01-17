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
use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Events;
use Contao\Input;
use Contao\PageModel;
use Contao\StringUtil;

class BreadcrumbListener
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    public function onGenerateBreadcrumb(array $items): array
    {
        $eventAlias = $this->getEventAlias();
        if (!$eventAlias) {
            return $items;
        }

        $eventModel = $this->getEventModel($eventAlias);
        if (!$eventModel) {
            return $items;
        }

        if ($GLOBALS['objPage']->requireItem) {
            return $this->overrideActiveBreadcrumbItem($items, $eventModel);
        }

        return $this->addBreadcrumbItem($items, $eventModel);
    }

    private function getEventAlias(): ?string
    {
        if (!isset($GLOBALS['objPage'])) {
            return null;
        }

        /** @var Adapter|Input $inputAdapter */
        $inputAdapter = $this->framework->getAdapter(Input::class);
        /** @var Adapter|Config $configAdapter */
        $configAdapter = $this->framework->getAdapter(Config::class);

        if ($configAdapter->get('useAutoItem')) {
            return $inputAdapter->get('auto_item');
        }

        return $inputAdapter->get('events');
    }

    private function getEventModel(string $eventAlias): ?CalendarEventsModel
    {
        /** @var Adapter|CalendarModel $repository */
        $calendarModel = $this->framework->getAdapter(CalendarModel::class)->findOneByJumpTo($GLOBALS['objPage']->id);
        if (!$calendarModel) {
            return null;
        }
        /** @var Adapter|CalendarEventsModel $repository */
        return $this->framework
            ->getAdapter(CalendarEventsModel::class)
            ->findPublishedByParentAndIdOrAlias($eventAlias, [$calendarModel->id]);
    }

    private function addBreadcrumbItem(array $items, CalendarEventsModel $eventModel): array
    {
        $currentPage = $this->getCurrentPage();

        foreach ($items as &$item) {
            $item['isActive'] = false;
        }
        unset ($item);

        $title = $this->getEventTitle($eventModel, $currentPage);
        $items[] = [
            'isRoot' => false,
            'isActive' => true,
            'href' => $this->generateEventUrl($eventModel),
            'title' => StringUtil::specialchars($title, true),
            'link' => $title,
            'data' => $currentPage->row(),
            'class' => ''
        ];

        return $items;
    }

    private function overrideActiveBreadcrumbItem(array $items, CalendarEventsModel $eventModel): array
    {
        $currentPage = $this->getCurrentPage();
        $title = $this->getEventTitle($eventModel, $currentPage);

        foreach ($items as &$item) {
            if ($item['isActive'] && $item['data']['id'] === $currentPage->id) {
                $item['title'] = StringUtil::specialchars($title, true);
                $item['link'] = $title;
                $item['href'] = $this->generateEventUrl($eventModel);

                break;
            }
        }

        return $items;
    }

    private function getCurrentPage(): PageModel
    {
        // Fetch the page again from the database as the global objPage might already have an overridden title
        /** @var Adapter|PageModel $repository */
        $repository = $this->framework->getAdapter(PageModel::class);

        return $repository->findByPk($GLOBALS['objPage']->id) ?: $GLOBALS['objPage'];
    }

    private function getEventTitle(CalendarEventsModel $eventModel, PageModel $currentPage): string
    {
        if ($eventModel->pageTitle) {
            return $eventModel->pageTitle;
        }

        if ($eventModel->title) {
            return $eventModel->title;
        }

        return $currentPage->pageTitle ?: $currentPage->title;
    }

    private function generateEventUrl(CalendarEventsModel $eventModel): string
    {
        /** @var Adapter|Events $adapter */
        $adapter = $this->framework->getAdapter(Events::class);

        return $adapter->generateEventUrl($eventModel);
    }
}
