<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\CoreBundle\Tests\TestCase;
use Contao\StringUtil;
use Contao\System;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class StringUtilTest extends TestCase
{
    private $prevSubstituteCharacter;

    protected function setUp(): void
    {
        parent::setUp();

        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $this->getFixturesDir());
        $container->set('monolog.logger.contao', new NullLogger());

        System::setContainer($container);

        // Save the previous substitute character, as we need to override it in the tests (see #5011)
        $this->prevSubstituteCharacter = mb_substitute_character();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        mb_substitute_character($this->prevSubstituteCharacter);
    }

    public function testGeneratesAliases(): void
    {
        $GLOBALS['TL_CONFIG']['characterSet'] = 'UTF-8';

        $this->assertSame('foo', StringUtil::generateAlias('foo'));
        $this->assertSame('foo', StringUtil::generateAlias('FOO'));
        $this->assertSame('foo-bar', StringUtil::generateAlias('foo bar'));
        $this->assertSame('foo-bar', StringUtil::generateAlias('%foo&bar~'));
        $this->assertSame('foo-bar', StringUtil::generateAlias('foo&amp;bar'));
        $this->assertSame('foo-bar', StringUtil::generateAlias('foo-{{link::12}}-bar'));
        $this->assertSame('id-123', StringUtil::generateAlias('123'));
        $this->assertSame('123foo', StringUtil::generateAlias('123foo'));
        $this->assertSame('foo123', StringUtil::generateAlias('foo123'));
    }

    /**
     * @dataProvider parseSimpleTokensProvider
     */
    public function testParsesSimpleTokens(string $string, array $tokens, string $expected): void
    {
        $this->assertSame($expected, StringUtil::parseSimpleTokens($string, $tokens));
    }

    public function parseSimpleTokensProvider(): \Generator
    {
        yield 'Test regular token replacement' => [
            'This is my ##email##',
            ['email' => 'test@foobar.com'],
            'This is my test@foobar.com',
        ];

        yield 'Test regular token replacement is non greedy' => [
            'This is my ##email##,##email2##',
            ['email' => 'test@foobar.com', 'email2' => 'foo@test.com'],
            'This is my test@foobar.com,foo@test.com',
        ];

        yield 'Test token replacement with special characters (-)' => [
            'This is my ##e-mail##',
            ['e-mail' => 'test@foobar.com'],
            'This is my test@foobar.com',
        ];

        yield 'Test token replacement with special characters (&)' => [
            'This is my ##e&mail##',
            ['e&mail' => 'test@foobar.com'],
            'This is my test@foobar.com',
        ];

        yield 'Test token replacement with special characters (#)' => [
            'This is my ##e#mail##',
            ['e#mail' => 'test@foobar.com'],
            'This is my test@foobar.com',
        ];

        yield 'Test token replacement with token delimiter (##)' => [
            'This is my ##e##mail##',
            ['e##mail' => 'test@foobar.com'],
            'This is my ##e##mail##',
        ];

        yield 'Test comparisons (==) with regular characters (match)' => [
            'This is my {if email==""}match{endif}',
            ['email' => ''],
            'This is my match',
        ];

        yield 'Test comparisons (==) with regular characters (no match)' => [
            'This is my {if email==""}match{endif}',
            ['email' => 'test@foobar.com'],
            'This is my ',
        ];

        yield 'Test comparisons (!=) with regular characters (match)' => [
            'This is my {if email!=""}match{endif}',
            ['email' => 'test@foobar.com'],
            'This is my match',
        ];

        yield 'Test comparisons (!=) with regular characters (no match)' => [
            'This is my {if email!=""}match{endif}',
            ['email' => ''],
            'This is my ',
        ];

        yield 'Test comparisons (>) with regular characters (match)' => [
            'This is my {if value>0}match{endif}',
            ['value' => 5],
            'This is my match',
        ];

        yield 'Test comparisons (>) with regular characters (no match)' => [
            'This is my {if value>0}hello{endif}',
            ['value' => -8],
            'This is my ',
        ];

        yield 'Test comparisons (>=) with regular characters (match)' => [
            'This is my {if value>=0}match{endif}',
            ['value' => 5],
            'This is my match',
        ];

        yield 'Test comparisons (>=) with regular characters (no match)' => [
            'This is my {if value>=0}hello{endif}',
            ['value' => -8],
            'This is my ',
        ];

        yield 'Test comparisons (<) with regular characters (match)' => [
            'This is my {if value<0}match{endif}',
            ['value' => -5],
            'This is my match',
        ];

        yield 'Test comparisons (<) with regular characters (no match)' => [
            'This is my {if value<0}hello{endif}',
            ['value' => 9],
            'This is my ',
        ];

        yield 'Test comparisons (<=) with regular characters (match)' => [
            'This is my {if value<=0}match{endif}',
            ['value' => -5],
            'This is my match',
        ];

        yield 'Test comparisons (<=) with regular characters (no match)' => [
            'This is my {if value<=0}hello{endif}',
            ['value' => 9],
            'This is my ',
        ];

        yield 'Test comparisons (<) with special characters (match)' => [
            'This is my {if val&#ue<0}match{endif}',
            ['val&#ue' => -5],
            'This is my match',
        ];

        yield 'Test comparisons (<) with special characters (no match)' => [
            'This is my {if val&#ue<0}match{endif}',
            ['val&#ue' => 9],
            'This is my ',
        ];

        yield 'Test comparisons (===) with regular characters (match)' => [
            'This is my {if value===5}match{endif}',
            ['value' => 5],
            'This is my match',
        ];

        yield 'Test comparisons (===) with regular characters (no match)' => [
            'This is my {if value===5}match{endif}',
            ['value' => 5.0],
            'This is my ',
        ];

        yield 'Test comparisons (!==) with regular characters (match)' => [
            'This is my {if value!==5.0}match{endif}',
            ['value' => '5'],
            'This is my match',
        ];

        yield 'Test comparisons (!==) with regular characters (no match)' => [
            'This is my {if value!==5.0}match{endif}',
            ['value' => 5.0],
            'This is my ',
        ];

        yield 'Test whitespace in tokens not allowed and ignored' => [
            'This is my ##dumb token## you know',
            ['dumb token' => 'foobar'],
            'This is my ##dumb token## you know',
        ];

        yield 'Test if-tags insertion not evaluated' => [
            '##token##',
            ['token' => '{if token=="foo"}'],
            '{if token=="foo"}',
        ];

        yield 'Test if-tags insertion not evaluated with multiple tokens' => [
            '##token1####token2####token3##',
            ['token1' => '{', 'token2' => 'if', 'token3' => ' token=="foo"}'],
            '{if token=="foo"}',
        ];

        yield 'Test nested if-tag with " in value (match)' => [
            '{if value=="f"oo"}1{endif}{if value=="f\"oo"}2{endif}',
            ['value' => 'f"oo'],
            '12',
        ];

        yield 'Test else (match)' => [
            'This is my {if value=="foo"}match{else}else-match{endif}',
            ['value' => 'foo'],
            'This is my match',
        ];

        yield 'Test else (no match)' => [
            'This is my {if value!="foo"}match{else}else-match{endif}',
            ['value' => 'foo'],
            'This is my else-match',
        ];

        yield 'Test nested if (match)' => [
            '0{if value=="foo"}1{if value!="foo"}2{else}3{if value=="foo"}4{else}5{endif}6{endif}7{else}8{endif}9',
            ['value' => 'foo'],
            '0134679',
        ];

        yield 'Test nested if (no match)' => [
            '0{if value!="foo"}1{if value=="foo"}2{else}3{if value!="foo"}4{else}5{endif}6{endif}7{else}8{endif}9',
            ['value' => 'foo'],
            '089',
        ];

        yield 'Test nested elseif (match)' => [
            '0{if value=="bar"}1{elseif value=="foo"}2{else}3{if value=="bar"}4{elseif value=="foo"}5{else}6{endif}7{endif}8',
            ['value' => 'foo'],
            '028',
        ];

        yield 'Test nested elseif (no match)' => [
            '0{if value=="bar"}1{elseif value!="foo"}2{else}3{if value=="bar"}4{elseif value!="foo"}5{else}6{endif}7{endif}8',
            ['value' => 'foo'],
            '03678',
        ];

        yield 'Test special value chars \'=!<>;$()[] (match)' => [
            '{if value=="\'=!<>;$()[]"}match{else}no-match{endif}',
            ['value' => '\'=!<>;$()[]'],
            'match',
        ];

        yield 'Test special value chars \'=!<>;$()[] (no match)' => [
            '{if value=="\'=!<>;$()[]"}match{else}no-match{endif}',
            ['value' => '=!<>;$()[]'],
            'no-match',
        ];

        yield 'Test every elseif expression is skipped if first if statement evaluates to true' => [
            '{if value=="foobar"}Output 1{elseif value=="foobar"}Output 2{elseif value=="foobar"}Output 3{elseif value=="foobar"}Output 4{else}Output 5{endif}',
            ['value' => 'foobar'],
            'Output 1',
        ];

        yield 'Test every elseif expression is skipped if first elseif statement evaluates to true' => [
            '{if value!="foobar"}Output 1{elseif value=="foobar"}Output 2{elseif value=="foobar"}Output 3{elseif value=="foobar"}Output 4{elseif value=="foobar"}Output 5{else}Output 6{endif}',
            ['value' => 'foobar'],
            'Output 2',
        ];

        yield 'Test every elseif expression is skipped if second elseif statement evaluates to true' => [
            '{if value!="foobar"}Output 1{elseif value!="foobar"}Output 2{elseif value=="foobar"}Output 3{elseif value=="foobar"}Output 4{elseif value=="foobar"}Output 5{elseif value=="foobar"}Output 6{else}Output 7{endif}',
            ['value' => 'foobar'],
            'Output 3',
        ];

        yield 'Test {{iflng}} insert tag or similar constructs are ignored' => [
            '{if value=="foobar"}{{iflng::en}}hi{{iflng}}{{elseifinserttag::whodoesthisanyway}}{elseif value=="foo"}{{iflng::en}}hi2{{iflng}}{else}ok{endif}',
            ['value' => 'foobar'],
            '{{iflng::en}}hi{{iflng}}{{elseifinserttag::whodoesthisanyway}}',
        ];

        yield 'Test single white space characters in expressions' => [
            'This is my {if number > 5}match{endif}',
            ['number' => 6],
            'This is my match',
        ];

        yield 'Test multiple white space characters in expressions' => [
            'This is my {if email  ==  "test@foobar.com"  }match{endif}',
            ['email' => 'test@foobar.com'],
            'This is my match',
        ];

        yield 'Test does not support tabs in expressions' => [
            "This is my {if number\t>\t5}match{endif}",
            ['number' => 6],
            'This is my ',
        ];

        yield 'Test does not support line breaks in expressions' => [
            "This is my {if number\n>\n5}match{endif}",
            ['number' => 6],
            'This is my ',
        ];

        yield 'Test unknown token is treated as null (match)' => [
            'This is my {if foo===null}match{endif}',
            ['value' => 1],
            'This is my match',
        ];

        yield 'Test unknown token is treated as null (no match)' => [
            'This is my {if foo!="bar"}match{endif}',
            ['value' => 1],
            'This is my match',
        ];
    }

    /**
     * @dataProvider parseSimpleTokensCorrectNewlines
     */
    public function testHandlesLineBreaksWhenParsingSimpleTokens(string $string, array $tokens, string $expected): void
    {
        $this->assertSame($expected, StringUtil::parseSimpleTokens($string, $tokens));
        $this->assertSame($expected, StringUtil::parseSimpleTokens($string, $tokens, false));
    }

    public function parseSimpleTokensCorrectNewlines(): \Generator
    {
        yield 'Test newlines are kept end of token' => [
            "This is my ##token##\n",
            ['token' => 'foo'],
            "This is my foo\n",
        ];

        yield 'Test newlines are kept end in token' => [
            'This is my ##token##',
            ['token' => "foo\n"],
            "This is my foo\n",
        ];

        yield 'Test newlines are kept end in and after token' => [
            "This is my ##token##\n",
            ['token' => "foo\n"],
            "This is my foo\n\n",
        ];

        yield 'Test newlines are kept' => [
            "This is my \n ##newline## here",
            ['newline' => "foo\nbar\n"],
            "This is my \n foo\nbar\n here",
        ];

        yield 'Test newlines are removed after if tag' => [
            "\n{if token=='foo'}\nline2\n{endif}\n",
            ['token' => 'foo'],
            "\nline2\n",
        ];

        yield 'Test newlines are removed after else tag' => [
            "\n{if token!='foo'}{else}\nline2\n{endif}\n",
            ['token' => 'foo'],
            "\nline2\n",
        ];
    }

    /**
     * @dataProvider parseSimpleTokensDoesntExecutePhp
     */
    public function testDoesNotExecutePhpCode(string $string): void
    {
        $this->assertSame($string, StringUtil::parseSimpleTokens($string, []));
    }

    public function parseSimpleTokensDoesntExecutePhp(): \Generator
    {
        yield '(<?php)' => [
            'This <?php var_dump() ?> is a test.',
            false,
        ];

        yield '(<?=)' => [
            'This <?= $var ?> is a test.',
            false,
        ];

        yield '(<?)' => [
            'This <? var_dump() ?> is a test.',
            \PHP_VERSION_ID >= 70000,
        ];

        yield '(<%)' => [
            'This <% var_dump() ?> is a test.',
            \PHP_VERSION_ID >= 70000 || !\in_array(strtolower(\ini_get('asp_tags')), ['1', 'on', 'yes', 'true'], true),
        ];

        yield '(<script language="php">)' => [
            'This <script language="php"> var_dump() </script> is a test.',
            \PHP_VERSION_ID >= 70000,
        ];

        yield '(<script language=\'php\'>)' => [
            'This <script language=\'php\'> var_dump() </script> is a test.',
            \PHP_VERSION_ID >= 70000,
        ];
    }

    /**
     * @dataProvider parseSimpleTokensDoesntExecutePhpInToken
     */
    public function testDoesNotExecutePhpCodeInTokens(array $tokens): void
    {
        $this->assertSame($tokens['foo'], StringUtil::parseSimpleTokens('##foo##', $tokens));
    }

    public function parseSimpleTokensDoesntExecutePhpInToken(): \Generator
    {
        yield '(<?php)' => [
            ['foo' => 'This <?php var_dump() ?> is a test.'],
            false,
        ];

        yield '(<?=)' => [
            ['foo' => 'This <?= $var ?> is a test.'],
            false,
        ];

        yield '(<?)' => [
            ['foo' => 'This <? var_dump() ?> is a test.'],
            \PHP_VERSION_ID >= 70000,
        ];

        yield '(<%)' => [
            ['foo' => 'This <% var_dump() ?> is a test.'],
            \PHP_VERSION_ID >= 70000 || !\in_array(strtolower(\ini_get('asp_tags')), ['1', 'on', 'yes', 'true'], true),
        ];

        yield '(<script language="php">)' => [
            ['foo' => 'This <script language="php"> var_dump() </script> is a test.'],
            \PHP_VERSION_ID >= 70000,
        ];

        yield '(<script language=\'php\'>)' => [
            ['foo' => 'This <script language=\'php\'> var_dump() </script> is a test.'],
            \PHP_VERSION_ID >= 70000,
        ];
    }

    public function testDoesNotExecutePhpCodeInCombinedTokens(): void
    {
        $data = [
            'open' => '<',
            'open2' => '?php echo "I am evil";',
            'close' => '?>',
        ];

        $this->assertSame(
            'This is <?php echo "I am evil";?> evil',
            StringUtil::parseSimpleTokens('This is ##open####open2####close## evil', $data)
        );
    }

    /**
     * @dataProvider parseSimpleTokensInvalidComparison
     */
    public function testFailsIfTheComparisonOperatorIsInvalid(string $string): void
    {
        $this->expectException('InvalidArgumentException');

        StringUtil::parseSimpleTokens($string, ['foo' => 'bar']);
    }

    public function parseSimpleTokensInvalidComparison(): \Generator
    {
        yield 'PHP constants are not allowed' => ['{if foo==__FILE__}{endif}'];
        yield 'Not closed string (")' => ['{if foo=="bar}{endif}'];
        yield 'Not closed string (\')' => ['{if foo==\'bar}{endif}'];
        yield 'Additional chars after string ("/)' => ['{if foo=="bar"/}{endif}'];
        yield 'Additional chars after string (\'/)' => ['{if foo==\'bar\'/}{endif}'];
        yield 'Additional chars after string ("*)' => ['{if foo=="bar"*}{endif}'];
        yield 'Additional chars after string (\'*)' => ['{if foo==\'bar\'*}{endif}'];
        yield 'Unknown operator (=)' => ['{if foo="bar"}{endif}'];
        yield 'Unknown operator (====)' => ['{if foo===="bar"}{endif}'];
        yield 'Unknown operator (<==)' => ['{if foo<=="bar"}{endif}'];
    }

    public function testStripsTheRootDirectory(): void
    {
        $this->assertSame('', StringUtil::stripRootDir($this->getFixturesDir().'/'));
        $this->assertSame('', StringUtil::stripRootDir($this->getFixturesDir().'\\'));
        $this->assertSame('foo', StringUtil::stripRootDir($this->getFixturesDir().'/foo'));
        $this->assertSame('foo', StringUtil::stripRootDir($this->getFixturesDir().'\foo'));
        $this->assertSame('foo/', StringUtil::stripRootDir($this->getFixturesDir().'/foo/'));
        $this->assertSame('foo\\', StringUtil::stripRootDir($this->getFixturesDir().'\foo\\'));
        $this->assertSame('foo/bar', StringUtil::stripRootDir($this->getFixturesDir().'/foo/bar'));
        $this->assertSame('foo\bar', StringUtil::stripRootDir($this->getFixturesDir().'\foo\bar'));
        $this->assertSame('../../foo/bar', StringUtil::stripRootDir($this->getFixturesDir().'/../../foo/bar'));
    }

    public function testFailsIfThePathIsOutsideTheRootDirectory(): void
    {
        $this->expectException('InvalidArgumentException');

        StringUtil::stripRootDir('/foo');
    }

    public function testFailsIfThePathIsTheParentFolder(): void
    {
        $this->expectException('InvalidArgumentException');

        StringUtil::stripRootDir(\dirname($this->getFixturesDir()).'/');
    }

    public function testFailsIfThePathDoesNotMatch(): void
    {
        $this->expectException('InvalidArgumentException');

        StringUtil::stripRootDir($this->getFixturesDir().'foo/');
    }

    public function testFailsIfThePathHasNoTrailingSlash(): void
    {
        $this->expectException('InvalidArgumentException');

        StringUtil::stripRootDir($this->getFixturesDir());
    }

    public function testHandlesFalseyValuesWhenDecodingEntities(): void
    {
        $this->assertSame('0', StringUtil::decodeEntities(0));
        $this->assertSame('0', StringUtil::decodeEntities('0'));
        $this->assertSame('', StringUtil::decodeEntities(''));
        $this->assertSame('', StringUtil::decodeEntities(false));
        $this->assertSame('', StringUtil::decodeEntities(null));
    }

    /**
     * @dataProvider trimsplitProvider
     */
    public function testSplitsAndTrimsStrings(string $pattern, string $string, array $expected): void
    {
        $this->assertSame($expected, StringUtil::trimsplit($pattern, $string));
    }

    public function trimsplitProvider(): \Generator
    {
        yield 'Test regular split' => [
            ',',
            'foo,bar',
            ['foo', 'bar'],
        ];

        yield 'Test split with trim' => [
            ',',
            " \n \r \t foo \n \r \t , \n \r \t bar \n \r \t ",
            ['foo', 'bar'],
        ];

        yield 'Test regex split' => [
            '[,;]',
            'foo,bar;baz',
            ['foo', 'bar', 'baz'],
        ];

        yield 'Test regex split with trim' => [
            '[,;]',
            " \n \r \t foo \n \r \t , \n \r \t bar \n \r \t ; \n \r \t baz \n \r \t ",
            ['foo', 'bar', 'baz'],
        ];

        yield 'Test split cache bug 1' => [
            ',',
            ',foo,,bar',
            ['', 'foo', '', 'bar'],
        ];

        yield 'Test split cache bug 2' => [
            ',,',
            'foo,,bar',
            ['foo', 'bar'],
        ];
    }

    /**
     * @dataProvider validEncodingsProvider
     */
    public function testConvertsEncodingOfAString($string, string $toEncoding, $expected, $fromEncoding = null): void
    {
        // Enforce substitute character for these tests (see #5011)
        mb_substitute_character(0x3F);

        $result = StringUtil::convertEncoding($string, $toEncoding, $fromEncoding);

        $this->assertSame($expected, $result);
    }

    public function validEncodingsProvider(): \Generator
    {
        yield 'From UTF-8 to ISO-8859-1' => [
            '𝚏ōȏճăᴦ',
            'ISO-8859-1',
            '??????',
            'UTF-8',
        ];

        yield 'From ISO-8859-1 to UTF-8' => [
            '𝚏ōȏճăᴦ',
            'UTF-8',
            'ðÅÈÕ³Äá´¦',
            'ISO-8859-1',
        ];

        yield 'From UTF-8 to ASCII' => [
            '𝚏ōȏճăᴦbaz',
            'ASCII',
            '??????baz',
            'UTF-8',
        ];

        yield 'Same encoding with UTF-8' => [
            '𝚏ōȏճăᴦ',
            'UTF-8',
            '𝚏ōȏճăᴦ',
            'UTF-8',
        ];

        yield 'Same encoding with ASCII' => [
            'foobar',
            'ASCII',
            'foobar',
            'ASCII',
        ];

        yield 'Empty string' => [
            '',
            'UTF-8',
            '',
        ];

        yield 'Integer argument' => [
            42,
            'UTF-8',
            '42',
            'ASCII',
        ];

        yield 'Integer argument with same encoding' => [
            42,
            'UTF-8',
            '42',
            'UTF-8',
        ];

        yield 'Float argument with same encoding' => [
            13.37,
            'ASCII',
            '13.37',
            'ASCII',
        ];

        yield 'String with blanks' => [
            '  ',
            'UTF-8',
            '  ',
        ];

        yield 'String "0"' => [
            '0',
            'UTF-8',
            '0',
        ];

        yield 'Stringable argument' => [
            new class('foobar') {
                private $value;

                public function __construct(string $value)
                {
                    $this->value = $value;
                }

                public function __toString(): string
                {
                    return $this->value;
                }
            },
            'UTF-8',
            'foobar',
            'UTF-8',
        ];
    }

    /**
     * @group legacy
     *
     * @dataProvider invalidEncodingsProvider
     *
     * @expectedDeprecation Passing a non-stringable argument to StringUtil::convertEncoding() has been deprecated %s.
     */
    public function testReturnsEmptyStringAndTriggersDeprecationWhenEncodingNonStringableValues($value): void
    {
        $result = StringUtil::convertEncoding($value, 'UTF-8');

        $this->assertSame('', $result);
    }

    public function invalidEncodingsProvider(): \Generator
    {
        yield 'Array' => [[]];
        yield 'Non-stringable object' => [new \stdClass()];
    }
}
