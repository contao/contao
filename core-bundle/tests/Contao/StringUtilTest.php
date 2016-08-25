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
     * Tests the parseSimpleTokens() method works correctly with newlines.
     *
     * @param string $string
     * @param array $tokens
     * @param string $expected
     *
     * @dataProvider parseSimpleTokensCorrectNewlines
     */
    public function testParseSimpleTokensCorrectNewlines($string, array $tokens, $expected)
    {
        $this->assertEquals($expected, StringUtil::parseSimpleTokens($string, $tokens));
    }

    /**
     * Tests the parseSimpleTokens() method doesn’t execute php code.
     *
     * @param string $string
     * @param bool
     *
     * @dataProvider parseSimpleTokensDoesntExecutePhp
     */
    public function testParseSimpleTokensDoesntExecutePhp($string, $skip)
    {
        $this->assertEquals($string, StringUtil::parseSimpleTokens($string, []));
    }

    /**
     * Tests the parseSimpleTokens() method doesn’t execute php code inside
     * tokens.
     *
     * @param array $tokens
     * @param bool
     *
     * @dataProvider parseSimpleTokensDoesntExecutePhpInToken
     */
    public function testParseSimpleTokensDoesntExecutePhpInToken(array $tokens, $skip)
    {
        $this->assertEquals($tokens['foo'], StringUtil::parseSimpleTokens('##foo##', $tokens));
    }

    /**
     * Tests the parseSimpleTokens() method doesn’t execute php code when tokens
     * contain php code that is generated only after replacing the tokens.
     */
    public function testParseSimpleTokensDoesntExecutePhpInCombinedToken()
    {
        $this->assertEquals('This is <?php echo "I am evil";?> evil', StringUtil::parseSimpleTokens('This is ##open####open2####close## evil', [
            'open'  => '<',
            'open2' => '?php echo "I am evil";',
            'close' => '?>'
        ]));
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
            'Test if-tags insertion not evaluated' => [
                '##token##',
                ['token' => '{if token=="foo"}'],
                '{if token=="foo"}',
            ],
            'Test if-tags insertion not evaluated with multiple tokens' => [
                '##token1####token2####token3##',
                ['token1' => '{', 'token2' => 'if', 'token3' => ' token=="foo"}'],
                '{if token=="foo"}',
            ],
        ];
    }

    /**
     * Provides the data for the testParseSimpleTokensCorrectNewlines() method.
     *
     * @return array
     */
    public function parseSimpleTokensCorrectNewlines()
    {
        return [
            'Test newlines are kept end of token' => [
                "This is my ##token##\n",
                ['token' => "foo"],
                "This is my foo\n",
            ],
            'Test newlines are kept end in token' => [
                "This is my ##token##",
                ['token' => "foo\n"],
                "This is my foo\n",
            ],
            'Test newlines are kept end in and after token' => [
                "This is my ##token##\n",
                ['token' => "foo\n"],
                "This is my foo\n\n",
            ],
            'Test newlines are kept' => [
                "This is my \n ##newline## here",
                ['newline' => "foo\nbar\n"],
                "This is my \n foo\nbar\n here",
            ],
            'Test newlines are removed after if tag' => [
                "\n{if token=='foo'}\nline2\n{endif}\n",
                ['token' => "foo"],
                "\nline2\n",
            ],
            'Test newlines are removed after else tag' => [
                "\n{if token!='foo'}{else}\nline2\n{endif}\n",
                ['token' => "foo"],
                "\nline2\n",
            ],
        ];
    }

    /**
     * Provides the data for the testParseSimpleTokens() method.
     *
     * @return array
     */
    public function parseSimpleTokensDoesntExecutePhp()
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
    public function parseSimpleTokensDoesntExecutePhpInToken()
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
