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
use Knp\Menu\FactoryInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class EventPickerProvider extends AbstractInsertTagPickerProvider implements DcaPickerProviderInterface, FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    /**
     * @internal
     */
    public function __construct(FactoryInterface $menuFactory, RouterInterface $router, TranslatorInterface|null $translator, private Security $security)
    {
        parent::__construct($menuFactory, $router, $translator);
    }

    public function getName(): string
    {
        return 'eventPicker';
    }

    public function supportsContext($context): bool
    {
        return 'link' === $context && $this->security->isGranted('contao_user.modules', 'calendar');
    }

    public function supportsValue(PickerConfig $config): bool
    {
        return $this->isMatchingInsertTag($config);
    }

    public function getDcaTable(PickerConfig|null $config = null): string
    {
        return 'tl_calendar_events';
    }

    public function getDcaAttributes(PickerConfig $config): array
    {
        $attributes = ['fieldType' => 'radio'];

        if ($this->supportsValue($config)) {
            $attributes['value'] = $this->getInsertTagValue($config);

            if ($flags = $this->getInsertTagFlags($config)) {
                $attributes['flags'] = $flags;
            }
        }

        return $attributes;
    }

    public function convertDcaValue(PickerConfig $config, $value): string
    {
        return sprintf($this->getInsertTag($config), $value);
    }

    protected function getRouteParameters(PickerConfig|null $config = null): array
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

    protected function getDefaultInsertTag(): string
    {
        return '{{event_url::%s}}';
    }

    private function getCalendarId(int|string $id): int|null
    {
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
