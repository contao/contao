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

use Contao\CoreBundle\EventListener\InsertTags\AssetListener;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Asset\Packages;

class AssetListenerTest extends TestCase
{
    public function testReplacesInsertTagsWithPackageName(): void
    {
        $packages = $this->createMock(Packages::class);
        $packages
            ->expects($this->once())
            ->method('getUrl')
            ->with('foo/bar', 'package')
            ->willReturn('/foo/bar')
        ;

        $listener = new AssetListener($packages);

        $this->assertSame('/foo/bar', $listener->onReplaceInsertTags('asset::foo/bar::package'));
    }

    public function testReplacesInsertTagsWithoutPackageName(): void
    {
        $packages = $this->createMock(Packages::class);
        $packages
            ->expects($this->once())
            ->method('getUrl')
            ->with('foo/bar', null)
            ->willReturn('/foo/bar')
        ;

        $listener = new AssetListener($packages);

        $this->assertSame('/foo/bar', $listener->onReplaceInsertTags('asset::foo/bar'));
    }

    public function testIgnoresOtherInsertTags(): void
    {
        $packages = $this->createMock(Packages::class);
        $packages
            ->expects($this->never())
            ->method('getUrl')
        ;

        $listener = new AssetListener($packages);

        $this->assertFalse($listener->onReplaceInsertTags('env::pageTitle'));
    }
}
