<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Filesystem\Dbafs\Hashing;

use Contao\CoreBundle\Filesystem\Dbafs\DbafsManager;
use Contao\CoreBundle\Filesystem\Dbafs\Hashing\Context;
use Contao\CoreBundle\Filesystem\Dbafs\Hashing\HashGenerator;
use Contao\CoreBundle\Filesystem\MountManager;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Tests\TestCase;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;

class HashGeneratorTest extends TestCase
{
    public function testValidateHashFunctionExists(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/The "foofoo" hash algorithm is not available on this system. Try ".*" instead\./');

        new HashGenerator('foofoo');
    }

    /**
     * @dataProvider provideExpectedHashes
     */
    public function testHashString(string $algorithm, string $hash): void
    {
        $generator = new HashGenerator($algorithm);

        $this->assertSame($hash, $generator->hashString("foo\0bar"));
    }

    /**
     * @dataProvider provideExpectedHashes
     */
    public function testHashFileContent(string $algorithm, string $hash): void
    {
        $filesystem = $this->getDemoFilesystem();
        $context = new Context();

        $generator = new HashGenerator($algorithm);
        $generator->hashFileContent($filesystem, 'foo.txt', $context);

        $this->assertSame($hash, $context->getResult());
    }

    public function provideExpectedHashes(): \Generator
    {
        yield 'md5' => [
            'md5', 'f6f5f8cd0cb63668898ba29025ae824e',
        ];

        yield 'sha1' => [
            'sha1', 'e2c300a39311a2dfcaff799528415cb74c19317f',
        ];
    }

    public function testSkipsHashingIfLastModifiedTimeMatches(): void
    {
        $filesystem = $this->getDemoFilesystem();
        $lastModified = $filesystem->getLastModified('foo.txt');
        $context = new Context('fallback', $lastModified);

        $generator = new HashGenerator('md5', true);
        $generator->hashFileContent($filesystem, 'foo.txt', $context);

        $this->assertSame('fallback', $context->getResult());
    }

    public function testDoesNotSkipHashingIfLastModifiedTimeDiffers(): void
    {
        $filesystem = $this->getDemoFilesystem();
        $context = new Context('fallback', 12345);

        $generator = new HashGenerator('md5', true);
        $generator->hashFileContent($filesystem, 'foo.txt', $context);

        $this->assertSame('f6f5f8cd0cb63668898ba29025ae824e', $context->getResult());
    }

    public function testUpdatesLastModifiedEntry(): void
    {
        $filesystem = $this->getDemoFilesystem();
        $lastModified = $filesystem->getLastModified('foo.txt');
        $context = new Context('fallback', 12345);

        $generator = new HashGenerator('md5', true);
        $generator->hashFileContent($filesystem, 'foo.txt', $context);

        $this->assertTrue($context->lastModifiedChanged());
        $this->assertSame($lastModified, $context->getLastModified());
    }

    public function testDoesNotUpdateLastModifiedEntryIfDisabled(): void
    {
        $filesystem = $this->getDemoFilesystem();
        $lastModified = $filesystem->getLastModified('foo.txt');
        $context = new Context('fallback', 12345);

        $generator = new HashGenerator('md5', false);
        $generator->hashFileContent($filesystem, 'foo.txt', $context);

        $this->assertFalse($context->lastModifiedChanged());
        $this->assertNotSame($lastModified, $context->getLastModified());
    }

    private function getDemoFilesystem(): VirtualFilesystemInterface
    {
        $filesystem = new VirtualFilesystem(
            (new MountManager())->mount(new InMemoryFilesystemAdapter()),
            $this->createMock(DbafsManager::class),
        );

        $filesystem->write('foo.txt', "foo\0bar");

        return $filesystem;
    }
}
