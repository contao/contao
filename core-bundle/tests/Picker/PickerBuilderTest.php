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
use Contao\TestCase\ContaoTestCase;
use Knp\Menu\MenuFactory;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class PickerBuilderTest extends ContaoTestCase
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

        $this->builder = new PickerBuilder(new MenuFactory(), $router);
    }

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf('Contao\CoreBundle\Picker\PickerBuilder', $this->builder);
    }

    public function testCreatesAPickerObject(): void
    {
        $factory = new MenuFactory();
        $router = $this->createMock(RouterInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $pageProvider = new PagePickerProvider($factory, $router);
        $pageProvider->setTokenStorage($this->mockTokenStorage(BackendUser::class));

        $this->builder->addProvider($pageProvider);

        $fileProvider = new FilePickerProvider($factory, $router, $translator, __DIR__);

        $this->builder->addProvider($fileProvider);

        $config = new PickerConfig('page', ['providers' => ['pagePicker']]);
        $picker = $this->builder->create($config);

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
        $factory = new MenuFactory();
        $router = $this->createMock(RouterInterface::class);

        $provider = new PagePickerProvider($factory, $router);
        $provider->setTokenStorage($this->mockTokenStorage(BackendUser::class));

        $this->builder->addProvider($provider);

        $picker = $this->builder->createFromData('H4sIAAAAAAAAA6tWSs7PK0mtKFGyUsrJzMtW0lECcooSi5WsomN1lJJLi4pS80CSBYnpqQGZydmpRUAlZYk5palAQaVaAN/dCYtAAAAA');

        $this->assertInstanceOf('Contao\CoreBundle\Picker\PickerInterface', $picker);
        $this->assertSame('link', $picker->getConfig()->getContext());
    }

    public function testDoesNotCreateAPickerObjectFromDataIfTheArgumentIsInvalid(): void
    {
        $factory = new MenuFactory();
        $router = $this->createMock(RouterInterface::class);

        $provider = new PagePickerProvider($factory, $router);
        $provider->setTokenStorage($this->mockTokenStorage(BackendUser::class));

        $this->builder->addProvider($provider);

        $this->assertNull($this->builder->createFromData('invalid'));
    }

    public function testChecksIfAContextIsSupported(): void
    {
        $factory = new MenuFactory();
        $router = $this->createMock(RouterInterface::class);

        $provider = new PagePickerProvider($factory, $router);
        $provider->setTokenStorage($this->mockTokenStorage(BackendUser::class));

        $this->builder->addProvider($provider);

        $this->assertTrue($this->builder->supportsContext('page'));
        $this->assertFalse($this->builder->supportsContext('page', ['filePicker']));
        $this->assertFalse($this->builder->supportsContext('foo'));
    }

    public function testReturnsThePickerUrl(): void
    {
        $factory = new MenuFactory();
        $router = $this->createMock(RouterInterface::class);

        $provider = new PagePickerProvider($factory, $router);
        $provider->setTokenStorage($this->mockTokenStorage(BackendUser::class));

        $this->builder->addProvider($provider);

        $this->assertSame('/_contao/picker?context=page', $this->builder->getUrl('page', [], '{{link_url::5}}'));
    }

    public function testReturnsAnEmptyPickerUrlIfTheContextIsNotSupported(): void
    {
        $this->assertSame('', $this->builder->getUrl('foo'));
    }
}
