<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Config;

use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Test\TestCase;

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
    public function testInstantiation()
    {
        $finder = new ResourceFinder();

        $this->assertInstanceOf('Contao\CoreBundle\Config\ResourceFinder', $finder);
    }

    /**
     * Tests the find() method.
     */
    public function testFind()
    {
        $finder = new ResourceFinder([]);

        $this->assertInstanceOf('Symfony\Component\Finder\Finder', $finder->find());
    }

    /**
     * Tests the findIn() method.
     */
    public function testFindIn()
    {
        $finder = new ResourceFinder([
            $this->getRootDir().'/vendor/contao/test-bundle/Resources/contao',
            $this->getRootDir().'/system/modules/foobar',
        ]);

        $this->assertInstanceOf('Symfony\Component\Finder\Finder', $finder->findIn('config'));
    }

    /**
     * Tests the findIn() method with an invalid subpath.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testFindInInvalidSubpath()
    {
        $finder = new ResourceFinder([
            $this->getRootDir().'/vendor/contao/test-bundle/Resources/contao',
            $this->getRootDir().'/system/modules/foobar',
        ]);

        $this->assertInstanceOf('Symfony\Component\Finder\Finder', $finder->findIn('foo'));
    }
}
