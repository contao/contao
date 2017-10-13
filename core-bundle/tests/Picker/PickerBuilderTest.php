<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Picker;

use Contao\BackendUser;
use Contao\CoreBundle\Picker\FilePickerProvider;
use Contao\CoreBundle\Picker\PagePickerProvider;
use Contao\CoreBundle\Picker\PickerBuilder;
use Contao\CoreBundle\Picker\PickerConfig;
use Knp\Menu\MenuFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Translation\TranslatorInterface;

class PickerBuilderTest extends TestCase
{
    /**
     * @var PickerBuilder
     */
    protected $builder;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $router = $this->createMock(RouterInterface::class);

        $router
            ->method('generate')
            ->willReturn('/_contao/picker?context=page')
        ;

        $this->builder = new PickerBuilder(new MenuFactory(), $router, new RequestStack());
    }

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf('Contao\CoreBundle\Picker\PickerBuilder', $this->builder);
    }

    public function testCreatesAPickerObject(): void
    {
        $provider = new PagePickerProvider(new MenuFactory(), $this->createMock(RouterInterface::class));
        $provider->setTokenStorage($this->mockTokenStorage());

        $this->builder->addProvider($provider);

        $this->builder->addProvider(
            new FilePickerProvider(
                new MenuFactory(),
                $this->createMock(RouterInterface::class),
                $this->createMock(TranslatorInterface::class),
                __DIR__
            )
        );

        $picker = $this->builder->create(new PickerConfig('page', ['providers' => ['pagePicker']]));

        $this->assertInstanceOf('Contao\CoreBundle\Picker\PickerInterface', $picker);

        $config = $picker->getConfig();

        $this->assertSame('page', $config->getContext());
        $this->assertSame(['providers' => ['pagePicker']], $config->getExtras());
    }

    public function testDoesNotCreateAPickerObjectIfThereAreNoProviders(): void
    {
        $this->assertNull($this->builder->create(new PickerConfig('page')));
    }

    public function testCreatesAPickerObjectFromData(): void
    {
        $provider = new PagePickerProvider(new MenuFactory(), $this->createMock(RouterInterface::class));
        $provider->setTokenStorage($this->mockTokenStorage());

        $this->builder->addProvider($provider);

        $picker = $this->builder->createFromData('H4sIAAAAAAAAA6tWSs7PK0mtKFGyUsrJzMtW0lECcooSi5WsomN1lJJLi4pS80CSBYnpqQGZydmpRUAlZYk5palAQaVaAN/dCYtAAAAA');

        $this->assertInstanceOf('Contao\CoreBundle\Picker\PickerInterface', $picker);
        $this->assertSame('link', $picker->getConfig()->getContext());
    }

    public function testDoesNotCreateAPickerObjectFromDataIfTheArgumentIsInvalid(): void
    {
        $provider = new PagePickerProvider(new MenuFactory(), $this->createMock(RouterInterface::class));
        $provider->setTokenStorage($this->mockTokenStorage());

        $this->builder->addProvider($provider);

        $this->assertNull($this->builder->createFromData('invalid'));
    }

    public function testChecksIfAContextIsSupported(): void
    {
        $provider = new PagePickerProvider(new MenuFactory(), $this->createMock(RouterInterface::class));
        $provider->setTokenStorage($this->mockTokenStorage());

        $this->builder->addProvider($provider);

        $this->assertTrue($this->builder->supportsContext('page'));
        $this->assertFalse($this->builder->supportsContext('page', ['filePicker']));
        $this->assertFalse($this->builder->supportsContext('foo'));
    }

    public function testReturnsThePickerUrl(): void
    {
        $provider = new PagePickerProvider(new MenuFactory(), $this->createMock(RouterInterface::class));
        $provider->setTokenStorage($this->mockTokenStorage());

        $this->builder->addProvider($provider);

        $this->assertSame('/_contao/picker?context=page', $this->builder->getUrl('page', [], '{{link_url::5}}'));
    }

    public function testReturnsAnEmptyPickerUrlIfTheContextIsNotSupported(): void
    {
        $this->assertSame('', $this->builder->getUrl('foo'));
    }

    /**
     * Mocks a token storage.
     *
     * @return TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockTokenStorage(): TokenStorageInterface
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

        return $tokenStorage;
    }
}
