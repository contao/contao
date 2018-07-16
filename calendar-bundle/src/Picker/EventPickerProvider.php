<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Picker;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\CoreBundle\Picker\AbstractPickerProvider;
use Contao\CoreBundle\Picker\DcaPickerProviderInterface;
use Contao\CoreBundle\Picker\PickerConfig;

/**
 * Provides the event picker.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class EventPickerProvider extends AbstractPickerProvider implements DcaPickerProviderInterface, FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'eventPicker';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsContext($context)
    {
        return 'link' === $context && $this->getUser()->hasAccess('calendar', 'modules');
    }

    /**
     * {@inheritdoc}
     */
    public function supportsValue(PickerConfig $config)
    {
        return false !== strpos($config->getValue(), '{{event_url::');
    }

    /**
     * {@inheritdoc}
     */
    public function getDcaTable()
    {
        return 'tl_calendar_events';
    }

    /**
     * {@inheritdoc}
     */
    public function getDcaAttributes(PickerConfig $config)
    {
        $attributes = ['fieldType' => 'radio'];

        if ($source = $config->getExtra('source')) {
            $attributes['preserveRecord'] = $source;
        }

        if ($this->supportsValue($config)) {
            $attributes['value'] = str_replace(['{{event_url::', '}}'], '', $config->getValue());
        }

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function convertDcaValue(PickerConfig $config, $value)
    {
        return '{{event_url::'.$value.'}}';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRouteParameters(PickerConfig $config = null)
    {
        $params = ['do' => 'calendar'];

        if (null === $config || !$config->getValue() || false === strpos($config->getValue(), '{{event_url::')) {
            return $params;
        }

        $value = str_replace(['{{event_url::', '}}'], '', $config->getValue());

        if (null !== ($calendarId = $this->getCalendarId($value))) {
            $params['table'] = 'tl_calendar_events';
            $params['id'] = $calendarId;
        }

        return $params;
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

        if (!($calendarEventsModel = $eventAdapter->findById($id)) instanceof CalendarEventsModel) {
            return null;
        }

        if (!($calendar = $calendarEventsModel->getRelated('pid')) instanceof CalendarModel) {
            return null;
        }

        return $calendar->id;
    }
}
