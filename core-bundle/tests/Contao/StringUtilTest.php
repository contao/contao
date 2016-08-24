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
     * Tests the parseSimpleTokens() method throws exception when containing php
     * code.
     *
     * @param string $string
     * @param bool
     *
     * @expectedException \InvalidArgumentException
     * @dataProvider parseSimpleTokensThrowsExceptionWhenContainingPhp
     */
    public function testParseSimpleTokensThrowsExceptionWhenContainingPhp($string, $skip)
    {
        if ($skip) {
            $this->markTestSkipped(sprintf('Skipped because PHP version is "%s" and tested opening tags are not interpreted at all.', PHP_VERSION));
        }

        StringUtil::parseSimpleTokens($string, []);
    }

    /**
     * Tests the parseSimpleTokens() method throws exception when tokens contain php
     * code.
     *
     * @param array $tokens
     * @param bool
     *
     * @expectedException \InvalidArgumentException
     * @dataProvider parseSimpleTokensThrowsExceptionWhenTokensContainingPhp
     */
    public function testParseSimpleTokensThrowsExceptionWhenTokensContainingPhp(array $tokens, $skip)
    {
        if ($skip) {
            $this->markTestSkipped(sprintf('Skipped because PHP version is "%s" and tested opening tags are not interpreted at all.', PHP_VERSION));
        }

        StringUtil::parseSimpleTokens('foobar', $tokens);
    }

    /**
     * Provides the data for the testParseSimpleTokens() method.
     *
     * @return array
     */
    public function parseSimpleTokensProvider()
    {
        return [
            'Test regular token replacement' => [
                'This is my ##email##',
                ['email' => 'test@foobar.com'],
                'This is my test@foobar.com',
            ],
            'Test regular token replacement is non greedy' => [
                'This is my ##email##,##email2##',
                ['email' => 'test@foobar.com', 'email2' => 'foo@test.com'],
                'This is my test@foobar.com,foo@test.com',
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

    /**
     * Provides the data for the testParseSimpleTokens() method.
     *
     * @return array
     */
    public function parseSimpleTokensThrowsExceptionWhenContainingPhp()
    {
        return [
            '(<?php)' => [
                'This <?php var_dump() ?> is a test.',
                false
            ],
            '(<?=)' => [
                'This <?= $var ?> is a test.',
                false
            ],
            '(<?)' => [
                'This <? var_dump() ?> is a test.',
                version_compare(PHP_VERSION, '7.0.0', '>=')
            ],
            '(<%)' => [
                'This <% var_dump() ?> is a test.',
                version_compare(PHP_VERSION, '7.0.0', '>=') || !in_array(strtolower(ini_get('asp_tags')), ['1', 'on', 'yes', 'true'])
            ],
            '(<script language="php">)' => [
                'This <script language="php"> var_dump() </script> is a test.',
                version_compare(PHP_VERSION, '7.0.0', '>=')
            ],
            '(<script language=\'php\'>)' => [
                'This <script language=\'php\'> var_dump() </script> is a test.',
                version_compare(PHP_VERSION, '7.0.0', '>=')
            ],
        ];
    }

    /**
     * Provides the data for the testParseSimpleTokens() method.
     *
     * @return array
     */
    public function parseSimpleTokensThrowsExceptionWhenTokensContainingPhp()
    {
        return [
            '(<?php)' => [
                ['foo' => 'This <?php var_dump() ?> is a test.'],
                false
            ],
            '(<?=)' => [
                ['foo' => 'This <?= $var ?> is a test.'],
                false
            ],
            '(<?)' => [
                ['foo' => 'This <? var_dump() ?> is a test.'],
                version_compare(PHP_VERSION, '7.0.0', '>=')
            ],
            '(<%)' => [
                ['foo' => 'This <% var_dump() ?> is a test.'],
                version_compare(PHP_VERSION, '7.0.0', '>=') || !in_array(strtolower(ini_get('asp_tags')), ['1', 'on', 'yes', 'true'])
            ],
            '(<script language="php">)' => [
                ['foo' => 'This <script language="php"> var_dump() </script> is a test.'],
                version_compare(PHP_VERSION, '7.0.0', '>=')
            ],
            '(<script language=\'php\'>)' => [
                ['foo' => 'This <script language=\'php\'> var_dump() </script> is a test.'],
                version_compare(PHP_VERSION, '7.0.0', '>=')
            ],
        ];
    }
}
