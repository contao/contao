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
use Contao\StringUtil;

/**
 * Tests the StringUtil class.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class StringUtilTest extends \PHPUnit_Framework_TestCase
{
    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        define('TL_ERROR', 'ERROR');
    }

    /**
     * Tests the parseSimpleTokens() method.
     *
     * @param string $string
     * @param array $tokens
     * @param string $expected
     *
     * @dataProvider parseSimpleTokensProvider
     */
    public function testParseSimpleTokens($string, array $tokens, $expected)
    {
        $this->assertEquals($expected, StringUtil::parseSimpleTokens($string, $tokens));
    }

    /**
     * Provides the data for the testParseSimpleTokens() method.
     *
     * @return array
     */
    public function parseSimpleTokensProvider()
    {
        return [
            'Test PHP code is stripped (<?)' => [
                'This <? var_dump() ?> is a test.',
                [],
                'This  is a test.',
            ],
            'Test PHP code is stripped (<?php)' => [
                'This <?php var_dump() ?> is a test.',
                [],
                'This  is a test.',
            ],
            'Test PHP code is stripped (<%)' => [
                'This <% var_dump() ?> is a test.',
                [],
                'This  is a test.',
            ],
            'Test PHP code is stripped (<%=)' => [
                'This <%= var_dump() ?> is a test.',
                [],
                'This  is a test.',
            ],
            'Test PHP code is stripped (<script language="php">)' => [
                'This <script language="php"> var_dump() </script> is a test.',
                [],
                'This  is a test.',
            ],
            'Test regular token replacement' => [
                'This is my ##email##',
                ['email' => 'test@foobar.com'],
                'This is my test@foobar.com',
            ],
            'Test token replacement with special characters (-)' => [
                'This is my ##e-mail##',
                ['e-mail' => 'test@foobar.com'],
                'This is my test@foobar.com',
            ],
            'Test token replacement with special characters (&)' => [
                'This is my ##e&mail##',
                ['e&mail' => 'test@foobar.com'],
                'This is my test@foobar.com',
            ],
            'Test token replacement with special characters (#)' => [
                'This is my ##e#mail##',
                ['e#mail' => 'test@foobar.com'],
                'This is my test@foobar.com',
            ],
            'Test comparisons (==) with regular characters (match)' => [
                'This is my {if email==""}match{endif}',
                ['email' => ''],
                'This is my match',
            ],
            'Test comparisons (==) with regular characters (no match)' => [
                'This is my {if email==""}match{endif}',
                ['email' => 'test@foobar.com'],
                'This is my ',
            ],
            'Test comparisons (>) with regular characters (match)' => [
                'This is my {if value>0}match{endif}',
                ['value' => 5],
                'This is my match',
            ],
            'Test comparisons (>) with regular characters (no match)' => [
                'This is my {if value>0}hello{endif}',
                ['value' => -8],
                'This is my ',
            ],
            'Test comparisons (<) with regular characters (match)' => [
                'This is my {if value<0}match{endif}',
                ['value' => -5],
                'This is my match',
            ],
            'Test comparisons (<) with regular characters (no match)' => [
                'This is my {if value<0}hello{endif}',
                ['value' => 9],
                'This is my ',
            ],
            'Test comparisons (<) with special characters (match)' => [
                'This is my {if val&#ue<0}match{endif}',
                ['val&#ue' => -5],
                'This is my match',
            ],
            'Test comparisons (<) with special characters (no match)' => [
                'This is my {if val&#ue<0}match{endif}',
                ['val&#ue' => 9],
                'This is my ',
            ],
            'Test whitespace in tokens not allowed and ignored' => [
                'This is my ##dumb token## you know',
                ['dumb token' => 'foobar'],
                'This is my ##dumb token## you know',
            ],
        ];
    }
}
