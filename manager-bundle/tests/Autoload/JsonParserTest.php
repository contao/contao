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
use Contao\ManagerBundle\Autoload\JsonParser;
use Symfony\Component\Finder\SplFileInfo;

class JsonParserTest extends \PHPUnit_Framework_TestCase
{
    public function testInstanceOf()
    {
        $parser = new JsonParser();

        $this->assertInstanceOf('Contao\ManagerBundle\Autoload\JsonParser', $parser);
        $this->assertInstanceOf('Contao\ManagerBundle\Autoload\ParserInterface', $parser);
    }

    public function testDefaultAutoload()
    {
        $parser = new JsonParser();
        $file = new SplFileInfo(
            __DIR__ . '/../Fixtures/Autoload/JsonParser/regular.json',
            'relativePath',
            'relativePathName'
        );

        /** @var ConfigInterface[] $configs */
        $configs = $parser->parse($file);

        $this->assertCount(1, $configs);

        $config = reset($configs);
        $this->assertInstanceOf('Contao\ManagerBundle\Autoload\ConfigInterface', $config);
        $this->assertEquals('Contao\CoreBundle\ContaoCoreBundle', $config->getName());
        $this->assertEquals([], $config->getReplace());
        $this->assertEquals(['all'], $config->getEnvironments());
        $this->assertEquals([], $config->getLoadAfter());
    }

    public function testNoKeysDefinedAutoload()
    {
        $parser = new JsonParser();
        $file   = new SplFileInfo(
            __DIR__ . '/../Fixtures/Autoload/JsonParser/no-keys-defined.json',
            'relativePath',
            'relativePathName'
        );

        /** @var ConfigInterface[] $configs */
        $configs = $parser->parse($file);

        $this->assertCount(1, $configs);

        $config = reset($configs);
        $this->assertInstanceOf('Contao\ManagerBundle\Autoload\ConfigInterface', $config);
        $this->assertEquals('Contao\CoreBundle\ContaoCoreBundle', $config->getName());
        $this->assertEquals([], $config->getReplace());
        $this->assertEquals(['all'], $config->getEnvironments());
        $this->assertEquals([], $config->getLoadAfter());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testInvalidJsonWillThrowException()
    {
        $parser = new JsonParser();
        $file   = new SplFileInfo(
            __DIR__ . '/../Fixtures/Autoload/JsonParser/invalid.json',
            'relativePath',
            'relativePathName'
        );

        $parser->parse($file);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testNoBundlesKeyInJsonWillThrowException()
    {
        $parser = new JsonParser();
        $file   = new SplFileInfo(
            __DIR__ . '/../Fixtures/Autoload/JsonParser/no-bundles-key.json',
            'relativePath',
            'relativePathName'
        );

        $parser->parse($file);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWillThrowExceptionIfFileNotExists()
    {
        $parser = new JsonParser();
        $file   = new SplFileInfo('iDoNotExist', 'relativePath', 'relativePathName');

        $parser->parse($file);
    }
}
