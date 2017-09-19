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

/**
 * Tests the CombinedFileDumper class.
 */
class CombinedFileDumperTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(
            'Contao\CoreBundle\Config\Dumper\CombinedFileDumper',
            new CombinedFileDumper(
                $this->createMock(Filesystem::class),
                $this->createMock(PhpFileLoader::class),
                $this->getCacheDir()
            )
        );
    }

    /**
     * Tests dumping the data into a file.
     */
    public function testDumpsTheDataIntoAFile(): void
    {
        $dumper = new CombinedFileDumper(
            $this->mockFilesystem("<?php\n\necho 'test';\n"),
            $this->mockLoader(),
            $this->getCacheDir()
        );

        $dumper->dump(['test.php'], 'test.php');
    }

    /**
     * Tests that a custom header can be set.
     */
    public function testHandlesCustomHeaders(): void
    {
        $dumper = new CombinedFileDumper(
            $this->mockFilesystem("<?php\necho 'foo';\necho 'test';\n"),
            $this->mockLoader(),
            $this->getCacheDir()
        );

        $dumper->setHeader("<?php\necho 'foo';");
        $dumper->dump(['test.php'], 'test.php');
    }

    /**
     * Tests that an invalid header triggers an exception.
     */
    public function testFailsIfTheHeaderIsInvalid(): void
    {
        $this->expectException('InvalidArgumentException');

        $dumper = new CombinedFileDumper(
            $this->createMock(Filesystem::class),
            $this->createMock(PhpFileLoader::class),
            $this->getCacheDir()
        );

        $dumper->setHeader('No opening PHP tag');
    }

    /**
     * Returns a mocked filesystem object.
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
            ->with($this->getCacheDir().'/test.php', $expects)
        ;

        return $filesystem;
    }

    /**
     * Returns a mocked file loader object.
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
