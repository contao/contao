<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\ApiPlatform\State;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Contao\ApiBundle\ApiPlatform\State\VirtualFilesystemStateProcessor;
use Contao\ApiBundle\Dto\VirtualFilesystemMove;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class VirtualFilesystemStateProcessorTest extends TestCase
{
    public function testUploadsTheRequestBody(): void
    {
        $item = new FilesystemItem(true, 'documents/example.txt', 123, 7, 'text/plain');
        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->once())
            ->method('writeStream')
            ->with(
                'documents/example.txt',
                $this->callback(static fn ($stream): bool => \is_resource($stream) && 'content' === stream_get_contents($stream)),
            )
        ;

        $storage
            ->method('get')
            ->with('documents/example.txt')
            ->willReturn($item)
        ;

        $processor = new VirtualFilesystemStateProcessor($storage, $this->createSecurityStub(), new RequestStack());
        $result = $processor->process(null, new Put(), ['path' => 'documents/example.txt'], ['request' => Request::create('/', 'PUT', content: 'content')]);

        $this->assertSame('documents/example.txt', $result->path);
    }

    public function testMovesTheItem(): void
    {
        $item = new FilesystemItem(true, 'archive/example.txt', 123, 7, 'text/plain');
        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->once())
            ->method('move')
            ->with('documents/example.txt', 'archive/example.txt')
        ;

        $storage
            ->method('get')
            ->with('archive/example.txt')
            ->willReturn($item)
        ;

        $processor = new VirtualFilesystemStateProcessor($storage, $this->createSecurityStub(), new RequestStack());
        $result = $processor->process(new VirtualFilesystemMove('documents/example.txt', 'archive/example.txt'), new Post());

        $this->assertSame('archive/example.txt', $result->path);
    }

    private function createSecurityStub(): Security
    {
        $security = $this->createStub(Security::class);
        $security
            ->method('isGranted')
            ->willReturn(true)
        ;

        return $security;
    }
}
