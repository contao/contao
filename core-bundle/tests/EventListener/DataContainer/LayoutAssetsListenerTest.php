<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\InsertTags;

use Contao\CoreBundle\EventListener\DataContainer\LayoutAssetsListener;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class LayoutAssetsListenerTest extends TestCase
{
    public function testReturnsJsAndCssKeysFromManifestFile(): void
    {
        $listener = new LayoutAssetsListener(__DIR__.'/../../Fixtures/app/config/manifest1.json');

        $this->assertEquals(['foo.css'], $listener->getCssAssets());
        $this->assertEquals(['bar.js'], $listener->getJsAssets());
    }

    public function testReturnsNoJsAndCssIfManifestDoesNotContainAny(): void
    {
        $listener = new LayoutAssetsListener(__DIR__.'/../../Fixtures/app/config/manifest2.json');

        $this->assertEmpty($listener->getJsAssets());
        $this->assertEmpty($listener->getCssAssets());
    }

    public function testReturnsNoJsAndCssIfManifestIsNull(): void
    {
        $listener = new LayoutAssetsListener(null);

        $this->assertEmpty($listener->getJsAssets());
        $this->assertEmpty($listener->getCssAssets());
    }

    public function testReturnsNoJsAndCssIfManifestDoesNotExist(): void
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem
            ->expects($this->atLeastOnce())
            ->method('exists')
            ->willReturn(false)
        ;

        $listener = new LayoutAssetsListener('/invalid/path/to/manifest.json', $filesystem);

        $this->assertEmpty($listener->getJsAssets());
        $this->assertEmpty($listener->getCssAssets());
    }
}
