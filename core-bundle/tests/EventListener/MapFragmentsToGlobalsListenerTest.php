<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\ContentProxy;
use Contao\CoreBundle\EventListener\MapFragmentsToGlobalsListener;
use Contao\CoreBundle\Fragment\FragmentRegistry;
use Contao\CoreBundle\Fragment\FragmentRegistryInterface;
use Contao\CoreBundle\Tests\TestCase;
use Contao\ModuleProxy;
use Contao\PageProxy;

class MapFragmentsToGlobalsListenerTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        unset($GLOBALS['TL_PTY'], $GLOBALS['FE_MOD'], $GLOBALS['TL_CTE']);
    }

    public function testCanBeInstantiated(): void
    {
        $registry = new FragmentRegistry();
        $listener = new MapFragmentsToGlobalsListener($registry);

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\MapFragmentsToGlobalsListener', $listener);
    }

    public function testMapsFragmentsToTheGlobalsArray(): void
    {
        $registry = new FragmentRegistry();

        $registry->addFragment(
            'page-type',
            new \stdClass(),
            [
                'tag' => FragmentRegistryInterface::PAGE_TYPE_FRAGMENT,
                'type' => 'test',
                'controller' => 'test',
            ]
        );

        $registry->addFragment(
            'frontend-module',
            new \stdClass(),
            [
                'tag' => FragmentRegistryInterface::FRONTEND_MODULE_FRAGMENT,
                'type' => 'test',
                'controller' => 'test',
                'category' => 'navigationMod',
            ]
        );

        $registry->addFragment(
            'content-element',
            new \stdClass(),
            [
                'tag' => FragmentRegistryInterface::CONTENT_ELEMENT_FRAGMENT,
                'type' => 'test',
                'controller' => 'test',
                'category' => 'text',
            ]
        );

        $listener = new MapFragmentsToGlobalsListener($registry);
        $listener->setFramework($this->mockContaoFramework());
        $listener->onInitializeSystem();

        $this->assertSame(PageProxy::class, $GLOBALS['TL_PTY']['test']);
        $this->assertSame(ModuleProxy::class, $GLOBALS['FE_MOD']['navigationMod']['test']);
        $this->assertSame(ContentProxy::class, $GLOBALS['TL_CTE']['text']['test']);
    }

    public function testFailsToMapFrontendModulesWithoutACategory(): void
    {
        $registry = new FragmentRegistry();

        $registry->addFragment(
            'frontend-module',
            new \stdClass(),
            [
                'tag' => FragmentRegistryInterface::FRONTEND_MODULE_FRAGMENT,
                'type' => 'test',
                'controller' => 'test',
            ]
        );

        $listener = new MapFragmentsToGlobalsListener($registry);
        $listener->setFramework($this->mockContaoFramework());

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('but forgot to specify the "category" attribute');

        $listener->onInitializeSystem();
    }

    public function testFailsToMapContentElementsWithoutACategory(): void
    {
        $registry = new FragmentRegistry();

        $registry->addFragment(
            'content-element',
            new \stdClass(),
            [
                'tag' => FragmentRegistryInterface::CONTENT_ELEMENT_FRAGMENT,
                'type' => 'test',
                'controller' => 'test',
            ]
        );

        $listener = new MapFragmentsToGlobalsListener($registry);
        $listener->setFramework($this->mockContaoFramework());

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('but forgot to specify the "category" attribute');

        $listener->onInitializeSystem();
    }
}
