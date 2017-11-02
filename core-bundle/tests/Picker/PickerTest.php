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
use Symfony\Component\Translation\TranslatorInterface;

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
        $translator = $this->createMock(TranslatorInterface::class);

        $translator
            ->method('trans')
            ->willReturn('Page picker')
        ;

        $this->picker = new Picker(
            $factory,
            [new PagePickerProvider($factory, $this->createMock(RouterInterface::class), $translator)],
            new PickerConfig('page', [], 5, 'pagePicker')
        );
    }

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf('Contao\CoreBundle\Picker\Picker', $this->picker);
    }

    public function testReturnsTheConfiguration(): void
    {
        $config = $this->picker->getConfig();

        $this->assertInstanceOf('Contao\CoreBundle\Picker\PickerConfig', $config);
        $this->assertSame('page', $config->getContext());
    }

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

    public function testReturnsTheCurrentProvider(): void
    {
        $provider = $this->picker->getCurrentProvider();

        $this->assertInstanceOf('Contao\CoreBundle\Picker\PagePickerProvider', $provider);
        $this->assertSame('pagePicker', $provider->getName());
    }

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

    public function testReturnsTheCurrentUrl(): void
    {
        $this->assertSame(null, $this->picker->getCurrentUrl());
    }

    public function testReturnsNullAsCurrentUrlIfThereIsNoCurrentMenuItem(): void
    {
        $factory = new MenuFactory();

        $picker = new Picker(
            $factory,
            [
                new PagePickerProvider(
                    $factory,
                    $this->createMock(RouterInterface::class),
                    $this->createMock(TranslatorInterface::class)
                ),
            ],
            new PickerConfig('page')
        );

        $this->assertSame(null, $picker->getCurrentUrl());
    }

    public function testFailsToReturnTheCurrentUrlIfThereAreNoMenuItems(): void
    {
        $picker = new Picker(new MenuFactory(), [], new PickerConfig('page', [], 5, 'pagePicker'));

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('No picker menu items found');

        $picker->getCurrentUrl();
    }
}
