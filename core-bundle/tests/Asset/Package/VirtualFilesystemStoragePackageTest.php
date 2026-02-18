<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Asset\Package;

use Contao\CoreBundle\Asset\Package\VirtualFilesystemStoragePackage;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Tests\TestCase;
use Nyholm\Psr7\Uri;

class VirtualFilesystemStoragePackageTest extends TestCase
{
    public function testGetVersion(): void
    {
        $package = $this->getPackage();

        $this->assertSame(
            '62e29b4cad9e2ef6',
            $package->getVersion('some/path'),
        );
    }

    public function testGetUrl(): void
    {
        $package = $this->getPackage();

        $this->assertSame(
            'https://base-url/some/path?v=62e29b4cad9e2ef6',
            $package->getUrl('some/path'),
        );
    }

    private function getPackage(): VirtualFilesystemStoragePackage
    {
        $storage = $this->createStub(VirtualFilesystem::class);
        $storage
            ->method('getLastModified')
            ->willReturn(12345678)
        ;

        $storage
            ->method('generatePublicUri')
            ->willReturn(new Uri('https://base-url/some/path'))
        ;

        return new VirtualFilesystemStoragePackage($storage);
    }
}
