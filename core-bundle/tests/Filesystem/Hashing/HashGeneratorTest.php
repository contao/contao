<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Filesystem\Hashing;

use Contao\CoreBundle\Filesystem\Hashing\Context;
use Contao\CoreBundle\Filesystem\Hashing\HashGenerator;
use Contao\CoreBundle\Tests\TestCase;
use League\Flysystem\Config;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;

class HashGeneratorTest extends TestCase
{
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
        $adapter = new InMemoryFilesystemAdapter();
        $adapter->write('foo.txt', "foo\0bar", new Config());

        $context = new Context();

        $generator = new HashGenerator($algorithm);
        $generator->hashFileContent($adapter, 'foo.txt', $context);

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
        $adapter = new InMemoryFilesystemAdapter();
        $adapter->write('foo.txt', "foo\0bar", new Config());
        $lastModified = $adapter->lastModified('foo.txt')->lastModified();

        $context = new Context('fallback', $lastModified);

        $generator = new HashGenerator('md5', true);
        $generator->hashFileContent($adapter, 'foo.txt', $context);

        $this->assertSame('fallback', $context->getResult());
    }

    public function testDoesNotSkipHashingIfLastModifiedTimeDiffers(): void
    {
        $adapter = new InMemoryFilesystemAdapter();
        $adapter->write('foo.txt', "foo\0bar", new Config());

        $context = new Context('fallback', 12345);

        $generator = new HashGenerator('md5', true);
        $generator->hashFileContent($adapter, 'foo.txt', $context);

        $this->assertSame('f6f5f8cd0cb63668898ba29025ae824e', $context->getResult());
    }

    public function testUpdatesLastModifiedEntry(): void
    {
        $adapter = new InMemoryFilesystemAdapter();
        $adapter->write('foo.txt', "foo\0bar", new Config());
        $lastModified = $adapter->lastModified('foo.txt')->lastModified();

        $context = new Context('fallback', 12345);

        $generator = new HashGenerator('md5', true);
        $generator->hashFileContent($adapter, 'foo.txt', $context);

        $this->assertTrue($context->lastModifiedChanged());
        $this->assertSame($lastModified, $context->getLastModified());
    }

    public function testDoesNotUpdateLastModifiedEntryIfDisabled(): void
    {
        $adapter = new InMemoryFilesystemAdapter();
        $adapter->write('foo.txt', "foo\0bar", new Config());
        $lastModified = $adapter->lastModified('foo.txt')->lastModified();

        $context = new Context('fallback', 12345);

        $generator = new HashGenerator('md5');
        $generator->hashFileContent($adapter, 'foo.txt', $context);

        $this->assertFalse($context->lastModifiedChanged());
        $this->assertNotSame($lastModified, $context->getLastModified());
    }
}
