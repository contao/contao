<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Config\Dumper;

use Contao\CoreBundle\Config\Dumper\CombinedFileDumper;
use Contao\CoreBundle\Config\Loader\PhpFileLoader;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class CombinedFileDumperTest extends TestCase
{
    public function testDumpsTheDataIntoAFile(): void
    {
        $filesystem = new Filesystem();
        $source = $this->getTempDir().'/source.php';
        $cacheDirectory = $this->getTempDir().'/cache';
        $filesystem->dumpFile($source, "<?php\necho 'test';\n");

        $dumper = new CombinedFileDumper($filesystem, new PhpFileLoader(), $cacheDirectory);
        $dumper->dump([$source], 'dca/test.php');

        $expected = <<<'PHP'
            <?php
            /*
             * Source files (line ranges in this cache file):
             * 8-8: source.php
             */

            /* START of file: source.php */
            echo 'test';
            /* END of file: source.php */
            PHP;

        $this->assertSame($expected."\n", file_get_contents($cacheDirectory.'/dca/test.php'));
    }

    public function testHandlesCustomHeaders(): void
    {
        $expected = <<<'PHP'
            <?php
            echo 'foo';
            /*
             * Source files (line ranges in this cache file):
             * 9-9: test.php
             */

            /* START of file: test.php */
            echo 'test';
            /* END of file: test.php */
            PHP;

        $filesystem = $this->mockFilesystem($expected."\n");

        $dumper = new CombinedFileDumper($filesystem, $this->mockLoader(), $this->getTempDir());
        $dumper->setHeader("<?php\necho 'foo';");
        $dumper->dump(['test.php'], 'test.php');
    }

    public function testFailsIfTheHeaderIsInvalid(): void
    {
        $filesystem = $this->createStub(Filesystem::class);
        $loader = $this->createStub(PhpFileLoader::class);
        $dumper = new CombinedFileDumper($filesystem, $loader, $this->getTempDir());

        $this->expectException('InvalidArgumentException');

        $dumper->setHeader('No opening PHP tag');
    }

    #[DataProvider('provideSourceContents')]
    public function testIndexesMultipleSources(string $first, string $second): void
    {
        $loader = $this->createStub(PhpFileLoader::class);
        $loader
            ->method('load')
            ->willReturnMap([
                ['first.php', null, $first],
                ['empty.php', null, ''],
                ['second.php', null, $second],
            ])
        ;

        $expected = <<<'PHP'
            <?php
            /*
             * Source files (line ranges in this cache file):
             * 9-9: first.php
             * 13-13: second.php
             */

            /* START of file: first.php */
            echo 'first';
            /* END of file: first.php */

            /* START of file: second.php */
            echo 'second';
            /* END of file: second.php */
            PHP;

        $dumper = new CombinedFileDumper($this->mockFilesystem($expected."\n"), $loader, $this->getTempDir());
        $dumper->dump(['first.php', 'empty.php', 'second.php'], 'test.php');
    }

    public static function provideSourceContents(): iterable
    {
        yield 'with trailing newlines' => ["\necho 'first';\n", "echo 'second';\n"];
        yield 'without trailing newlines' => ["\necho 'first';", "echo 'second';"];
    }

    private function mockFilesystem(string $expects): Filesystem&MockObject
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem
            ->expects($this->once())
            ->method('dumpFile')
            ->with(Path::normalize($this->getTempDir()).'/test.php', $expects)
        ;

        return $filesystem;
    }

    private function mockLoader(): MockObject&PhpFileLoader
    {
        $loader = $this->createMock(PhpFileLoader::class);
        $loader
            ->expects($this->once())
            ->method('load')
            ->with('test.php', null)
            ->willReturn("\necho 'test';\n")
        ;

        return $loader;
    }
}
