<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Autoload;

use Contao\CoreBundle\Autoload\IniParser;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Tests the IniParser class.
 *
 * @author Yanick Witschi <https://github.com/Toflar>
 */
class IniParserTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $parser = new IniParser();

        $this->assertInstanceOf('Contao\CoreBundle\Autoload\IniParser', $parser);
        $this->assertInstanceOf('Contao\CoreBundle\Autoload\ParserInterface', $parser);
    }

    /**
     * Tests parsing an autoload.ini file with requires.
     */
    public function testFileWithRequires()
    {
        $parser = new IniParser();

        $file = new SplFileInfo(
            $this->getRootDir() . '/system/modules/with-requires',
            'relativePath',
            'relativePathName'
        );

        $this->assertSame(
            [
                'bundles' => [
                    [
                        'class'         => null,
                        'name'          => 'with-requires',
                        'replace'       => [],
                        'environments'  => ['all'],
                        'load-after'    => ['core', 'news', 'calendar'],
                    ],
                ],
            ],
            $parser->parse($file)
        );
    }

    /**
     * Tests parsing an autoload.ini file without requires.
     */
    public function testFileWithoutRequires()
    {
        $parser = new IniParser();

        $file = new SplFileInfo(
            $this->getRootDir() . '/system/modules/without-requires',
            'relativePath',
            'relativePathName'
        );

        $this->assertSame(
            [
                'bundles' => [
                    [
                        'class'         => null,
                        'name'          => 'without-requires',
                        'replace'       => [],
                        'environments'  => ['all'],
                        'load-after'    => [],
                    ],
                ],
            ],
            $parser->parse($file));
    }

    /**
     * Tests parsing an autoload.ini file with invalid syntax.
     *
     * @expectedException \RuntimeException
     */
    public function testFileWithInvalidSyntax()
    {
        $parser = new IniParser();

        $file = new SplFileInfo(
            $this->getRootDir() . '/system/invalid',
            'relativePath',
            'relativePathName'
        );

        $errorReporting = error_reporting(0);

        $parser->parse($file);

        error_reporting($errorReporting);
    }
}
