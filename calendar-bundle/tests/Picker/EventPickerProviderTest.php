<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Tests\Picker;

use Contao\CalendarBundle\Picker\EventPickerProvider;
use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\Picker\PickerConfig;
use Contao\TestCase\ContaoTestCase;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuItem;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class EventPickerProviderTest extends ContaoTestCase
{
    public function testCreatesTheMenuItem(): void
    {
        $config = json_encode(
            [
                'context' => 'link',
                'extras' => [],
                'current' => 'eventPicker',
                'value' => '',
            ],
            JSON_THROW_ON_ERROR,
        );

        if (\function_exists('gzencode') && false !== ($encoded = @gzencode($config))) {
            $config = $encoded;
        }

        $picker = $this->getPicker();
        $item = $picker->createMenuItem(new PickerConfig('link', [], '', 'eventPicker'));
        $uri = 'contao_backend?do=calendar&popup=1&picker='.strtr(base64_encode($config), '+/=', '-_,');

        $this->assertSame('Event picker', $item->getLabel());
        $this->assertSame(['class' => 'eventPicker'], $item->getLinkAttributes());
        $this->assertTrue($item->isCurrent());
        $this->assertSame($uri, $item->getUri());
    }

    public function testChecksIfAMenuItemIsCurrent(): void
    {
        $picker = $this->getPicker();

        $this->assertTrue($picker->isCurrent(new PickerConfig('link', [], '', 'eventPicker')));
        $this->assertFalse($picker->isCurrent(new PickerConfig('link', [], '', 'filePicker')));
    }

    public function testReturnsTheCorrectName(): void
    {
        $picker = $this->getPicker();

        $this->assertSame('eventPicker', $picker->getName());
    }

    public function testChecksIfAContextIsSupported(): void
    {
        $picker = $this->getPicker(true);

        $this->assertTrue($picker->supportsContext('link'));
        $this->assertFalse($picker->supportsContext('file'));
    }

    public function testChecksIfModuleAccessIsGranted(): void
    {
        $picker = $this->getPicker(false);

        $this->assertFalse($picker->supportsContext('link'));
    }

    public function testChecksIfAValueIsSupported(): void
    {
        $picker = $this->getPicker();

        $this->assertTrue($picker->supportsValue(new PickerConfig('link', [], '{{event_url::5}}')));
        $this->assertFalse($picker->supportsValue(new PickerConfig('link', [], '{{link_url::5}}')));
    }

    public function testReturnsTheDcaTable(): void
    {
        $picker = $this->getPicker();

        $this->assertSame('tl_calendar_events', $picker->getDcaTable());
    }

    public function testReturnsTheDcaAttributes(): void
    {
        $picker = $this->getPicker();
        $extra = ['source' => 'tl_calendar_events.2'];

        $this->assertSame(
            [
                'fieldType' => 'radio',
                'value' => '5',
                'flags' => ['urlattr'],
            ],
            $picker->getDcaAttributes(new PickerConfig('link', $extra, '{{event_url::5|urlattr}}')),
        );

        $this->assertSame(
            [
                'fieldType' => 'radio',
            ],
            $picker->getDcaAttributes(new PickerConfig('link', $extra, '{{link_url::5}}')),
        );
    }

    public function testConvertsTheDcaValue(): void
    {
        $picker = $this->getPicker();

        $this->assertSame('{{event_url::5}}', $picker->convertDcaValue(new PickerConfig('link'), 5));
    }

    public function testConvertsTheDcaValueWithACustomInsertTag(): void
    {
        $picker = $this->getPicker();

        $this->assertSame(
            '{{event_title::5}}',
            $picker->convertDcaValue(new PickerConfig('link', ['insertTag' => '{{event_title::%s}}']), 5),
        );
    }

    public function testAddsTableAndIdIfThereIsAValue(): void
    {
        $calendarModel = $this->mockClassWithProperties(CalendarModel::class);
        $calendarModel->id = 1;

        $calendarEvents = $this->createMock(CalendarEventsModel::class);
        $calendarEvents
            ->expects($this->once())
            ->method('getRelated')
            ->with('pid')
            ->willReturn($calendarModel)
        ;

        $config = new PickerConfig('link', [], '{{event_url::1}}', 'eventPicker');

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $calendarEvents]),
        ];

        $picker = $this->getPicker();
        $picker->setFramework($this->mockContaoFramework($adapters));

        $method = new \ReflectionMethod(EventPickerProvider::class, 'getRouteParameters');
        $params = $method->invokeArgs($picker, [$config]);

        $this->assertSame('calendar', $params['do']);
        $this->assertSame('tl_calendar_events', $params['table']);
        $this->assertSame(1, $params['id']);
    }

    public function testDoesNotAddTableAndIdIfThereIsNoEventsModel(): void
    {
        $config = new PickerConfig('link', [], '{{event_url::1}}', 'eventPicker');

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => null]),
        ];

        $picker = $this->getPicker();
        $picker->setFramework($this->mockContaoFramework($adapters));

        $method = new \ReflectionMethod(EventPickerProvider::class, 'getRouteParameters');
        $params = $method->invokeArgs($picker, [$config]);

        $this->assertSame('calendar', $params['do']);
        $this->assertArrayNotHasKey('tl_calendar_events', $params);
        $this->assertArrayNotHasKey('id', $params);
    }

    public function testDoesNotAddTableAndIdIfThereIsNoCalendarModel(): void
    {
        $calendarEvents = $this->createMock(CalendarEventsModel::class);
        $calendarEvents
            ->expects($this->once())
            ->method('getRelated')
            ->with('pid')
            ->willReturn(null)
        ;

        $config = new PickerConfig('link', [], '{{event_url::1}}', 'eventPicker');

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $calendarEvents]),
        ];

        $picker = $this->getPicker();
        $picker->setFramework($this->mockContaoFramework($adapters));

        $method = new \ReflectionMethod(EventPickerProvider::class, 'getRouteParameters');
        $params = $method->invokeArgs($picker, [$config]);

        $this->assertSame('calendar', $params['do']);
        $this->assertArrayNotHasKey('tl_calendar_events', $params);
        $this->assertArrayNotHasKey('id', $params);
    }

    private function getPicker(bool|null $accessGranted = null): EventPickerProvider
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects(null === $accessGranted ? $this->never() : $this->once())
            ->method('isGranted')
            ->willReturn($accessGranted ?? false)
        ;

        $menuFactory = $this->createMock(FactoryInterface::class);
        $menuFactory
            ->method('createItem')
            ->willReturnCallback(
                static function (string $name, array $data) use ($menuFactory): ItemInterface {
                    $item = new MenuItem($name, $menuFactory);
                    $item->setLabel($data['label']);
                    $item->setLinkAttributes($data['linkAttributes']);
                    $item->setCurrent($data['current']);
                    $item->setUri($data['uri']);

                    return $item;
                },
            )
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->method('generate')
            ->willReturnCallback(static fn (string $name, array $params): string => $name.'?'.http_build_query($params))
        ;

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturn('Event picker')
        ;

        return new EventPickerProvider($menuFactory, $router, $translator, $security);
    }
}
