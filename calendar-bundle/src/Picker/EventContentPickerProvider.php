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

use Contao\CoreBundle\Picker\AbstractContentPickerProvider;

class EventContentPickerProvider extends AbstractContentPickerProvider
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'eventContentPicker';
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendModule(): string
    {
        return 'calendar';
    }

    /**
     * {@inheritdoc}
     */
    protected function getParentTable(): string
    {
        return 'tl_calendar_events';
    }
}
