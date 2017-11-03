<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Config\Dumper;

use Contao\CoreBundle\Config\Dumper\CombinedFileDumper;
use Contao\CoreBundle\Config\Loader\PhpFileLoader;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class CombinedFileDumperTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $filesystem = $this->createMock(Filesystem::class);
        $loader = $this->createMock(PhpFileLoader::class);
        $dumper = new CombinedFileDumper($filesystem, $loader, $this->getTempDir());

        $this->assertInstanceOf('Contao\CoreBundle\Config\Dumper\CombinedFileDumper', $dumper);
    }

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

    /**
     * Mocks the filesystem.
     *
     * @param mixed $expects
     *
     * @return Filesystem|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockFilesystem($expects): Filesystem
    {
        $filesystem = $this->createMock(Filesystem::class);

        $filesystem
            ->expects($this->once())
            ->method('dumpFile')
            ->with($this->getTempDir().'/test.php', $expects)
        ;

        return $filesystem;
    }

    /**
     * Mocks a file loader.
     *
     * @return PhpFileLoader|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockLoader(): PhpFileLoader
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
