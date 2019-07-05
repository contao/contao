<?php

declare(strict_types=1);

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
use Contao\CoreBundle\Picker\AbstractInsertTagPickerProvider;
use Contao\CoreBundle\Picker\DcaPickerProviderInterface;
use Contao\CoreBundle\Picker\PickerConfig;

class EventPickerProvider extends AbstractInsertTagPickerProvider implements DcaPickerProviderInterface, FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    protected const INSERTTAG = '{{event_url::%s}}';

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'eventPicker';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsContext($context): bool
    {
        return 'link' === $context && $this->getUser()->hasAccess('calendar', 'modules');
    }

    /**
     * {@inheritdoc}
     */
    public function supportsValue(PickerConfig $config): bool
    {
        return $this->isMatchingInsertTag($config);
    }

    /**
     * {@inheritdoc}
     */
    public function getDcaTable(): string
    {
        return 'tl_calendar_events';
    }

    /**
     * {@inheritdoc}
     */
    public function getDcaAttributes(PickerConfig $config): array
    {
        $attributes = ['fieldType' => 'radio'];

        if ($source = $config->getExtra('source')) {
            $attributes['preserveRecord'] = $source;
        }

        if ($this->supportsValue($config)) {
            $attributes['value'] = $this->getInsertTagValue($config);
        }

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function convertDcaValue(PickerConfig $config, $value): string
    {
        return sprintf($this->getInsertTag($config), $value);
    }

    /**
     * {@inheritdoc}
     */
    protected function getRouteParameters(PickerConfig $config = null): array
    {
        $params = ['do' => 'calendar'];

        if (null === $config || !$config->getValue() || !$this->supportsValue($config)) {
            return $params;
        }

        if (null !== ($calendarId = $this->getCalendarId($this->getInsertTagValue($config)))) {
            $params['table'] = 'tl_calendar_events';
            $params['id'] = $calendarId;
        }

        return $params;
    }

    /**
     * @param int|string $id
     */
    private function getCalendarId($id): ?int
    {
        /** @var CalendarEventsModel $eventAdapter */
        $eventAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

        if (!($calendarEventsModel = $eventAdapter->findById($id)) instanceof CalendarEventsModel) {
            return null;
        }

        if (!($calendar = $calendarEventsModel->getRelated('pid')) instanceof CalendarModel) {
            return null;
        }

        return (int) $calendar->id;
    }
}
