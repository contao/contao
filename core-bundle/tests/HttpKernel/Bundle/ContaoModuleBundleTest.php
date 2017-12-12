<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\HttpKernel\Bundle;

use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Contao\CoreBundle\Tests\TestCase;

class ContaoModuleBundleTest extends TestCase
{
    /**
     * @var ContaoModuleBundle
     */
    private $bundle;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->bundle = new ContaoModuleBundle('foobar', $this->getFixturesDir().'/app');
    }

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf('Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle', $this->bundle);
    }

    public function testReturnsTheModulePath(): void
    {
        $this->assertSame($this->getFixturesDir().'/system/modules/foobar', $this->bundle->getPath());
    }

    public function testFailsIfTheModuleFolderDoesNotExist(): void
    {
        $this->expectException('LogicException');

        $this->bundle = new ContaoModuleBundle('invalid', $this->getFixturesDir().'/app');
    }
}
