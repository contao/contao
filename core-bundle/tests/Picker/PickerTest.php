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

use Contao\CoreBundle\Picker\PagePickerProvider;
use Contao\CoreBundle\Picker\Picker;
use Contao\CoreBundle\Picker\PickerConfig;
use Knp\Menu\MenuFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * Tests the Picker class.
 */
class PickerTest extends TestCase
{
    /**
     * @var Picker
     */
    protected $picker;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $factory = new MenuFactory();

        $this->picker = new Picker(
            $factory,
            [new PagePickerProvider($factory, $this->createMock(RouterInterface::class))],
            new PickerConfig('page', [], 5, 'pagePicker')
        );

        $GLOBALS['TL_LANG']['MSC']['pagePicker'] = 'Page picker';
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TL_LANG']);
    }

    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf('Contao\CoreBundle\Picker\Picker', $this->picker);
    }

    /**
     * Tests returning the configuration.
     */
    public function testReturnsTheConfiguration(): void
    {
        $config = $this->picker->getConfig();

        $this->assertInstanceOf('Contao\CoreBundle\Picker\PickerConfig', $config);
        $this->assertSame('page', $config->getContext());
    }

    /**
     * Tests returning the menu.
     */
    public function testReturnsTheMenu(): void
    {
        $menu = $this->picker->getMenu();

        $this->assertInstanceOf('Knp\Menu\ItemInterface', $menu);
        $this->assertSame('picker', $menu->getName());
        $this->assertSame(1, $menu->count());

        $pagePicker = $menu->getChild('pagePicker');

        $this->assertInstanceOf('Knp\Menu\ItemInterface', $pagePicker);
        $this->assertTrue($pagePicker->isCurrent());
        $this->assertSame('Page picker', $pagePicker->getLabel());

        $this->assertSame($menu, $this->picker->getMenu());
    }

    /**
     * Tests returning the current provider.
     */
    public function testReturnsTheCurrentProvider(): void
    {
        $provider = $this->picker->getCurrentProvider();

        $this->assertInstanceOf('Contao\CoreBundle\Picker\PagePickerProvider', $provider);
        $this->assertSame('pagePicker', $provider->getName());
    }

    /**
     * Tests returning the current provider if there is no current provider.
     */
    public function testReturnsNullIfThereIsNoCurrentProvider(): void
    {
        $factory = new MenuFactory();

        $picker = new Picker(
            $factory,
            [new PagePickerProvider($factory, $this->createMock(RouterInterface::class))],
            new PickerConfig('page')
        );

        $this->assertNull($picker->getCurrentProvider());
    }

    /**
     * Tests returning the current URL.
     */
    public function testReturnsTheCurrentUrl(): void
    {
        $this->assertSame(null, $this->picker->getCurrentUrl());
    }

    /**
     * Tests returning the current URL if there is no current menu item.
     */
    public function testReturnsNullAsCurrentUrlIfThereIsNoCurrentMenuItem(): void
    {
        $factory = new MenuFactory();

        $picker = new Picker(
            $factory,
            [new PagePickerProvider($factory, $this->createMock(RouterInterface::class))],
            new PickerConfig('page')
        );

        $this->assertSame(null, $picker->getCurrentUrl());
    }

    /**
     * Tests returning the current URL if there are no menu items.
     */
    public function testFailsToReturnTheCurrentUrlIfThereAreNoMenuItems(): void
    {
        $picker = new Picker(new MenuFactory(), [], new PickerConfig('page', [], 5, 'pagePicker'));

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('No picker menu items found');

        $picker->getCurrentUrl();
    }
}
