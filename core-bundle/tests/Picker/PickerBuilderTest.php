<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Picker;

use Contao\CoreBundle\Picker\FilePickerProvider;
use Contao\CoreBundle\Picker\PagePickerProvider;
use Contao\CoreBundle\Picker\PickerBuilder;
use Contao\CoreBundle\Picker\PickerConfig;
use Contao\TestCase\ContaoTestCase;
use Knp\Menu\MenuFactory;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PickerBuilderTest extends ContaoTestCase
{
    private PickerBuilder $builder;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $router = $this->createMock(RouterInterface::class);
        $router
            ->method('generate')
            ->willReturn('/contao/picker?context=page')
        ;

        $this->builder = new PickerBuilder(new MenuFactory(), $router);
    }

    public function testCreatesAPickerObject(): void
    {
        $factory = new MenuFactory();
        $router = $this->createMock(RouterInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $pageProvider = new PagePickerProvider($factory, $router, $translator, $this->getSecurityHelper());

        $this->builder->addProvider($pageProvider);

        $fileProvider = new FilePickerProvider($factory, $router, $translator, $this->getSecurityHelper(), __DIR__);

        $this->builder->addProvider($fileProvider);

        $config = new PickerConfig('page', ['providers' => ['pagePicker']]);
        $picker = $this->builder->create($config);

        $this->assertNotNull($picker);

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
        $translator = $this->createMock(TranslatorInterface::class);

        $provider = new PagePickerProvider($factory, $router, $translator, $this->getSecurityHelper());

        $this->builder->addProvider($provider);

        $picker = $this->builder->createFromData('H4sIAAAAAAAAA6tWSs7PK0mtKFGyUsrJzMtW0lECcooSi5WsomN1lJJLi4pS80CSBYnpqQGZydmpRUAlZYk5palAQaVaAN/dCYtAAAAA');

        $this->assertNotNull($picker);
        $this->assertSame('link', $picker->getConfig()->getContext());
    }

    public function testDoesNotCreateAPickerObjectFromDataIfTheArgumentIsInvalid(): void
    {
        $factory = new MenuFactory();
        $router = $this->createMock(RouterInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $provider = new PagePickerProvider($factory, $router, $translator, $this->getSecurityHelper());

        $this->builder->addProvider($provider);

        $this->assertNull($this->builder->createFromData('invalid'));
    }

    public function testChecksIfAContextIsSupported(): void
    {
        $factory = new MenuFactory();
        $router = $this->createMock(RouterInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $provider = new PagePickerProvider($factory, $router, $translator, $this->getSecurityHelper());

        $this->builder->addProvider($provider);

        $this->assertTrue($this->builder->supportsContext('page'));
        $this->assertFalse($this->builder->supportsContext('page', ['filePicker']));
        $this->assertFalse($this->builder->supportsContext('foo'));
    }

    public function testReturnsThePickerUrl(): void
    {
        $factory = new MenuFactory();
        $router = $this->createMock(RouterInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $provider = new PagePickerProvider($factory, $router, $translator, $this->getSecurityHelper());

        $this->builder->addProvider($provider);

        $this->assertSame('/contao/picker?context=page', $this->builder->getUrl('page', [], '{{link_url::5}}'));
    }

    public function testReturnsAnEmptyPickerUrlIfTheContextIsNotSupported(): void
    {
        $this->assertSame('', $this->builder->getUrl('foo'));
    }

    private function getSecurityHelper(): Security
    {
        $security = $this->createMock(Security::class);
        $security
            ->method('isGranted')
            ->willReturn(true)
        ;

        return $security;
    }
}
