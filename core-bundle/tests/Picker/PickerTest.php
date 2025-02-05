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

use Contao\CoreBundle\Picker\PagePickerProvider;
use Contao\CoreBundle\Picker\Picker;
use Contao\CoreBundle\Picker\PickerConfig;
use Knp\Menu\MenuFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PickerTest extends TestCase
{
    private Picker $picker;

    protected function setUp(): void
    {
        parent::setUp();

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturn('Page picker')
        ;

        $factory = new MenuFactory();
        $router = $this->createMock(RouterInterface::class);
        $provider = new PagePickerProvider($factory, $router, $translator, $this->getSecurityHelper());
        $config = new PickerConfig('page', [], 5, 'pagePicker');

        $this->picker = new Picker($factory, [$provider], $config);
    }

    public function testReturnsTheConfiguration(): void
    {
        $this->assertSame('page', $this->picker->getConfig()->getContext());
    }

    public function testReturnsTheMenu(): void
    {
        $menu = $this->picker->getMenu();

        $this->assertSame('picker', $menu->getName());
        $this->assertCount(1, $menu);

        $pagePicker = $menu->getChild('pagePicker');

        $this->assertNotNull($pagePicker);
        $this->assertTrue($pagePicker->isCurrent());
        $this->assertSame('Page picker', $pagePicker->getLabel());

        $this->assertSame($menu, $this->picker->getMenu());
    }

    public function testReturnsTheCurrentProvider(): void
    {
        $provider = $this->picker->getCurrentProvider();

        $this->assertNotNull($provider);
        $this->assertSame('pagePicker', $provider->getName());
    }

    public function testReturnsNullIfThereIsNoCurrentProvider(): void
    {
        $factory = new MenuFactory();
        $router = $this->createMock(RouterInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $provider = new PagePickerProvider($factory, $router, $translator, $this->getSecurityHelper());
        $config = new PickerConfig('page');
        $picker = new Picker($factory, [$provider], $config);

        $this->assertNull($picker->getCurrentProvider());
    }

    public function testReturnsTheCurrentUrl(): void
    {
        $this->assertSame('', (string) $this->picker->getCurrentUrl());
    }

    public function testReturnsNullAsCurrentUrlIfThereIsNoCurrentMenuItem(): void
    {
        $factory = new MenuFactory();
        $router = $this->createMock(RouterInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $provider = new PagePickerProvider($factory, $router, $translator, $this->getSecurityHelper());
        $config = new PickerConfig('page');
        $picker = new Picker($factory, [$provider], $config);

        $this->assertSame('', (string) $picker->getCurrentUrl());
    }

    public function testFailsToReturnTheCurrentUrlIfThereAreNoMenuItems(): void
    {
        $factory = new MenuFactory();
        $config = new PickerConfig('page', [], 5, 'pagePicker');
        $picker = new Picker($factory, [], $config);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('No picker menu items found');

        $picker->getCurrentUrl();
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
