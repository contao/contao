<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\HttpKernel\Bundle;

use Contao\CoreBundle\HttpKernel\Bundle\ResourcesProvider;
use Contao\CoreBundle\Test\TestCase;

/**
 * Tests the ResourcesProviderTest class.
 *
 * @author Andreas Schempp <http://github.com/aschempp>
 */
class ResourcesProviderTest extends TestCase
{
    /**
     * @var ResourcesProvider
     */
    protected $service;

    /**
     * Creates a new Contao module bundle.
     */
    protected function setUp()
    {
        $this->service = new ResourcesProvider($this->getRootDir() . '/app');
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\HttpKernel\Bundle\ResourcesProvider', $this->service);
    }

    public function testAddResourcesPath()
    {
        $this->service->addResourcesPath('testBundle', 'testPath');

        $this->assertContains('testBundle', $this->service->getBundleNames());
        $this->assertContains('testPath', $this->service->getResourcesPaths());
    }

    public function testAddPublicFolders()
    {
        $this->service->addPublicFolders([$this->getRootDir() . '/system/modules/legacy-module/config/../assets']);

        $this->assertContains('system/modules/legacy-module/assets', $this->service->getPublicFolders());
    }
}
