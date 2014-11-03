<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
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
    public function testInstanceOf()
    {
        $parser = new IniParser();

        $this->assertInstanceOf('Contao\CoreBundle\Autoload\IniParser', $parser);
        $this->assertInstanceOf('Contao\CoreBundle\Autoload\ParserInterface', $parser);
    }

    /**
     * FIXME
     */
    public function testDummyModuleWithRequires()
    {
        $parser = new IniParser();

        $file = new SplFileInfo(
            __DIR__ . '/../Fixtures/IniParser/dummy-module-with-requires',
            'relativePath',
            'relativePathName'
        );

        $this->assertSame(
            [
                'bundles' => [
                    [
                        'class'         => null,
                        'name'          => 'dummy-module-with-requires',
                        'replace'       => [],
                        'environments'  => ['all'],
                        'load-after'    => ['core', 'news', 'calendar']
                    ]
                ]
            ],
            $parser->parse($file)
        );
    }

    /**
     * FIXME
     */
    public function testDummyModuleWithoutAutoload()
    {
        $parser = new IniParser();

        $file = new SplFileInfo(
            __DIR__ . '/../Fixtures/IniParser/dummy-module-without-autoload',
            'relativePath',
            'relativePathName'
        );

        $this->assertSame(
            [
                'bundles' => [
                    [
                        'class'         => null,
                        'name'          => 'dummy-module-without-autoload',
                        'replace'       => [],
                        'environments'  => ['all'],
                        'load-after'    => []
                    ]
                ]
            ],
            $parser->parse($file)
        );
    }

    /**
     * FIXME
     */
    public function testDummyModuleWithoutRequires()
    {
        $parser = new IniParser();

        $file = new SplFileInfo(
            __DIR__ . '/../Fixtures/IniParser/dummy-module-without-requires',
            'relativePath',
            'relativePathName'
        );

        $this->assertSame(
            [
                'bundles' => [
                    [
                        'class'         => null,
                        'name'          => 'dummy-module-without-requires',
                        'replace'       => [],
                        'environments'  => ['all'],
                        'load-after'    => []
                    ]
                ]
            ],
            $parser->parse($file));
    }

    /**
     * FIXME
     */
    public function testDummyModuleWithHorribleBrokenIni()
    {
        $this->disableErrorReporting();

        $parser = new IniParser();

        $file = new SplFileInfo(
            __DIR__ . '/../Fixtures/IniParser/dummy-module-with-invalid-autoload',
            'relativePath',
            'relativePathName'
        );

        $this->disableErrorReporting(); # FIXME: enableErrorReporting?

        $this->setExpectedException('RuntimeException', "File $file/config/autoload.ini cannot be decoded");

        $parser->parse($file);
    }
}
