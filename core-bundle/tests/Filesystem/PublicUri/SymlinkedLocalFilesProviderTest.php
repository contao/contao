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

use Contao\CoreBundle\Filesystem\PublicUri\SymlinkedLocalFilesProvider;
use Contao\CoreBundle\Tests\TestCase;
use League\Flysystem\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SymlinkedLocalFilesProviderTest extends TestCase
{
    public function testGetUri(): void
    {
        $adapter = $this->createMock(FilesystemAdapter::class);

        $request = $this->createMock(Request::class);
        $request
            ->method('getSchemeAndHttpHost')
            ->willReturn('https://example.com')
        ;

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

        $provider = new SymlinkedLocalFilesProvider($adapter, 'upload/dir', $requestStack);
        $uri = $provider->getUri($adapter, 'path/to/resource.txt', null);

        $this->assertNotNull($uri);
        $this->assertSame('https://example.com/upload/dir/path/to/resource.txt', (string) $uri);
    }

    public function testGetUriWithNonMatchingAdapter(): void
    {
        $provider = new SymlinkedLocalFilesProvider(
            $this->createMock(FilesystemAdapter::class),
            'upload/dir',
            $this->createMock(RequestStack::class)
        );

        $uri = $provider->getUri(
            $this->createMock(FilesystemAdapter::class),
            'path/to/resource.txt',
            null
        );

        $this->assertNull($uri);
    }
}
