<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Contao;

use Contao\Input;

/**
 * Tests the Widget class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Leo Feyer <https://github.com/leofeyer>
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @group legacy
 */
class WidgetTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Includes the helper functions if they have not yet been included.
     */
    public static function setUpBeforeClass()
    {
        if (!function_exists('utf8_decode_entities')) {
            include_once __DIR__.'/../../src/Resources/contao/helper/functions.php';
        }
    }

    /**
     * Tests the getPost() method.
     *
     * @param string $key
     * @param string $input
     * @param mixed  $value
     * @param string $expected
     *
     * @dataProvider postProvider
     */
    public function testGetPost($key, $input, $value, $expected)
    {
        // Prevent "undefined index" errors
        $errorReporting = error_reporting();
        error_reporting($errorReporting & ~E_NOTICE);

        $widget = $this->getMock('Contao\Widget');
        $class = new \ReflectionClass('Contao\Widget');
        $method = $class->getMethod('getPost');

        $method->setAccessible(true);

        $_POST[$input] = $value;
        Input::resetCache();
        Input::initialize();

        $this->assertEquals($expected, $method->invoke($widget, $key));

        // Restore the error reporting level
        error_reporting($errorReporting);
    }

    /**
     * Provides the data for the testGetPost() method.
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
                ['k1' => ['k2' => ['k3' => 'bar']]],
                'bar',
            ],
            ['foo[0]', 'foo', ['k1' => 'bar'], ''],
            ['foo[k1][0]', 'foo', ['k1' => 'bar'], 'bar'],
            ['foo', 'nofoo', 'bar', ''],
            ['', 'foo', 'bar', ''],
            ['', '', 'bar', 'bar'],
            ['[0]', '', ['bar'], 'bar'],
        ];
    }
}
