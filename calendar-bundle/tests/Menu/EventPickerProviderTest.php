<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CalendarBundle\Tests\Menu;

use Contao\BackendUser;
use Contao\CalendarBundle\Menu\EventPickerProvider;
use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Menu\PickerMenuProviderInterface;
use Knp\Menu\MenuFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Tests the EventPickerProvider class.
 *
 * @author Leo Feyer <https:/github.com/leofeyer>
 */
class EventPickerProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PickerMenuProviderInterface
     */
    private $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->provider = $this->mockPickerProvider();
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CalendarBundle\Menu\EventPickerProvider', $this->provider);
    }

    /**
     * Tests the createMenu() method.
     */
    public function testCreateMenu()
    {
        $factory = new MenuFactory();
        $menu = $factory->createItem('foo');

        $this->provider->createMenu($menu, $factory);

        $this->assertTrue($menu->hasChildren());

        $eventPicker = $menu->getChild('eventPicker');

        $this->assertNotNull($eventPicker);
        $this->assertEquals('events', $eventPicker->getLinkAttribute('class'));
        $this->assertEquals('contao_backend:do=calendar', $eventPicker->getUri());
    }

    /**
     * Tests the supports() method.
     */
    public function testSupports()
    {
        $this->assertTrue($this->provider->supports('link'));
        $this->assertFalse($this->provider->supports('page'));
    }

    /**
     * Tests the supportsTable() method.
     */
    public function testSupportsTable()
    {
        $this->assertTrue($this->provider->supportsTable('tl_calendar_events'));
        $this->assertFalse($this->provider->supportsTable('tl_page'));
    }

    /**
     * Tests the processSelection() method.
     */
    public function testProcessSelection()
    {
        $this->assertEquals('{{event_url::2}}', $this->provider->processSelection(2));
    }

    /**
     * Tests the canHandle() method.
     */
    public function testCanHandle()
    {
        $request = new Request();
        $request->query->set('value', '{{event_url::2}}');

        $this->assertTrue($this->provider->canHandle($request));

        $request->query->set('value', 'files/test/foo.jpg');

        $this->assertFalse($this->provider->canHandle($request));

        $request->query->remove('value');

        $this->assertFalse($this->provider->canHandle($request));
    }

    /**
     * Tests the getPickerUrl() method.
     */
    public function testGetPickerUrl()
    {
        $request = new Request();
        $request->query->set('value', '{{event_url::42}}');

        $this->assertEquals(
            'contao_backend:value=42:do=calendar:table=tl_calendar_events:id=2',
            $this->provider->getPickerUrl($request)
        );

        $this->assertEquals('contao_backend:value=42:do=calendar', $this->provider->getPickerUrl($request));
        $this->assertEquals('contao_backend:value=42:do=calendar', $this->provider->getPickerUrl($request));
        $this->assertEquals('contao_backend:value=42:do=calendar', $this->provider->getPickerUrl($request));
    }

    /**
     * Mocks a picker provider.
     *
     * @return PickerMenuProviderInterface
     */
    protected function mockPickerProvider()
    {
        $router = $this->getMock(RouterInterface::class);

        $router
            ->expects($this->any())
            ->method('generate')
            ->willReturnCallback(function ($name, $params) {
                $url = $name;

                foreach ($params as $key => $value) {
                    $url .= ':'.$key.'='.$value;
                }

                return $url;
            })
        ;

        $user = $this
            ->getMockBuilder(BackendUser::class)
            ->setMethods(['hasAccess'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $user
            ->expects($this->any())
            ->method('hasAccess')
            ->willReturn(true)
        ;

        $token = $this->getMock(TokenInterface::class);

        $token
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($user)
        ;

        $tokenStorage = $this->getMock(TokenStorageInterface::class);

        $tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token)
        ;

        $request = new Request();

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $calendarModel = $this
            ->getMockBuilder(CalendarModel::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $calendarModel
            ->expects($this->any())
            ->method('__get')
            ->willReturn(2)
        ;

        $eventsModel = $this
            ->getMockBuilder(CalendarEventsModel::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $eventsModel
            ->expects($this->any())
            ->method('getRelated')
            ->willReturnOnConsecutiveCalls($calendarModel, null)
        ;

        $adapter = $this
            ->getMockBuilder(CalendarEventsModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['findById'])
            ->getMock()
        ;

        $adapter
            ->expects($this->any())
            ->method('findById')
            ->willReturnOnConsecutiveCalls($eventsModel, $eventsModel, null)
        ;

        $framework = $this
            ->getMockBuilder(ContaoFramework::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $framework
            ->expects($this->any())
            ->method('getAdapter')
            ->willReturn($adapter)
        ;

        $provider = new EventPickerProvider($router, $requestStack, $tokenStorage);
        $provider->setFramework($framework);

        return $provider;
    }
}
