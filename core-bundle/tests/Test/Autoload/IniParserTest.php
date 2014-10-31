<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao\CoreBundle\Test\Autoload;

use Contao\CoreBundle\Autoload\IniParser;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\Finder\SplFileInfo;

class IniParserTest extends TestCase
{
    public function testInstanceOf()
    {
        $parser = new IniParser();

        $this->assertInstanceOf('Contao\CoreBundle\Autoload\IniParser', $parser);
        $this->assertInstanceOf('Contao\CoreBundle\Autoload\ParserInterface', $parser);
    }

    public function testDummyModuleWithRequires()
    {
        $parser = new IniParser();
        $file = new SplFileInfo(
            __DIR__ . '/../../fixtures/Autoload/IniParser/dummy-module-with-requires'
            , 'relativePath',
            'relativePathName'
        );

        $this->assertSame([
            'bundles' => [[
                'class'         => null,
                'name'          => 'dummy-module-with-requires',
                'replace'       => [],
                'environments'  => ['all'],
                'load-after'    => ['core', 'news', 'calendar']
            ]]
        ], $parser->parse($file));
    }

    public function testDummyModuleWithoutAutoload()
    {
        $parser = new IniParser();
        $file = new SplFileInfo(
            __DIR__ . '/../../fixtures/Autoload/IniParser/dummy-module-without-autoload'
            , 'relativePath',
            'relativePathName'
        );

        $this->assertSame([
            'bundles' => [[
                'class'         => null,
                'name'          => 'dummy-module-without-autoload',
                'replace'       => [],
                'environments'  => ['all'],
                'load-after'    => []
            ]]
        ], $parser->parse($file));
    }

    public function testDummyModuleWithoutRequires()
    {
        $parser = new IniParser();
        $file = new SplFileInfo(
            __DIR__ . '/../../fixtures/Autoload/IniParser/dummy-module-without-requires'
            , 'relativePath',
            'relativePathName'
        );

        $this->assertSame([
            'bundles' => [[
                'class'         => null,
                'name'          => 'dummy-module-without-requires',
                'replace'       => [],
                'environments'  => ['all'],
                'load-after'    => []
            ]]
        ], $parser->parse($file));
    }

    public function testDummyModuleWithHorribleBrokenIni()
    {
        $this->disableErrorReporting();
        $parser = new IniParser();
        $file   = new SplFileInfo(
            __DIR__ . '/../../fixtures/Autoload/IniParser/dummy-module-with-invalid-autoload'
            , 'relativePath',
            'relativePathName'
        );

        $this->disableErrorReporting();
        $this->setExpectedException('RuntimeException', "File $file/config/autoload.ini cannot be decoded");
        $parser->parse($file);
    }
}
