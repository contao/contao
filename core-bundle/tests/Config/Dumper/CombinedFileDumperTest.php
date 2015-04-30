<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Config\Dumper;

use Contao\CoreBundle\Config\Dumper\CombinedFileDumper;
use Contao\CoreBundle\Config\Loader\PhpFileLoader;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests the CombinedFileDumper class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class CombinedFileDumperTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf(
            'Contao\\CoreBundle\\Config\\Dumper\\CombinedFileDumper',
            new CombinedFileDumper(
                $this->getMock('Symfony\\Component\\Filesystem\\Filesystem'),
                $this->getMock('Contao\\CoreBundle\\Config\\Loader\\PhpFileLoader'),
                $this->getCacheDir()
            )
        );
    }

    /**
     * Tests dumping a file.
     */
    public function testDump()
    {
        $dumper = new CombinedFileDumper(
            $this->mockFilesystem("<?php \necho 'test';\n"),
            $this->mockLoader(),
            $this->getCacheDir()
        );

        $dumper->dump(['test.php'], 'test.php');
    }

    /**
     * Tests setting a valid header.
     */
    public function testValidHeader()
    {
        $dumper = new CombinedFileDumper(
            $this->mockFilesystem("<?php \necho 'foo';\necho 'test';\n"),
            $this->mockLoader(),
            $this->getCacheDir()
        );

        $dumper->setHeader("<?php \necho 'foo';");

        $dumper->dump(['test.php'], 'test.php');
    }

    /**
     * Tests setting an invalid header.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidHeader()
    {
        $dumper = new CombinedFileDumper(
            $this->getMock('Symfony\\Component\\Filesystem\\Filesystem'),
            $this->getMock('Contao\\CoreBundle\\Config\\Loader\\PhpFileLoader'),
            $this->getCacheDir()
        );

        $dumper->setHeader('No opening PHP tag');
    }

    /**
     * Returns a mocked filesystem object.
     *
     * @param mixed $expects The value expected to dump
     *
     * @return Filesystem The filesystem object
     */
    private function mockFilesystem($expects)
    {
        $filesystem = $this->getMock(
            'Symfony\\Component\\Filesystem\\Filesystem',
            ['dumpFile']
        );

        $filesystem
            ->expects($this->once())
            ->method('dumpFile')
            ->with($this->getCacheDir() . '/test.php', $expects)
        ;

        return $filesystem;
    }

    /**
     * Returns a mocked file loader object.
     *
     * @return PhpFileLoader The file loader object
     */
    private function mockLoader()
    {
        $loader = $this->getMock(
            'Contao\\CoreBundle\\Config\\Loader\\PhpFileLoader',
            ['load']
        );

        $loader
            ->expects($this->once())
            ->method('load')
            ->with('test.php', null)
            ->willReturn("\necho 'test';\n")
        ;

        return $loader;
    }
}
