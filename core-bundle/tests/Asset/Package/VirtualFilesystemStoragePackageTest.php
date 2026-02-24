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
use Contao\CoreBundle\Filesystem\PublicUri\AbstractPublicUriProvider;
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

    public function testGetVersionFallsBackToLastModifiedHashIfNoVersionCouldBeFound(): void
    {
        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->exactly(2))
            ->method('generatePublicUri')
            ->willReturnMap([
                ['some/path', new Uri('https://base-url/some/path')],
                ['some/empty-version', new Uri('https://base-url/some/empty-version?'.AbstractPublicUriProvider::VERSION_QUERY_PARAMETER.'=')],
            ])
        ;

        $storage
            ->expects($this->exactly(2))
            ->method('getLastModified')
            ->willReturnMap([
                ['some/path', 123456789],
                ['some/empty-version', 123456789],
            ])
        ;

        $package = new VirtualFilesystemStoragePackage($storage);

        $expected = hash('xxh3', '123456789');

        $this->assertSame($expected, $package->getVersion('some/path'));
        $this->assertSame($expected, $package->getVersion('some/empty-version'));
    }

    public function testGetVersionReturnsEmptyStringIfNoPublicUriCanBeGenerated(): void
    {
        $storage = $this->createStub(VirtualFilesystem::class);
        $storage
            ->method('generatePublicUri')
            ->willReturn(null)
        ;

        $package = new VirtualFilesystemStoragePackage($storage);

        $this->assertSame('', $package->getVersion('some/path'));
    }

    public function testGetUrl(): void
    {
        $package = $this->getPackage();

        $this->assertSame(
            'https://base-url/some/path?version=62e29b4cad9e2ef6',
            $package->getUrl('some/path'),
        );
    }

    private function getPackage(): VirtualFilesystemStoragePackage
    {
        $storage = $this->createStub(VirtualFilesystem::class);
        $storage
            ->method('generatePublicUri')
            ->willReturnMap([['some/path', new Uri('https://base-url/some/path?'.AbstractPublicUriProvider::VERSION_QUERY_PARAMETER.'=62e29b4cad9e2ef6')]])
        ;

        return new VirtualFilesystemStoragePackage($storage);
    }
}
