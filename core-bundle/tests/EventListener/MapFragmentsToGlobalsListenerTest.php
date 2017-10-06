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
use Contao\CoreBundle\DependencyInjection\Compiler\FragmentRegistryPass;
use Contao\CoreBundle\EventListener\MapFragmentsToGlobalsListener;
use Contao\CoreBundle\FragmentRegistry\FragmentRegistry;
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

        $listener = new MapFragmentsToGlobalsListener($registry);
        $listener->setFramework($this->mockContaoFramework());

        $registry->addFragment(
            'page-type',
            new \stdClass(),
            [
                'tag' => FragmentRegistryPass::TAG_FRAGMENT_PAGE_TYPE,
                'type' => 'test',
                'controller' => 'test',
            ]
        );

        $registry->addFragment(
            'frontend-module',
            new \stdClass(),
            [
                'tag' => FragmentRegistryPass::TAG_FRAGMENT_FRONTEND_MODULE,
                'type' => 'test',
                'controller' => 'test',
                'category' => 'navigationMod',
            ]
        );

        $registry->addFragment(
            'content-element',
            new \stdClass(),
            [
                'tag' => FragmentRegistryPass::TAG_FRAGMENT_CONTENT_ELEMENT,
                'type' => 'test',
                'controller' => 'test',
                'category' => 'text',
            ]
        );

        $listener->onInitializeSystem();

        $this->assertSame(PageProxy::class, $GLOBALS['TL_PTY']['test']);
        $this->assertSame(ModuleProxy::class, $GLOBALS['FE_MOD']['navigationMod']['test']);
        $this->assertSame(ContentProxy::class, $GLOBALS['TL_CTE']['text']['test']);
    }
}
