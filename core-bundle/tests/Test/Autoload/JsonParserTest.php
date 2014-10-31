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

use Contao\CoreBundle\Autoload\JsonParser;
use Symfony\Component\Finder\SplFileInfo;

class JsonParserTest extends \PHPUnit_Framework_TestCase
{
    public function testInstanceOf()
    {
        $parser = new JsonParser();

        $this->assertInstanceOf('Contao\CoreBundle\Autoload\JsonParser', $parser);
        $this->assertInstanceOf('Contao\CoreBundle\Autoload\ParserInterface', $parser);
    }

    public function testDefaultAutoload()
    {
        $parser = new JsonParser();
        $file = new SplFileInfo(
            __DIR__ . '/../../fixtures/Autoload/JsonParser/regular/autoload.json'
            , 'relativePath',
            'relativePathName'
        );

        $this->assertSame([
            'bundles' => [
                'Contao\CoreBundle\ContaoCoreBundle' => [
                    'class'         => 'Contao\CoreBundle\ContaoCoreBundle',
                    'name'          => 'ContaoCoreBundle',
                    'replace'       => [],
                    'environments'  => ['all'],
                    'load-after'    => []
                ]]
        ], $parser->parse($file));
    }
    public function testNoKeysDefinedAutoload()
    {
        $parser = new JsonParser();
        $file = new SplFileInfo(
            __DIR__ . '/../../fixtures/Autoload/JsonParser/no-keys-defined/autoload.json'
            , 'relativePath',
            'relativePathName'
        );

        $this->assertSame([
            'bundles' => [
                'Contao\CoreBundle\ContaoCoreBundle' => [
                    'class'         => 'Contao\CoreBundle\ContaoCoreBundle',
                    'name'          => 'ContaoCoreBundle',
                    'replace'       => [],
                    'environments'  => ['all'],
                    'load-after'    => []
                ]]
        ], $parser->parse($file));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testInvalidJsonWillThrowException()
    {
        $parser = new JsonParser();
        $file = new SplFileInfo(
            __DIR__ . '/../../fixtures/Autoload/JsonParser/invalid/autoload.json'
            , 'relativePath',
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
        $file = new SplFileInfo(
            __DIR__ . '/../../fixtures/Autoload/JsonParser/no-bundles-key/autoload.json'
            , 'relativePath',
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
        $file = new SplFileInfo('iDoNotExist', 'relativePath', 'relativePathName');

        $parser->parse($file);
    }
}
