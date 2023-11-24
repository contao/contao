<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\HttpKernel\Bundle;

use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Filesystem\Path;

class ContaoModuleBundleTest extends TestCase
{
    private ContaoModuleBundle $bundle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bundle = new ContaoModuleBundle('foobar', Path::normalize($this->getFixturesDir()));
    }

    public function testReturnsTheModulePath(): void
    {
        $this->assertSame(Path::normalize($this->getFixturesDir()).'/system/modules/foobar', $this->bundle->getPath());
    }

    public function testFailsIfTheModuleFolderDoesNotExist(): void
    {
        $this->expectException('LogicException');

        $this->bundle = new ContaoModuleBundle('invalid', $this->getFixturesDir());
    }
}
