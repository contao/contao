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
        $this->assertInstanceOf('Contao\CoreBundle\HttpKernel\Bundle\ResourceProvider', new ResourceProvider());
    }

    public function testConstructorArguments()
    {
        $provider = new ResourceProvider(
            ['testPath'],
            ['publicFolder']
        );

        $this->assertContains('testPath', $provider->getResourcesPaths());
        $this->assertContains('publicFolder', $provider->getPublicFolders());
    }

    public function testAddResourcesPath()
    {
        $provider = new ResourceProvider();
        $provider->addResourcesPath('testPath');

        $this->assertContains('testPath', $provider->getResourcesPaths());
    }

    public function testAddPublicFolders()
    {
        $provider = new ResourceProvider();
        $provider->addPublicFolders(['publicFolder']);

        $this->assertContains('publicFolder', $provider->getPublicFolders());
    }
}
