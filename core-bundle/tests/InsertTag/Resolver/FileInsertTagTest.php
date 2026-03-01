<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\InsertTag\Resolver;

use Contao\CoreBundle\Filesystem\PublicUri\Options;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\ResolvedParameters;
use Contao\CoreBundle\InsertTag\Resolver\FileInsertTag;
use Contao\CoreBundle\Tests\TestCase;
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Uid\Uuid;

class FileInsertTagTest extends TestCase
{
    #[TestWith(['2c6bd7fa-7d0d-4f31-9f8a-2f74a5a8c7d1', 'https://example.com/file?version=foobar', new Uri('https://example.com/file?version=foobar')])]
    #[TestWith(['2c6bd7fa-7d0d-4f31-9f8a-2f74a5a8c7d1', '', null])]
    public function testValidUuidReturnsCorrectResult(string $uuid, string $expectedUri, Uri|null $filesystemReturnValue): void
    {
        $storage = $this->createMock(VirtualFilesystemInterface::class);
        $storage
            ->expects($this->once())
            ->method('generatePublicUri')
            ->with(
                $this->callback(
                    function (Uuid $uuidInstance) use ($uuid): bool {
                        $this->assertSame($uuid, (string) $uuidInstance);

                        return true;
                    },
                ),
                $this->callback(
                    function (Options $options): bool {
                        $this->assertTrue($options->get(Options::OPTION_ADD_VERSION_QUERY_PARAMETER));

                        return true;
                    },
                ),
            )
            ->willReturn($filesystemReturnValue)
        ;

        $listener = new FileInsertTag($storage);

        $this->assertSame($expectedUri, $listener(
            new ResolvedInsertTag('file', new ResolvedParameters(['2c6bd7fa-7d0d-4f31-9f8a-2f74a5a8c7d1']), []),
        )->getValue());
    }

    public function testInvalidUUidReturnsEmpty(): void
    {
        $storage = $this->createMock(VirtualFilesystemInterface::class);
        $storage
            ->expects($this->never())
            ->method('generatePublicUri')
        ;

        $listener = new FileInsertTag($storage);

        $this->assertSame('', $listener(
            new ResolvedInsertTag('file', new ResolvedParameters(['invalid']), []),
        )->getValue());
    }
}
