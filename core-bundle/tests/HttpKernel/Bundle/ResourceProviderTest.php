<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\HttpKernel\Bundle;

use Contao\CoreBundle\HttpKernel\Bundle\ResourceProvider;
use Contao\CoreBundle\Test\TestCase;

/**
 * Tests the ResourceProviderTest class.
 *
 * @author Andreas Schempp <http://github.com/aschempp>
 */
class ResourceProviderTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\HttpKernel\Bundle\ResourcesProvider', new ResourceProvider());
    }

    public function testConstructorArguments()
    {
        $provider = new ResourceProvider(
            ['testBundle' => 'testPath'],
            ['publicFolder']
        );

        $this->assertContains('testBundle', $provider->getBundleNames());
        $this->assertContains('testPath', $provider->getResourcesPaths());
        $this->assertContains('publicFolder', $provider->getPublicFolders());
    }

    public function testAddResourcesPath()
    {
        $provider = new ResourceProvider();
        $provider->addResourcesPath('testBundle', 'testPath');

        $this->assertContains('testBundle', $provider->getBundleNames());
        $this->assertContains('testPath', $provider->getResourcesPaths());
    }

    public function testAddPublicFolders()
    {
        $provider = new ResourceProvider();
        $provider->addPublicFolders(['publicFolder']);

        $this->assertContains('publicFolder', $provider->getPublicFolders());
    }
}
