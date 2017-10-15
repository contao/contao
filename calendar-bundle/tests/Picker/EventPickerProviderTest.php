<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CalendarBundle\Tests\Picker;

use Contao\BackendUser;
use Contao\CalendarBundle\Picker\EventPickerProvider;
use Contao\CoreBundle\Picker\PickerConfig;
use Knp\Menu\FactoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Translation\TranslatorInterface;

class EventPickerProviderTest extends TestCase
{
    /**
     * @var EventPickerProvider
     */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $menuFactory = $this->createMock(FactoryInterface::class);

        $menuFactory
            ->method('createItem')
            ->willReturnArgument(1)
        ;

        $router = $this->createMock(RouterInterface::class);

        $router
            ->method('generate')
            ->willReturnCallback(
                function (string $name, array $params): string {
                    return $name.'?'.http_build_query($params);
                }
            )
        ;

        $translator = $this->createMock(TranslatorInterface::class);

        $translator
            ->method('trans')
            ->willReturn('Event picker')
        ;

        $this->provider = new EventPickerProvider($menuFactory, $router, $translator);
    }

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf('Contao\CalendarBundle\Picker\EventPickerProvider', $this->provider);
    }

    public function testCreatesTheMenuItem(): void
    {
        $picker = json_encode([
            'context' => 'link',
            'extras' => [],
            'current' => 'eventPicker',
            'value' => '',
        ]);

        if (\function_exists('gzencode') && false !== ($encoded = @gzencode($picker))) {
            $picker = $encoded;
        }

        $this->assertSame(
            [
                'label' => 'Event picker',
                'linkAttributes' => ['class' => 'eventPicker'],
                'current' => true,
                'uri' => 'contao_backend?do=calendar&popup=1&picker='.strtr(base64_encode($picker), '+/=', '-_,'),
            ], $this->provider->createMenuItem(new PickerConfig('link', [], '', 'eventPicker'))
        );
    }

    public function testChecksIfAMenuItemIsCurrent(): void
    {
        $this->assertTrue($this->provider->isCurrent(new PickerConfig('link', [], '', 'eventPicker')));
        $this->assertFalse($this->provider->isCurrent(new PickerConfig('link', [], '', 'filePicker')));
    }

    public function testReturnsTheCorrectName(): void
    {
        $this->assertSame('eventPicker', $this->provider->getName());
    }

    public function testChecksIfAContextIsSupported(): void
    {
        $user = $this
            ->getMockBuilder(BackendUser::class)
            ->disableOriginalConstructor()
            ->setMethods(['hasAccess'])
            ->getMock()
        ;

        $user
            ->method('hasAccess')
            ->willReturn(true)
        ;

        $token = $this->createMock(TokenInterface::class);

        $token
            ->method('getUser')
            ->willReturn($user)
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage
            ->method('getToken')
            ->willReturn($token)
        ;

        $this->provider->setTokenStorage($tokenStorage);

        $this->assertTrue($this->provider->supportsContext('link'));
        $this->assertFalse($this->provider->supportsContext('file'));
    }

    public function testFailsToCheckTheContextIfThereIsNoTokenStorage(): void
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('No token storage provided');

        $this->provider->supportsContext('link');
    }

    public function testFailsToCheckTheContextIfThereIsNoToken(): void
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage
            ->method('getToken')
            ->willReturn(null)
        ;

        $this->provider->setTokenStorage($tokenStorage);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('No token provided');

        $this->provider->supportsContext('link');
    }

    public function testFailsToCheckTheContextIfThereIsNoUser(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $token
            ->method('getUser')
            ->willReturn(null)
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage
            ->method('getToken')
            ->willReturn($token)
        ;

        $this->provider->setTokenStorage($tokenStorage);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('The token does not contain a back end user object');

        $this->provider->supportsContext('link');
    }

    public function testChecksIfAValueIsSupported(): void
    {
        $this->assertTrue($this->provider->supportsValue(new PickerConfig('link', [], '{{event_url::5}}')));
        $this->assertFalse($this->provider->supportsValue(new PickerConfig('link', [], '{{link_url::5}}')));
    }

    public function testReturnsTheDcaTable(): void
    {
        $this->assertSame('tl_calendar_events', $this->provider->getDcaTable());
    }

    public function testReturnsTheDcaAttributes(): void
    {
        $this->assertSame(
            [
                'fieldType' => 'radio',
                'value' => '5',
            ],
            $this->provider->getDcaAttributes(new PickerConfig('link', [], '{{event_url::5}}'))
        );

        $this->assertSame(
            ['fieldType' => 'radio'],
            $this->provider->getDcaAttributes(new PickerConfig('link', [], '{{link_url::5}}'))
        );
    }

    public function testConvertsTheDcaValue(): void
    {
        $this->assertSame('{{event_url::5}}', $this->provider->convertDcaValue(new PickerConfig('link'), 5));
    }
}
