<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Config\Dumper;

use Contao\CoreBundle\Config\Dumper\CombinedFileDumper;
use Contao\CoreBundle\Test\TestCase;

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
            'Contao\CoreBundle\Config\Dumper\CombinedFileDumper',
            new CombinedFileDumper(
                $this->getMock('Symfony\Component\Filesystem\Filesystem'),
                $this->getMock('Contao\CoreBundle\Config\Loader\PhpFileLoader'),
                $this->getCacheDir()
            )
        );
    }

    public function testDump()
    {
        $dumper = new CombinedFileDumper(
            $this->mockFilesystem("<?php \necho 'test';"),
            $this->mockLoader(),
            $this->getCacheDir()
        );

        $dumper->dump(['test.php'], 'test.php');
    }

    public function testValidHeader()
    {
        $dumper = new CombinedFileDumper(
            $this->mockFilesystem("<?php \necho 'foo';\necho 'test';"),
            $this->mockLoader(),
            $this->getCacheDir()
        );

        $dumper->setHeader("<?php \necho 'foo';");

        $dumper->dump(['test.php'], 'test.php');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidHeader()
    {
        $dumper = new CombinedFileDumper(
            $this->getMock('Symfony\Component\Filesystem\Filesystem'),
            $this->getMock('Contao\CoreBundle\Config\Loader\PhpFileLoader'),
            $this->getCacheDir()
        );

        $dumper->setHeader('no PHP open tag');
    }

    /**
     * Mocks a filesystem.
     *
     * @param mixed $expects The value expected to dump
     *
     * @return \Symfony\Component\Filesystem\Filesystem
     */
    private function mockFilesystem($expects)
    {
        $filesystem = $this->getMock(
            'Symfony\Component\Filesystem\Filesystem',
            [
                'dumpFile'
            ]
        );

        $filesystem
            ->expects($this->once())
            ->method('dumpFile')
            ->with($this->getCacheDir() . '/test.php', $expects)
        ;


        return $filesystem;
    }

    /**
     * Mocks a file loader
     */
    private function mockLoader()
    {
        $loader = $this->getMock(
            'Contao\CoreBundle\Config\Loader\PhpFileLoader',
            [
                'load'
            ]
        );

        $loader
            ->expects($this->once())
            ->method('load')
            ->with('test.php', null)
            ->willReturn("\necho 'test';")
        ;

        return $loader;
    }
}
