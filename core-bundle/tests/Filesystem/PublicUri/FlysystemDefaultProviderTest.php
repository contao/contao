<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Filesystem\PublicUri;

use Contao\CoreBundle\Filesystem\PublicUri\FlysystemDefaultProvider;
use Contao\CoreBundle\Tests\Fixtures\FilesystemAdapterAndPublicUrlGeneratorInterface;
use Contao\CoreBundle\Tests\TestCase;
use League\Flysystem\Local\LocalFilesystemAdapter;

/**
 * @experimental
 */
class FlysystemDefaultProviderTest extends TestCase
{
    public function testGetUri(): void
    {
        $adapter = $this->createMock(FilesystemAdapterAndPublicUrlGeneratorInterface::class);
        $adapter
            ->method('publicUrl')
            ->with('path/to/resource.txt')
            ->willReturn('https://some.bucket/some.key')
        ;

        $provider = new FlysystemDefaultProvider();
        $uri = $provider->getUri($adapter, 'path/to/resource.txt', null);

        $this->assertSame('https://some.bucket/some.key', (string) $uri);
    }

    public function testGetUriWithNonMatchingAdapter(): void
    {
        $provider = new FlysystemDefaultProvider();

        $uri = $provider->getUri(
            $this->createMock(LocalFilesystemAdapter::class),
            'path/to/resource.txt',
            null,
        );

        $this->assertNull($uri);
    }
}
