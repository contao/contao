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
use Contao\CoreBundle\Picker\AbstractPickerProvider;
use Contao\CoreBundle\Picker\DcaPickerProviderInterface;
use Contao\CoreBundle\Picker\PickerConfig;

class EventPickerProvider extends AbstractPickerProvider implements DcaPickerProviderInterface, FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    protected const DEFAULT_INSERTTAG = '{{event_url::%s}}';

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
        $insertTagChunks = explode('%s', $this->getInsertTag($config, self::DEFAULT_INSERTTAG), 2);

        return false !== strpos($config->getValue(), $insertTagChunks[0]);
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
            $insertTagChunks = explode('%s', $this->getInsertTag($config, self::DEFAULT_INSERTTAG), 2);

            $attributes['value'] = str_replace($insertTagChunks, '',  $config->getValue());
        }

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function convertDcaValue(PickerConfig $config, $value): string
    {
        return sprintf($this->getInsertTag($config, self::DEFAULT_INSERTTAG), $value);
    }

    /**
     * {@inheritdoc}
     */
    protected function getRouteParameters(PickerConfig $config = null): array
    {
        $params = ['do' => 'calendar'];

        if (null === $config || !$config->getValue()) {
            return $params;
        }

        $insertTagChunks = explode('%s', $this->getInsertTag($config, self::DEFAULT_INSERTTAG), 2);

        if (false === strpos($config->getValue(), $insertTagChunks[0])) {
            return $params;
        }

        $value = str_replace($insertTagChunks, '',  $config->getValue());

        if (null !== ($calendarId = $this->getCalendarId($value))) {
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
