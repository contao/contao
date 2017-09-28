<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Config;

use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Finder\Finder;

/**
 * Tests the ResourceFinder class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ResourceFinderTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $finder = new ResourceFinder();

        $this->assertInstanceOf('Contao\CoreBundle\Config\ResourceFinder', $finder);
    }

    /**
     * Tests that the find() and findIn() methods return a finder object.
     */
    public function testReturnsAFinderObject()
    {
        $finder = new ResourceFinder([]);

        $this->assertInstanceOf(Finder::class, $finder->find());

        $finder = new ResourceFinder([
            $this->getRootDir().'/vendor/contao/test-bundle/Resources/contao',
            $this->getRootDir().'/system/modules/foobar',
        ]);

        $this->assertInstanceOf(Finder::class, $finder->findIn('config'));
    }

    /**
     * Tests that an invalid subpath triggers an exception.
     */
    public function testFailsIfTheSubpathIsInvalid()
    {
        $finder = new ResourceFinder([
            $this->getRootDir().'/vendor/contao/test-bundle/Resources/contao',
            $this->getRootDir().'/system/modules/foobar',
        ]);

        $this->expectException('InvalidArgumentException');
        $this->assertInstanceOf(Finder::class, $finder->findIn('foo'));
    }
}
