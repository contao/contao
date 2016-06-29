<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Test\Autoload;

use Contao\ManagerBundle\Autoload\ConfigInterface;
use Contao\ManagerBundle\Autoload\IniParser;
use Symfony\Component\Finder\SplFileInfo;

class IniParserTest extends \PHPUnit_Framework_TestCase
{
    public function testInstanceOf()
    {
        $parser = new IniParser();

        $this->assertInstanceOf('Contao\ManagerBundle\Autoload\IniParser', $parser);
        $this->assertInstanceOf('Contao\ManagerBundle\Autoload\ParserInterface', $parser);
    }

    public function testDummyModuleWithRequires()
    {
        $parser = new IniParser();
        $file = new SplFileInfo(
            __DIR__ . '/../Fixtures/Autoload/IniParser/dummy-module-with-requires',
            'relativePath',
            'relativePathName'
        );

        /** @var ConfigInterface[] $configs */
        $configs = $parser->parse($file);

        $this->assertCount(1, $configs);
        $this->assertInstanceOf('Contao\ManagerBundle\Autoload\ConfigInterface', $configs[0]);

        $this->assertEquals(null, $configs[0]->getClass());
        $this->assertEquals('dummy-module-with-requires', $configs[0]->getName());
        $this->assertEquals([], $configs[0]->getReplace());
        $this->assertEquals(['all'], $configs[0]->getEnvironments());
        $this->assertEquals(['core', 'news', 'calendar'], $configs[0]->getLoadAfter());
    }

    public function testDummyModuleWithoutAutoload()
    {
        $parser = new IniParser();
        $file = new SplFileInfo(
            __DIR__ . '/../Fixtures/Autoload/IniParser/dummy-module-without-autoload',
            'relativePath',
            'relativePathName'
        );

        /** @var ConfigInterface[] $configs */
        $configs = $parser->parse($file);

        $this->assertCount(1, $configs);
        $this->assertInstanceOf('Contao\ManagerBundle\Autoload\ConfigInterface', $configs[0]);

        $this->assertEquals(null, $configs[0]->getClass());
        $this->assertEquals('dummy-module-without-autoload', $configs[0]->getName());
        $this->assertEquals([], $configs[0]->getReplace());
        $this->assertEquals(['all'], $configs[0]->getEnvironments());
        $this->assertEquals([], $configs[0]->getLoadAfter());
    }

    public function testDummyModuleWithoutRequires()
    {
        $parser = new IniParser();
        $file = new SplFileInfo(
            __DIR__ . '/../Fixtures/Autoload/IniParser/dummy-module-without-requires',
            'relativePath',
            'relativePathName'
        );

        /** @var ConfigInterface[] $configs */
        $configs = $parser->parse($file);

        $this->assertCount(1, $configs);
        $this->assertInstanceOf('Contao\ManagerBundle\Autoload\ConfigInterface', $configs[0]);

        $this->assertEquals(null, $configs[0]->getClass());
        $this->assertEquals('dummy-module-without-requires', $configs[0]->getName());
        $this->assertEquals([], $configs[0]->getReplace());
        $this->assertEquals(['all'], $configs[0]->getEnvironments());
        $this->assertEquals([], $configs[0]->getLoadAfter());
    }

    /**
     * @runInSeparateProcess
     */
    public function testDummyModuleWithHorribleBrokenIni()
    {
        $parser = new IniParser();
        $file   = new SplFileInfo(
            __DIR__ . '/../Fixtures/Autoload/IniParser/dummy-module-with-invalid-autoload',
            'relativePath',
            'relativePathName'
        );

        /**
         * refs php - test the return value of a method that triggers an error with PHPUnit - Stack Overflow
         * http://stackoverflow.com/questions/1225776/test-the-return-value-of-a-method-that-triggers-an-error-with-phpunit
         */
        \PHPUnit_Framework_Error_Warning::$enabled = false;
        \PHPUnit_Framework_Error_Notice::$enabled = false;
        error_reporting(0);

        $this->setExpectedException('RuntimeException', "File $file/config/autoload.ini cannot be decoded");
        $parser->parse($file);
    }
}
