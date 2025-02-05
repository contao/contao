<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\String;

use Contao\CoreBundle\String\HtmlAttributes;
use Contao\CoreBundle\Tests\TestCase;

class HtmlAttributesTest extends TestCase
{
    /**
     * @dataProvider provideAttributeStrings
     */
    public function testParsesAttributeStrings(string $attributeString, array $expectedAttributes): void
    {
        $attributes = new HtmlAttributes($attributeString);

        $this->assertSame($expectedAttributes, iterator_to_array($attributes));

        $attributes = new HtmlAttributes($attributes->toString());

        $this->assertSame($expectedAttributes, iterator_to_array($attributes));
    }

    public static function provideAttributeStrings(): iterable
    {
        yield 'basic' => [
            'foo="bar" baz="42"',
            ['foo' => 'bar', 'baz' => '42'],
        ];

        yield 'no value' => [
            'foo baz="42"',
            ['foo' => '', 'baz' => '42'],
        ];

        yield 'no quotes' => [
            'foo=bar baz=42',
            ['foo' => 'bar', 'baz' => '42'],
        ];

        yield 'additional spaces' => [
            'foo =  bar  baz = "42"  ',
            ['foo' => 'bar', 'baz' => '42'],
        ];

        yield 'new lines and tabs' => [
            "\n\t\rfoo\n\t\r=\n\t\rbar\n\t\rbaz\n\t\r=\n\t\r'42'\n\t\r",
            ['foo' => 'bar', 'baz' => '42'],
        ];

        yield 'AlpineJS attributes' => [
            '@click="open = true" x-on:click="open = !open"',
            ['@click' => 'open = true', 'x-on:click' => 'open = !open'],
        ];

        yield 'Vue.js attributes' => [
            'v-html="raw" v-bind:id="id" :disabled="dis" :[attr]="val" :[\'data-\'+key]="val"',
            ['v-html' => 'raw', 'v-bind:id' => 'id', ':disabled' => 'dis', ':[attr]' => 'val', ":['data-'+key]" => 'val'],
        ];

        yield 'Vue.js events' => [
            '@click.prevent="start"  @[event]="run"',
            ['@click.prevent' => 'start', '@[event]' => 'run'],
        ];

        yield 'special html parsing rules' => [
            "/X===.._-/\n/Y/-== bool='' ===",
            ['x' => '==.._-/', 'y' => '', '-' => '=', 'bool' => '', '=' => '='],
        ];

        yield 'terminate closing and keep opening tags as per html parsing rules' => [
            '/</<attr=<value foo="bar>"baz>foo',
            ['<' => '', '<attr' => '<value', 'foo' => 'bar>', 'baz' => ''],
        ];

        yield 'skip unclosed attributes completely' => [
            'foo="bar" baz="42 bar=\'123\'> <div class=H4x0r',
            ['foo' => 'bar'],
        ];

        yield 'decode values' => [
            'foo=&quot; bar="b&auml;z" baz=&ZeroWidthSpace; &lt;=&gt;',
            ['foo' => '"', 'bar' => 'bäz', 'baz' => "\u{200B}", '&lt;' => '>'],
        ];

        yield 'no attributes' => [
            '',
            [],
        ];

        yield 'just spaces' => [
            '  ',
            [],
        ];

        yield 'duplicate attributes' => [
            'foo=1 bar=2 bar=3 FOO=4',
            ['foo' => '1', 'bar' => '2'],
        ];

        yield 'complex styles' => [
            'style=" content:&quot; foo : bar ; baz ( &quot;; foo : url(https://example.com/foo;bar) ;color:red" STYLE=color:blue',
            ['style' => 'content: " foo : bar ; baz ( "; foo: url(https://example.com/foo;bar); color: red;'],
        ];

        yield 'double styles' => [
            'style="color: fallback; color: cutting-edge(foo);"',
            ['style' => 'color: fallback; color: cutting-edge(foo);'],
        ];

        yield 'inline svg single quotes' => [
            'style="background: url(\'data:image/svg+xml;utf8,<svg/>\');"',
            ['style' => "background: url('data:image/svg+xml;utf8,<svg/>');"],
        ];

        yield 'inline svg double quotes' => [
            'style="background: url(&quot;data:image/svg+xml;utf8,<svg/>&quot;);"',
            ['style' => 'background: url("data:image/svg+xml;utf8,<svg/>");'],
        ];

        yield 'inline svg no quotes' => [
            'style="background: url(data:image/svg+xml;utf8,<svg/>);"',
            ['style' => 'background: url(data:image/svg+xml;utf8,<svg/>);'],
        ];

        yield 'escaped name' => [
            'style="c\6F lor: red;"',
            ['style' => 'c\6F lor: red;'],
        ];

        yield 'escaped name colon' => [
            'style="--foo-16\:9: bar"',
            ['style' => '--foo-16\:9: bar;'],
        ];

        yield 'escaped name semicolon' => [
            'style="--foo\;bar: baz"',
            ['style' => '--foo\;bar: baz;'],
        ];

        yield 'escaped value' => [
            'style="color: r\&quot;ed"',
            ['style' => 'color: r\"ed;'],
        ];

        yield 'escaped string' => [
            "style=\"color: 'r\\'ed'\"",
            ['style' => "color: 'r\\'ed';"],
        ];

        yield 'escaped string hacking' => [
            "style=\"color: 'r\\'; eval : foo '\"",
            ['style' => "color: 'r\\'; eval : foo ';"],
        ];

        yield 'escaped string hacking double quotes' => [
            "style='color: \"r\\\"; eval : foo \"'",
            ['style' => 'color: "r\\"; eval : foo ";'],
        ];

        yield 'newline' => [
            "style=\"content:'new\\\nline'\"",
            ['style' => "content: 'new\\\nline';"],
        ];

        yield 'invalid block' => [
            'style="{ foo: red } bar: green; baz: blue"',
            ['style' => 'baz: blue;'],
        ];

        yield 'completely invalid' => [
            'style="{ foo: red; bar: green; baz: blue"',
            [],
        ];

        yield 'leading comment' => [
            'style="/* Farbe: */ color: red"',
            ['style' => '/* Farbe: */ color: red;'],
        ];

        yield 'trailing comment' => [
            'style="color: red /* Rot; */"',
            ['style' => 'color: red /* Rot; */;'],
        ];

        yield 'excessive comments in property and value' => [
            'style="; /* ; Farbe << */ color /* >> */ : /* Rot */ red /* foo: bar */ "',
            ['style' => '/* ; Farbe << */ color /* >> */: /* Rot */ red /* foo: bar */;'],
        ];
    }

    public function testCreatesAttributesFromIterable(): void
    {
        $properties = [
            'foO_bAr' => 'bar',
            'bar-bar' => 42,
            'BAZ123' => true,
            'other' => null,
        ];

        $expectedProperties = [
            'foo_bar' => 'bar',
            'bar-bar' => '42',
            'baz123' => '',
            'other' => '',
        ];

        $this->assertSame(
            $expectedProperties,
            iterator_to_array(new HtmlAttributes($properties)),
        );

        $this->assertSame(
            $expectedProperties,
            iterator_to_array(new HtmlAttributes(new \ArrayIterator($properties))),
        );
    }

    public function testCreatesAttributesFromAttributesClass(): void
    {
        $attributes = new HtmlAttributes([
            'foO_bAr' => 'bar',
            'bar-bar' => 42,
            'BAZ123' => true,
            'other' => null,
        ]);

        $this->assertSame(iterator_to_array($attributes), iterator_to_array(new HtmlAttributes($attributes)));
    }

    public function testMergesAttributesFromAttributesClass(): void
    {
        $attributesA = new HtmlAttributes([
            'foO_bAr' => 'bar',
            'class' => 'class1',
            'style' => 'COLOR: RED',
        ]);

        $attributesB = new HtmlAttributes([
            'bar-bar' => 42,
            'foo-foo' => 'foo',
            'style' => 'c\6F lor: green; c\olor: darkgreen',
        ]);

        $attributesC = 'BAZ123 = "" class="class2" style=background:red';

        $attributesD = [
            'other' => null,
            'foo-foo' => false,
            'class' => 'class1 class3',
            'style' => 'color: blue',
        ];

        $expectedProperties = [
            'foo_bar' => 'bar',
            'class' => 'class1 class2 class3',
            'style' => 'color: blue; background: red;',
            'bar-bar' => '42',
            'baz123' => '',
            'other' => '',
        ];

        $attributes = $attributesA->mergeWith($attributesB)->mergeWith($attributesC)->mergeWith($attributesD);

        $this->assertSame($expectedProperties, iterator_to_array($attributes));
    }

    public function testSkipsInvalidAttributeNamesWhenParsingString(): void
    {
        $attributes = new HtmlAttributes("foo=bar f\xC2o\x00o=b&#xC2;ar baz=42");

        $this->assertSame(['foo' => 'bar', "f\u{FFFD}o\u{FFFD}o" => "b\u{C2}ar", 'baz' => '42'], iterator_to_array($attributes));
    }

    /**
     * @dataProvider provideInvalidAttributeNames
     */
    public function testRejectsInvalidAttributeNamesWhenConstructingFromArray(string $name): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/An HTML attribute name must be valid UTF-8 and not contain the characters >, \/, = or whitespace, got ".*"\./');

        new HtmlAttributes([$name => 'bar']);
    }

    /**
     * @dataProvider provideInvalidAttributeNames
     */
    public function testRejectsInvalidAttributeNamesWhenSetting(string $name): void
    {
        $attributes = new HtmlAttributes();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/An HTML attribute name must be valid UTF-8 and not contain the characters >, \/, = or whitespace, got ".*"\./');

        $attributes->set($name, 'bar');
    }

    public static function provideInvalidAttributeNames(): iterable
    {
        yield 'invalid non-utf8 character' => ["f\xC2"];
        yield 'empty string' => [''];
        yield 'greater-than sign' => ['>'];
        yield 'includes a slash' => ['f/oo'];
        yield 'includes an equals sign' => ['f=oo'];
        yield 'includes a space' => ['f oo'];
    }

    public function testSetAndUnsetProperties(): void
    {
        $attributes = new HtmlAttributes();

        $this->assertSame([], iterator_to_array($attributes));

        // Set and overwrite properties
        $attributes->set('foo', 'bar');
        $attributes->set('bar', 42);
        $attributes->set('foo', '{{baz}}');

        $this->assertSame(['foo' => '{{baz}}', 'bar' => '42'], iterator_to_array($attributes));

        // Unset existing and non-existing properties
        $attributes->unset('foo');
        $attributes->unset('other');

        $this->assertSame(['bar' => '42'], iterator_to_array($attributes));

        // Set values that should get ignored
        $attributes->setIfExists('a', null);
        $attributes->setIfExists('b', false);
        $attributes->setIfExists('c', 0);
        $attributes->setIfExists('d', '');
        $attributes->setIfExists('s', $this->toStringable(''));

        // Set values that should be used
        $attributes->setIfExists('e', ' ');
        $attributes->setIfExists('f', 'abc');
        $attributes->setIfExists('g', $this->toStringable(' '));

        $this->assertSame(['bar' => '42', 'e' => ' ', 'f' => 'abc', 'g' => ' '], iterator_to_array($attributes));

        // Unset properties by setting them to false
        $attributes->set('bar', false);
        $attributes->setIfExists('f', false); // should not alter the list
        $attributes->mergeWith(['g' => false]);

        $this->assertSame(['e' => ' ', 'f' => 'abc'], iterator_to_array($attributes));

        // Uppercase names
        $attributes->set('E', 'UPPER');
        $attributes->unset('F');

        $this->assertSame(['e' => 'UPPER'], iterator_to_array($attributes));
    }

    public function testSetAndUnsetConditionalProperties(): void
    {
        $truthyValues = [
            true,
            1,
            'true',
            '1',
            ['test'],
            new \stdClass(),
            $this->toStringable('foo'),
        ];

        $falsyValues = [
            null,
            false,
            0,
            '',
            [],
            $this->toStringable(''),
        ];

        // Test truthy values fulfil the condition
        foreach ($truthyValues as $value) {
            $attributes = new HtmlAttributes();

            $attributes->set('data-feature', condition: $value);
            $this->assertSame(['data-feature' => ''], iterator_to_array($attributes));

            $attributes->unset('data-feature', condition: $value);
            $this->assertSame([], iterator_to_array($attributes));
        }

        // Test falsy values do not fulfil the condition
        foreach ($falsyValues as $value) {
            $attributes = new HtmlAttributes(['data-feature' => '']);

            $attributes->set('data-foo', condition: $value);
            $this->assertSame(['data-feature' => ''], iterator_to_array($attributes));

            $attributes->unset('data-feature', condition: $value);
            $this->assertSame(['data-feature' => ''], iterator_to_array($attributes));
        }
    }

    public function testAddAndRemoveClasses(): void
    {
        // Whitespaces should get normalized by default
        $attributes = new HtmlAttributes(['class' => " \ffoo  bar\tother1\nother2\r  "]);

        $this->assertSame('foo bar other1 other2', $attributes['class']);

        // And remove classes
        $attributes->addClass('baz');
        $attributes->addClass('foo foobar');
        $attributes->addClass(['foo2', 'foobar2']);
        $attributes->removeClass(' other1');
        $attributes->removeClass('thing other2');
        $attributes->removeClass(['foo2', ' foobar ']);

        $this->assertSame('foo bar baz foobar2', $attributes['class']);
    }

    public function testAddAndRemoveConditionalClasses(): void
    {
        $attributes = new HtmlAttributes();

        $attributes->addClass('a', null);
        $attributes->addClass('b', false);
        $attributes->addClass('c', 0);
        $attributes->addClass('d', '');

        $this->assertSame([], iterator_to_array($attributes));

        $attributes->addClass('a', condition: true);
        $attributes->addClass('b', condition: 1);
        $attributes->addClass('c', condition: 'true');
        $attributes->addClass('d', condition: '1');

        $this->assertSame(['class' => 'a b c d'], iterator_to_array($attributes));

        $attributes->removeClass('a', null);
        $attributes->removeClass('b', false);
        $attributes->removeClass('c', 0);
        $attributes->removeClass('d', '');

        $this->assertSame(['class' => 'a b c d'], iterator_to_array($attributes));

        $attributes->removeClass('a', condition: true);
        $attributes->removeClass('b', condition: 1);
        $attributes->removeClass('c', condition: 'true');
        $attributes->removeClass('d', condition: '1');

        $this->assertSame([], iterator_to_array($attributes));
    }

    public function testDoesNotOutputEmptyClassAttribute(): void
    {
        $attributes = new HtmlAttributes();
        $attributes->addClass('');

        $this->assertSame('', $attributes->toString());

        $attributes->addClass('foo');
        $attributes->removeClass('foo');

        $this->assertSame('', $attributes->toString());
    }

    public function testAddAndRemoveStyles(): void
    {
        // Whitespaces should get normalized by default
        $attributes = new HtmlAttributes(['style' => " \ffoo:  bar;\tother1\n:other2\r  ;"]);

        $this->assertSame('foo: bar; other1: other2;', $attributes['style']);

        // And remove classes
        $attributes->addStyle('baz: 1');
        $attributes->addStyle('foo: red; foobar: baz');
        $attributes->addStyle(['foo2: bar', 'foo3: bar; foo4: bar', 'foobar' => 'red']);
        $attributes->removeStyle('other2');
        $attributes->removeStyle('thing: x; other1: red;');
        $attributes->removeStyle(['foo3 : x', ' foo4 ']);

        $this->assertSame('foo: red; baz: 1; foobar: red; foo2: bar;', $attributes['style']);

        $attributes->removeStyle('/**/ foo /**/');
        $attributes->removeStyle(['/**/ foo2 /**/ : x']);
        $attributes->removeStyle('/* baz */ foobar');

        $this->assertSame('baz: 1;', $attributes['style']);
    }

    public function testAddAndRemoveCaseSensitiveStyles(): void
    {
        $attributes = new HtmlAttributes(['style' => 'color:red;--color:red']);

        $this->assertSame('color: red; --color: red;', $attributes['style']);

        $attributes->addStyle('COLOR:RED');

        $this->assertSame(
            'COLOR: RED; --color: red;',
            $attributes['style'],
            'Case-insensitive property should get overwritten',
        );

        $attributes->addStyle('--COLOR:RED');

        $this->assertSame(
            'COLOR: RED; --color: red; --COLOR: RED;',
            $attributes['style'],
            'Case-sensitive property should get appended',
        );

        $attributes->removeStyle('color');
        $this->assertSame('--color: red; --COLOR: RED;', $attributes['style']);

        $attributes->removeStyle('--COLOR');
        $this->assertSame('--color: red;', $attributes['style']);

        $attributes->removeStyle('--color');
        $this->assertFalse($attributes->offsetExists('style'));
    }

    public function testAddAndRemoveDoubleStyles(): void
    {
        $attributes = new HtmlAttributes();

        $attributes->addStyle('baz: 1; baz: 2');
        $this->assertSame('baz: 1; baz: 2;', $attributes['style']);

        $attributes->addStyle('baz: 3; color: red; baz: 4');
        $this->assertSame('baz: 3; baz: 4; color: red;', $attributes['style']);

        $attributes->removeStyle('baz');
        $this->assertSame('color: red;', $attributes['style']);

        $attributes->removeStyle('color: a; color: b; color: c');
        $this->assertFalse($attributes->offsetExists('style'));
    }

    public function testAddAndRemoveConditionalStyles(): void
    {
        $attributes = new HtmlAttributes();

        $attributes->addStyle('foo: a', null);
        $attributes->addStyle('foo: b', false);
        $attributes->addStyle('foo: c', 0);
        $attributes->addStyle('foo: d', '');

        $this->assertSame([], iterator_to_array($attributes));

        $attributes->addStyle('a: foo', condition: true);
        $attributes->addStyle('b: foo', condition: 1);
        $attributes->addStyle('c: foo', condition: 'true');
        $attributes->addStyle('d: foo', condition: '1');

        $this->assertSame(['style' => 'a: foo; b: foo; c: foo; d: foo;'], iterator_to_array($attributes));

        $attributes->removeStyle('a: foo', null);
        $attributes->removeStyle('b: foo', false);
        $attributes->removeStyle('c', 0);
        $attributes->removeStyle('d: foo', '');

        $this->assertSame(['style' => 'a: foo; b: foo; c: foo; d: foo;'], iterator_to_array($attributes));

        $attributes->removeStyle('a: foo', condition: true);
        $attributes->removeStyle('b', condition: 1);
        $attributes->removeStyle('c: foo', condition: 'true');
        $attributes->removeStyle(['d'], condition: '1');

        $this->assertSame([], iterator_to_array($attributes));
    }

    public function testDoesNotOutputEmptyStyleAttribute(): void
    {
        $attributes = new HtmlAttributes();
        $attributes->addStyle('');

        $this->assertSame('', $attributes->toString());

        $attributes->addStyle('foo: bar');
        $attributes->addStyle('invalid style');
        $attributes->removeStyle('foo');

        $this->assertSame('', $attributes->toString());
    }

    public function testAllowsChaining(): void
    {
        $attributes = (new HtmlAttributes())
            ->addClass('block headline foo')
            ->removeClass('foo')
            ->set('style', 'color: red;')
            ->setIfExists('data-foo', null)
        ;

        $this->assertSame(' class="block headline" style="color: red;"', (string) $attributes);
    }

    public function testEscapesAttributesWhenRenderingAsString(): void
    {
        $attributes = new HtmlAttributes([
            'A' => 'A B C',
            'b' => '{{b}}',
            'c' => 'foo&bar',
            'd' => 'foo&amp;bar',
            'e' => '&ZeroWidthSpace;',
            'property-without-value' => null,
            'data-json' => json_encode('foo &quot; bar'),
        ]);

        $expectedString = 'a="A B C" b="&#123;&#123;b&#125;&#125;" c="foo&amp;bar" d="foo&amp;bar" e="&ZeroWidthSpace;" property-without-value data-json="&quot;foo &amp;quot; bar&quot;"';

        $this->assertSame(" $expectedString", (string) $attributes);
        $this->assertSame(" $expectedString", $attributes->toString());
        $this->assertSame($expectedString, $attributes->toString(false));

        // With double encoding
        $this->assertSame($attributes, $attributes->setDoubleEncoding(true));
        $expectedString = 'a="A B C" b="&#123;&#123;b&#125;&#125;" c="foo&amp;bar" d="foo&amp;amp;bar" e="&amp;ZeroWidthSpace;" property-without-value data-json="&quot;foo &amp;quot; bar&quot;"';

        $this->assertSame(" $expectedString", (string) $attributes);
        $this->assertSame(" $expectedString", $attributes->toString());
        $this->assertSame($expectedString, $attributes->toString(false));
    }

    public function testStripsLeadingWhitespaceIfEmpty(): void
    {
        $this->assertSame('', (string) new HtmlAttributes());
        $this->assertSame('', (new HtmlAttributes())->toString());
        $this->assertSame('', (new HtmlAttributes())->toString(false));
    }

    /**
     * @dataProvider provideBooleanAttributes
     */
    public function testCorrectlySerializesBooleanAttributes(array $attrArray, string $attrString): void
    {
        $this->assertSame($attrString, (new HtmlAttributes($attrArray))->toString(false));
        $this->assertSame($attrArray, iterator_to_array(new HtmlAttributes($attrString)));
    }

    public static function provideBooleanAttributes(): iterable
    {
        yield [
            ['foo' => ''],
            'foo',
        ];

        yield [
            ['foo' => '', '=' => ''],
            'foo="" =',
        ];

        yield [
            ['=' => '', 'foo' => ''],
            '= foo',
        ];

        yield [
            ['=' => '', '=foo' => ''],
            '=="" =foo',
        ];

        yield [
            ['=foo' => '', '=' => ''],
            '=foo="" =',
        ];

        yield [
            ['1' => '', '=' => '', '2' => ''],
            '1="" = 2',
        ];
    }

    public function testThrowsIfValueContainsInvalidUtf8Characters(): void
    {
        $attributes = new HtmlAttributes(['foo' => "\xC2"]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The value of property "foo" is not a valid UTF-8 string.');

        $attributes->toString();
    }

    public function testArrayAccess(): void
    {
        $attributes = new HtmlAttributes(['foo' => '{{bar}}', 'baz' => 42]);

        $this->assertSame('{{bar}}', $attributes['foo']);
        $this->assertSame('42', $attributes['baz']);
        $this->assertTrue(isset($attributes['foo']));
        $this->assertFalse(isset($attributes['foobar']));

        $attributes['other'] = true;
        $attributes['baz'] = false;
        unset($attributes['foo']);

        $this->assertSame(['other' => ''], iterator_to_array($attributes));
    }

    public function testThrowsWhenSettingInvalidNameUsingArrayAccess(): void
    {
        $attributes = new HtmlAttributes();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('An HTML attribute name must be valid UTF-8 and not contain the characters >, /, = or whitespace, got ">foo".');

        $attributes['>foo'] = 'bar';
    }

    public function testThrowsIfPropertyDoesNotExistWhenUsingArrayAccess(): void
    {
        $attributes = new HtmlAttributes();

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('The attribute property "foo" does not exist.');

        /** @phpstan-ignore expr.resultUnused */
        $attributes['foo'];
    }

    public function testCanBeJsonSerialized(): void
    {
        $attributes = new HtmlAttributes(['foo' => 'bar', 'baz' => 42]);

        $this->assertSame('{"foo":"bar","baz":"42"}', json_encode($attributes, JSON_THROW_ON_ERROR));

        $attributes = new HtmlAttributes('0=foo 1=bar');

        $this->assertSame('{"0":"foo","1":"bar"}', json_encode($attributes, JSON_THROW_ON_ERROR));
    }

    public function testIteratorStringKeys(): void
    {
        $attributes = new HtmlAttributes('0=foo 1=bar');

        foreach ($attributes as $key => $value) {
            $this->assertIsString($key);
            $this->assertIsString($value);
        }
    }

    private function toStringable(string $string): \Stringable
    {
        return new class($string) implements \Stringable {
            public function __construct(private readonly string $string)
            {
            }

            public function __toString(): string
            {
                return $this->string;
            }
        };
    }
}
