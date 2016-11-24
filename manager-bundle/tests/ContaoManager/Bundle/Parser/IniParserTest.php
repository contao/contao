<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Test\ContaoManager\Bundle\Parser;

use Contao\ManagerBundle\ContaoManager\Bundle\Config\ConfigInterface;
use Contao\ManagerBundle\ContaoManager\Bundle\Parser\IniParser;
use Contao\ManagerBundle\ContaoManager\Bundle\Parser\ParserInterface;

class IniParserTest extends \PHPUnit_Framework_TestCase
{
    const FIXTURES_DIR = __DIR__ . '/../../../Fixtures/ContaoManager/Bundle/IniParser';

    /**
     * @var IniParser
     */
    private $parser;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->parser = new IniParser(self::FIXTURES_DIR);
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\ManagerBundle\ContaoManager\Bundle\Parser\IniParser', $this->parser);
        $this->assertInstanceOf('Contao\ManagerBundle\ContaoManager\Bundle\Parser\ParserInterface', $this->parser);
    }

    public function testSupports()
    {
        $this->assertTrue($this->parser->supports('foobar', 'ini'));
        $this->assertTrue($this->parser->supports('with-requires', null));
        $this->assertFalse($this->parser->supports('foobar', null));
    }

    public function testParseWithRequires()
    {
        /** @var ConfigInterface[] $configs */
        $configs = $this->parser->parse('with-requires');

        $this->assertCount(4, $configs);
        $this->assertInstanceOf(ConfigInterface::class, $configs[0]);

        $this->assertEquals('with-requires', $configs[0]->getName());
        $this->assertEquals([], $configs[0]->getReplace());
        $this->assertEquals(['core', 'news', 'without-ini', 'calendar'], $configs[0]->getLoadAfter());
        $this->assertTrue($configs[0]->loadInProduction());
        $this->assertTrue($configs[0]->loadInDevelopment());
    }

    public function testParseWithoutIni()
    {
        /** @var ConfigInterface[] $configs */
        $configs = $this->parser->parse('without-ini');

        $this->assertCount(1, $configs);
        $this->assertInstanceOf(ConfigInterface::class, $configs[0]);

        $this->assertEquals('without-ini', $configs[0]->getName());
        $this->assertEquals([], $configs[0]->getReplace());
        $this->assertEquals([], $configs[0]->getLoadAfter());
        $this->assertTrue($configs[0]->loadInProduction());
        $this->assertTrue($configs[0]->loadInDevelopment());
    }

    public function testParseWithoutRequires()
    {
        /** @var ConfigInterface[] $configs */
        $configs = $this->parser->parse('without-requires');

        $this->assertCount(1, $configs);
        $this->assertInstanceOf(ConfigInterface::class, $configs[0]);

        $this->assertEquals('without-requires', $configs[0]->getName());
        $this->assertEquals([], $configs[0]->getReplace());
        $this->assertEquals([], $configs[0]->getLoadAfter());
        $this->assertTrue($configs[0]->loadInProduction());
        $this->assertTrue($configs[0]->loadInDevelopment());
    }

    public function testParseNonExistingDirectory()
    {
        /** @var ConfigInterface[] $configs */
        $configs = $this->parser->parse('foobar');

        $this->assertCount(1, $configs);
        $this->assertInstanceOf(ConfigInterface::class, $configs[0]);

        $this->assertEquals('foobar', $configs[0]->getName());
        $this->assertEquals([], $configs[0]->getReplace());
        $this->assertEquals([], $configs[0]->getLoadAfter());
        $this->assertTrue($configs[0]->loadInProduction());
        $this->assertTrue($configs[0]->loadInDevelopment());
    }

    public function testParseRecursion()
    {
        /** @var ConfigInterface[] $configs */
        $configs = $this->parser->parse('recursion1');

        $this->assertCount(2, $configs);
        $this->assertInstanceOf(ConfigInterface::class, $configs[0]);
        $this->assertInstanceOf(ConfigInterface::class, $configs[1]);

        $this->assertEquals(['recursion2'], $configs[0]->getLoadAfter());
        $this->assertEquals(['recursion1'], $configs[1]->getLoadAfter());
    }

    /**
     * @runInSeparateProcess
     */
    public function testParseBrokenIni()
    {
        /**
         * refs php - test the return value of a method that triggers an error with PHPUnit - Stack Overflow
         * http://stackoverflow.com/questions/1225776/test-the-return-value-of-a-method-that-triggers-an-error-with-phpunit
         */
        \PHPUnit_Framework_Error_Warning::$enabled = false;
        \PHPUnit_Framework_Error_Notice::$enabled = false;
        error_reporting(0);

        $this->setExpectedException('RuntimeException', 'File ' . self::FIXTURES_DIR . '/broken-ini/config/autoload.ini cannot be decoded');
        $this->parser->parse('broken-ini');
    }
}
