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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class CombinedFileDumperTest extends TestCase
{
    public function testDumpsTheDataIntoAFile(): void
    {
        $filesystem = $this->mockFilesystem("<?php\n\necho 'test';\n");

        $dumper = new CombinedFileDumper($filesystem, $this->mockLoader(), $this->getTempDir());
        $dumper->dump(['test.php'], 'test.php');
    }

    public function testHandlesCustomHeaders(): void
    {
        $filesystem = $this->mockFilesystem("<?php\necho 'foo';\necho 'test';\n");

        $dumper = new CombinedFileDumper($filesystem, $this->mockLoader(), $this->getTempDir());
        $dumper->setHeader("<?php\necho 'foo';");
        $dumper->dump(['test.php'], 'test.php');
    }

    public function testFailsIfTheHeaderIsInvalid(): void
    {
        $filesystem = $this->createMock(Filesystem::class);
        $loader = $this->createMock(PhpFileLoader::class);
        $dumper = new CombinedFileDumper($filesystem, $loader, $this->getTempDir());

        $this->expectException('InvalidArgumentException');

        $dumper->setHeader('No opening PHP tag');
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

    private function mockLoader(): PhpFileLoader&MockObject
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
