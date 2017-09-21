<?php

declare(strict_types=1);

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

class ResourceFinderTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $finder = new ResourceFinder();

        $this->assertInstanceOf('Contao\CoreBundle\Config\ResourceFinder', $finder);
    }

    public function testReturnsAFinderObject(): void
    {
        $finder = new ResourceFinder([]);

        $this->assertInstanceOf(Finder::class, $finder->find());

        $finder = new ResourceFinder([
            $this->getRootDir().'/vendor/contao/test-bundle/Resources/contao',
            $this->getRootDir().'/system/modules/foobar',
        ]);

        $this->assertInstanceOf(Finder::class, $finder->findIn('config'));
    }

    public function testFailsIfTheSubpathIsInvalid(): void
    {
        $finder = new ResourceFinder([
            $this->getRootDir().'/vendor/contao/test-bundle/Resources/contao',
            $this->getRootDir().'/system/modules/foobar',
        ]);

        $this->expectException('InvalidArgumentException');
        $this->assertInstanceOf(Finder::class, $finder->findIn('foo'));
    }
}
