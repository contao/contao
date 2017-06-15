<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CalendarBundle\Menu;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\CoreBundle\Menu\AbstractMenuProvider;
use Contao\CoreBundle\Menu\PickerMenuProviderInterface;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the event picker.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class EventPickerProvider extends AbstractMenuProvider implements PickerMenuProviderInterface, FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function supports($context)
    {
        return 'link' === $context;
    }

    /**
     * {@inheritdoc}
     */
    public function createMenu(ItemInterface $menu, FactoryInterface $factory)
    {
        $user = $this->getUser();

        if ($user->hasAccess('calendar', 'modules')) {
            $this->addMenuItem($menu, $factory, 'calendar', 'eventPicker', 'events');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTable($table)
    {
        return 'tl_calendar_events' === $table;
    }

    /**
     * {@inheritdoc}
     */
    public function processSelection($value)
    {
        return sprintf('{{event_url::%s}}', $value);
    }

    /**
     * {@inheritdoc}
     */
    public function canHandle(Request $request)
    {
        return $request->query->has('value') && false !== strpos($request->query->get('value'), '{{event_url::');
    }

    /**
     * {@inheritdoc}
     */
    public function getPickerUrl(Request $request)
    {
        $params = $request->query->all();
        $params['do'] = 'calendar';
        $params['value'] = str_replace(['{{event_url::', '}}'], '', $params['value']);

        if (null !== ($calendarId = $this->getCalendarId($params['value']))) {
            $params['table'] = 'tl_calendar_events';
            $params['id'] = $calendarId;
        }

        return $this->route('contao_backend', $params);
    }

    /**
     * Returns the calendar ID.
     *
     * @param int $id
     *
     * @return int|null
     */
    private function getCalendarId($id)
    {
        /** @var CalendarEventsModel $eventAdapter */
        $eventAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

        if (!(($calendarEventsModel = $eventAdapter->findById($id)) instanceof CalendarEventsModel)) {
            return null;
        }

        if (!(($calendar = $calendarEventsModel->getRelated('pid')) instanceof CalendarModel)) {
            return null;
        }

        return $calendar->id;
    }
}
