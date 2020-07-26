<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Util;

use Contao\CoreBundle\Tests\Fixtures\IteratorAggregateStub;
use Contao\CoreBundle\Util\SimpleTokenExpressionLanguage;
use Contao\CoreBundle\Util\SimpleTokenParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class SimpleTokenParserTest extends TestCase
{
    /**
     * @dataProvider parseSimpleTokensProvider
     */
    public function testParsesSimpleTokens(string $string, array $tokens, string $expected): void
    {
        $this->assertSame($expected, $this->getParser()->parseTokens($string, $tokens));
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

        yield 'Test escaping works correctly' => [
            '{if value=="f\"oo"}match{endif}',
            ['value' => 'f"oo'],
            'match',
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

        yield 'Test if value in array' => [
            '{if value in ["foobar", "test", "other-value"]}match{else}no-match{endif}',
            ['value' => 'foobar'],
            'match',
        ];

        yield 'Test if value not in array' => [
            '{if value not in ["foobar", "test", "other-value"]}match{else}no-match{endif}',
            ['value' => 'whatever'],
            'match',
        ];

        yield 'Test OR operator (match)' => [
            '{if value == "whatever" || value == "foobar"}match{else}no-match{endif}',
            ['value' => 'whatever'],
            'match',
        ];

        yield 'Test OR operator (no-match)' => [
            '{if value == "whatever" || value == "foobar"}match{else}no-match{endif}',
            ['value' => 'irrelevant'],
            'no-match',
        ];

        yield 'Test AND operator (match)' => [
            '{if value == "whatever" && value matches "/whatever/"}match{else}no-match{endif}',
            ['value' => 'whatever'],
            'match',
        ];

        yield 'Test AND operator (no-match)' => [
            '{if value == "irrelevant" && value matches "/whatever/"}match{else}no-match{endif}',
            ['value' => 'irrelevant'],
            'no-match',
        ];

        yield 'Test tokens in tokens are handled correctly' => [
            '{if firstname == "myname"}match{else}no-match{endif}',
            ['name' => 'myname mylastname', 'firstname' => 'myname'],
            'match',
        ];

        yield 'Test ignores unknown tokens' => [
            'This is my ##token## that ##remains##',
            ['token' => 'value'],
            'This is my value that ##remains##',
        ];
    }

    /**
     * @group legacy
     * @dataProvider parseSimpleTokensLegacyProvider
     *
     * @expectedDeprecation Since contao/core-bundle 4.10: Using tokens that are not valid PHP variables has been deprecated %s.
     */
    public function testParsesSimpleTokensLegacy(string $string, array $tokens, string $expected): void
    {
        $this->assertSame($expected, $this->getParser()->parseTokens($string, $tokens));
    }

    public function parseSimpleTokensLegacyProvider(): \Generator
    {
        yield 'Test token replacement with token delimiter (##)' => [
            'This is my ##e##mail##',
            ['e##mail' => 'test@foobar.com'],
            'This is my ##e##mail##',
        ];

        yield 'Test whitespace in tokens not allowed and ignored' => [
            'This is my ##dumb token## you know',
            ['dumb token' => 'foobar'],
            'This is my ##dumb token## you know',
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

        yield 'Test indexed token replacement' => [
            'This is my ##0##,##1##',
            ['test@foobar.com', 'foo@test.com'],
            'This is my test@foobar.com,foo@test.com',
        ];
    }

    /**
     * @dataProvider parseSimpleTokensCorrectNewlines
     */
    public function testHandlesLineBreaksWhenParsingSimpleTokens(string $string, array $tokens, string $expected): void
    {
        $this->assertSame($expected, $this->getParser()->parseTokens($string, $tokens));
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
        $this->assertSame($string, $this->getParser()->parseTokens($string, []));
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
            \PHP_VERSION_ID >= 70000 || !\in_array(strtolower(ini_get('asp_tags')), ['1', 'on', 'yes', 'true'], true),
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
        $this->assertSame($tokens['foo'], $this->getParser()->parseTokens('##foo##', $tokens));
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
            \PHP_VERSION_ID >= 70000 || !\in_array(strtolower(ini_get('asp_tags')), ['1', 'on', 'yes', 'true'], true),
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
            $this->getParser()->parseTokens('This is ##open####open2####close## evil', $data)
        );
    }

    public function testConstantFunctionOfExpressionLanguageIsDisabled(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Cannot use the constant() function in the expression for security reasons.');

        $this->getParser()->parseTokens('{if constant("PHP_VERSION") > 7}match{else}no-match{endif}', []);
    }

    /**
     * @dataProvider parseSimpleTokensInvalidComparison
     */
    public function testFailsIfTheComparisonOperatorIsInvalid(string $string): void
    {
        $this->expectException('InvalidArgumentException');

        $this->getParser()->parseTokens($string, ['foo' => 'bar']);
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

    public function testParseSimpleTokenWithCustomExtensionProvider(): void
    {
        $stringExtensionProvider = new class() implements ExpressionFunctionProviderInterface {
            public function getFunctions(): array
            {
                return [ExpressionFunction::fromPhp('strtoupper')];
            }
        };

        $simpleTokenParser = $this->getParser(
            new SimpleTokenExpressionLanguage(null, new IteratorAggregateStub([$stringExtensionProvider]))
        );

        $this->assertSame(
            'Custom function evaluated!',
            $simpleTokenParser->parseTokens("Custom function {if strtoupper(token) === 'FOO'}evaluated!{endif}", ['token' => 'foo'])
        );
    }

    private function getParser(SimpleTokenExpressionLanguage $expressionLanguage = null): SimpleTokenParser
    {
        if (null === $expressionLanguage) {
            $expressionLanguage = new SimpleTokenExpressionLanguage();
        }

        return new SimpleTokenParser($expressionLanguage);
    }
}
