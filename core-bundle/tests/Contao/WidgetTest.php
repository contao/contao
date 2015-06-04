<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Contao;

use Contao\CoreBundle\Test\TestCase;
use Contao\Input;

/**
 * Tests the Widget class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class WidgetTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass()
    {
        define('TL_MODE', 'BE');

        require __DIR__ . "/../../src/Resources/contao/library/Contao/Input.php";
        class_alias('Contao\\Input', 'Input');

        require __DIR__ . "/../../src/Resources/contao/helper/functions.php";

        // Needs to be disable to prevent "undefined index" errors
        error_reporting(E_ALL & ~E_NOTICE);
    }

    /**
     * @dataProvider postProvider
     */
    public function testGetPost($key, $input, $value, $expected)
    {
        $widget = $this->getMock('\\Contao\\Widget');
        $class  = new \ReflectionClass('\\Contao\\Widget');
        $method = $class->getMethod('getPost');

        $method->setAccessible(true);

        $_POST[$input] = $value;
        Input::resetCache();
        Input::initialize();

        $actual = $method->invoke($widget, $key);

        $this->assertEquals($expected, $actual);
    }

    /**
     * DataProvider for testGetPost method.
     *
     * @return array
     */
    public function postProvider()
    {
        return [
            ['foo', 'foo', 'bar', 'bar'],
            ['foo[0]', 'foo', ['bar'], 'bar'],
            [
                'foo[k1][k2][k3]',
                'foo',
                ['k1'=>['k2'=>['k3'=>'bar']]],
                'bar'
            ],
            ['foo[0]', 'foo', ['k1'=>'bar'], ''],
            ['foo[k1][0]', 'foo', ['k1'=>'bar'], 'bar'],
            ['foo', 'nofoo', 'bar', ''],
            ['', 'foo', 'bar', ''],
            ['', '', 'bar', 'bar'],
            ['[0]', '', ['bar'], 'bar'],
        ];
    }
}
