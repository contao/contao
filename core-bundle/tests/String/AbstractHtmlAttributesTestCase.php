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
use PHPUnit\Framework\Attributes\DataProvider;

abstract class AbstractHtmlAttributesTestCase extends TestCase
{
    #[DataProvider('provideAttributeStrings')]
    public function testParsesAttributeStrings(string $attributeString, array $expectedAttributes): void
    {
        $attributes = $this->createAttributes($attributeString);

        $this->assertSameAttributes($expectedAttributes, $attributes);

        $attributes = $this->createAttributes($attributes->toString());

        $this->assertSameAttributes($expectedAttributes, $attributes);
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

    #[DataProvider('provideAttributeValues')]
    public function testSerializesAttributeValues(mixed $value, string|null $expected): void
    {
        $attributes = $this->createAttributes();
        $attributes->set('foo', $value);

        $this->assertSame($expected, $attributes['foo'] ?? null);

        $attributes = $this->createAttributes();
        $attributes->mergeWith(['foo' => $value]);

        $this->assertSame($expected, $attributes['foo'] ?? null);
    }

    public static function provideAttributeValues(): iterable
    {
        yield ['string', 'string'];

        yield ['', ''];

        yield [
            new class implements \Stringable {
                public function __toString(): string
                {
                    return 'stringable';
                }
            },
            'stringable',
        ];

        yield [null, ''];

        yield [true, ''];

        yield [false, null];

        yield [123, '123'];

        yield [0, '0'];

        yield [123.4, '123.4'];

        yield [0.0000000001, '0.0000000001'];

        yield [-0.0, '0'];

        yield [INF, null];

        yield [NAN, null];
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

        $this->assertSameAttributes(
            $expectedProperties,
            $this->createAttributes($properties),
        );

        $this->assertSameAttributes(
            $expectedProperties,
            $this->createAttributes(new \ArrayIterator($properties)),
        );
    }

    public function testCreatesAttributesFromAttributesClass(): void
    {
        $attributes = $this->createAttributes([
            'foO_bAr' => 'bar',
            'bar-bar' => 42,
            'BAZ123' => true,
            'other' => null,
        ]);

        $this->assertSameAttributes(iterator_to_array($attributes), $this->createAttributes($attributes));
    }

    public function testMergesAttributesFromAttributesClass(): void
    {
        $attributesA = $this->createAttributes([
            'foO_bAr' => 'bar',
            'class' => 'class1',
            'style' => 'COLOR: RED',
        ]);

        $attributesB = $this->createAttributes([
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
            'foo-foo' => false,
        ];

        $attributes = $attributesA->mergeWith($attributesB)->mergeWith($attributesC)->mergeWith($attributesD);

        $this->assertSameAttributes($expectedProperties, $attributes);
    }

    public function testMergingFalseValuesUnsetsAttributes(): void
    {
        $attributes = $this->createAttributes(['title' => 'old', 'class' => 'foo', 'style' => 'color: red;', 'id' => 'keep']);
        $attributes->mergeWith(['title' => false, 'class' => false, 'style' => false]);

        $this->assertSameAttributes(['id' => 'keep', 'title' => false, 'class' => false, 'style' => false], $attributes);
        $this->assertSame(' id="keep"', (string) $attributes);
    }

    public function testSkipsInvalidAttributeNamesWhenParsingString(): void
    {
        $attributes = $this->createAttributes("foo=bar f\xC2o\x00o=b&#xC2;ar baz=42");

        $this->assertSameAttributes(['foo' => 'bar', "f\u{FFFD}o\u{FFFD}o" => "b\u{C2}ar", 'baz' => '42'], $attributes);
    }

    #[DataProvider('provideInvalidAttributeNames')]
    public function testRejectsInvalidAttributeNamesWhenConstructingFromArray(string $name): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/An HTML attribute name must be valid UTF-8 and not contain the characters >, \/, = or whitespace, got ".*"\./');

        $this->createAttributes([$name => 'bar']);
    }

    #[DataProvider('provideInvalidAttributeNames')]
    public function testRejectsInvalidAttributeNamesWhenSetting(string $name): void
    {
        $attributes = $this->createAttributes();

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

    public function testAddsAttributesUsingTheirSyntax(): void
    {
        $attributes = $this->createAttributes();

        $this->assertSame($attributes, $attributes->add('DATA-CONTROLLER', 'contao--collection'));

        $attributes->add('data-controller', 'contao--fragment');
        $attributes->add('CLASS', 'foo bar')->add('class', 'bar baz');
        $attributes->add('STYLE', 'color: red; display: block')->add('style', 'color: blue');
        $attributes->add('ACCEPT', 'image/png')->add('accept', 'image/jpeg');
        $attributes->add('EXPORTPARTS', 'part1')->add('exportparts', 'part2:exposed');
        $attributes->add('IMAGESIZES', 'auto')->add('imagesizes', '100vw');
        $attributes->add('IMAGESRCSET', 'image.png 100w')->add('imagesrcset', 'image.jpeg 1x');
        $attributes->add('MEDIA', 'screen and (min-width: 30em)')->add('media', 'print');
        $attributes->add('SIZES', 'auto')->add('sizes', '100vw');
        $attributes->add('SRCSET', 'image.png 100w')->add('srcset', 'image.jpeg 1x');
        $attributes->add('ALLOW', "geolocation 'self'")->add('allow', "fullscreen 'none'");
        $attributes->add('CSP', "default-src 'self'")->add('csp', "img-src 'self' example.com");
        $attributes->add('COORDS', '0,0')->add('coords', '100,100');
        $attributes->add('data-foo', 'bar')->add('data-foo', 'baz', true, ' << >> ');
        $attributes->add('data-bar', 'foo')->add('data-bar', 'baz', true, '');

        $this->assertSame(
            [
                'data-controller' => 'contao--collection contao--fragment',
                'class' => 'foo bar baz',
                'style' => 'color: blue; display: block;',
                'accept' => 'image/png, image/jpeg',
                'exportparts' => 'part1, part2:exposed',
                'imagesizes' => 'auto, 100vw',
                'imagesrcset' => 'image.png 100w, image.jpeg 1x',
                'media' => 'screen and (min-width: 30em), print',
                'sizes' => 'auto, 100vw',
                'srcset' => 'image.png 100w, image.jpeg 1x',
                'allow' => 'geolocation \'self\'; fullscreen \'none\'',
                'csp' => 'default-src \'self\'; img-src \'self\' example.com',
                'coords' => '0,0,100,100',
                'data-foo' => 'bar << >> baz',
                'data-bar' => 'foobaz',
            ],
            iterator_to_array($attributes),
        );
    }

    #[DataProvider('provideListAttributeValues')]
    public function testAddsAttributeValues(mixed $value, string|null $expected): void
    {
        $attributes = $this->createAttributes();
        $attributes->add('foo', $value);

        $this->assertSame($expected, $attributes['foo'] ?? null);
    }

    public static function provideListAttributeValues(): iterable
    {
        yield 'array' => [['foo', 'bar'], 'foo bar'];
        yield 'empty array' => [[], null];

        foreach (self::provideAttributeValues() as [$value, $expected]) {
            if (!\is_bool($value)) {
                yield [$value, '' === $expected ? null : $expected];
            }
        }
    }

    public function testIgnoresEmptyAndNonFiniteListValues(): void
    {
        $attributes = $this->createAttributes(['existing' => 'value']);

        foreach (['add', 'remove'] as $method) {
            foreach (['', [], null, INF, -INF, NAN, $this->toStringable('')] as $value) {
                $this->assertSame($attributes, $attributes->$method('existing', $value));

                $attributes->$method('missing', $value);
            }

            $attributes->$method('existing');
            $attributes->$method('missing');
        }

        $this->assertSameAttributes(['existing' => 'value', 'missing' => false], $attributes);
    }

    public function testAppendsAfterZero(): void
    {
        $attributes = $this->createAttributes(['data-value' => '0']);
        $attributes->add('data-value', 'next');

        $this->assertSame('0 next', $attributes['data-value']);
    }

    public function testAddsArraysUsingAttributeSyntax(): void
    {
        $attributes = $this->createAttributes(['data-controller' => 'first', 'class' => 'foo', 'style' => 'color: red;', 'media' => 'screen']);
        $attributes->add('data-controller', ['second', 'third']);
        $attributes->add('class', ['foo', 'bar baz']);
        $attributes->add('style', ['color' => 'blue', 'display' => 'block']);
        $attributes->add('style', ['margin: 0', 'padding: 0']);
        $attributes->add('media', ['print', 'screen and (min-width: 30em)']);

        $this->assertSame(
            [
                'data-controller' => 'first second third',
                'class' => 'foo bar baz',
                'style' => 'color: blue; display: block; margin: 0; padding: 0;',
                'media' => 'screen, print, screen and (min-width: 30em)',
            ],
            iterator_to_array($attributes),
        );
    }

    #[DataProvider('provideListAttributeRemovals')]
    public function testRemovesAttributeValues(string $name, string|null $initial, mixed $value, string|null $expected, string|null $separator = null): void
    {
        $attributes = $this->createAttributes(null === $initial ? [] : [strtolower($name) => $initial]);

        $this->assertSame($attributes, $attributes->remove($name, $value, separator: $separator));
        $this->assertSame($expected, $attributes[strtolower($name)] ?? null);
    }

    public static function provideListAttributeRemovals(): iterable
    {
        yield 'array' => ['data-controller', 'foo bar baz', ['foo', 'baz'], 'bar'];
        yield 'array removes all items' => ['data-controller', 'foo bar', ['foo', 'bar'], null];
        yield 'array class' => ['class', 'foo bar baz', ['foo', 'baz'], 'bar'];
        yield 'array style properties' => ['style', 'color:red;display:block;margin:0', ['color', 'margin'], 'display: block;'];
        yield 'array style declarations' => ['style', 'color:red;display:block', ['color' => 'blue'], 'display: block;'];
        yield 'array media' => ['media', 'screen and (min-width: 30em), print, speech', ['screen and (min-width: 30em)', 'speech'], 'print'];
        yield 'first item' => ['DATA-CONTROLLER', 'foo bar baz', 'foo', 'bar baz'];
        yield 'middle item' => ['data-controller', 'foo bar baz', 'bar', 'foo baz'];
        yield 'last item' => ['data-controller', 'foo bar baz', 'baz', 'foo bar'];
        yield 'multiple items' => ['data-controller', 'foo bar baz', 'foo baz', 'bar'];
        yield 'all items' => ['data-controller', 'foo bar', 'foo bar', null];
        yield 'absent item' => ['data-controller', 'foo bar', 'baz', 'foo bar'];
        yield 'absent attribute' => ['data-controller', null, 'foo', null];
        yield 'whole items only' => ['data-controller', 'foobar foo barfoo', 'foo', 'foobar barfoo'];
        yield 'duplicate items' => ['data-controller', 'foo foo bar foo', 'foo', 'bar'];
        yield 'regular expression characters' => ['data-value', 'a/b (foo) a.b a+b', 'a/b (foo) a.b', 'a+b'];
        yield 'integer' => ['data-value', '0 1 2', 0, '1 2'];
        yield 'float' => ['data-value', '0.0000000001 1', 0.0000000001, '1'];
        yield 'stringable' => [
            'data-value',
            'foo bar',
            new class implements \Stringable {
                public function __toString(): string
                {
                    return 'foo';
                }
            },
            'bar',
        ];
        yield 'class' => ['CLASS', 'foo bar baz', 'foo baz', 'bar'];
        yield 'style' => ['STYLE', 'color: red; display: block;', 'color', 'display: block;'];
        yield 'media' => ['MEDIA', 'screen and (min-width: 30em), print, speech', 'screen and (min-width: 30em), speech', 'print'];
        yield 'media comma whitespace' => ['media', 'screen,print,  speech', 'print', 'screen,  speech'];
        yield 'whitespace-sensitive separator' => ['data-foo', '|a|b| b |b | b|c| c |c | c|d|', 'c| b |b | b', '|a|b| c |c | c|d|', '|'];
        yield 'whitespace-insensitive separator' => ['data-foo', '|a|b| b |b | b|c| c |c | c|d|', 'c| b |b | b', '|a|d|', ' | '];
        yield 'multi-character separator' => ['data-foo', 'a-b--c--a---b--a-c-', 'a--b--c', 'a-b---a-c-', '--'];
    }

    public function testRemovesAttributesConditionally(): void
    {
        $attributes = $this->createAttributes(['data-controller' => 'first second']);
        $attributes->remove('data-controller', 'first', $this->toStringable(''));
        $attributes->remove('invalid name', 'first', false);
        $attributes->remove('data-controller', ['first', 'second'], false);

        $this->assertSame('first second', $attributes['data-controller']);

        $attributes->remove('data-controller', 'first', $this->toStringable('1'));

        $this->assertSame('second', $attributes['data-controller']);
    }

    #[DataProvider('provideInvalidAttributeNames')]
    public function testRejectsInvalidAttributeNamesWhenRemoving(string $name): void
    {
        $attributes = $this->createAttributes();

        $this->expectException(\InvalidArgumentException::class);

        $attributes->remove($name, 'value');
    }

    public function testDoesNotOutputEmptyAttributes(): void
    {
        $attributes = $this->createAttributes();
        $attributes->add('data-controller', '');
        $attributes->add('srcset', []);

        $this->assertSame('', $attributes->toString());

        $attributes->add('data-controller', 'foo');
        $attributes->add('srcset', ['image.jpeg 1x']);
        $attributes->remove('data-controller', ['foo']);
        $attributes->remove('srcset', 'image.jpeg 1x');

        $this->assertSame('', $attributes->toString());

        $this->assertSameAttributes(['data-controller' => false, 'srcset' => false], $attributes);
    }

    public function testAddsAttributesConditionally(): void
    {
        $attributes = $this->createAttributes(['data-controller' => 'first']);
        $attributes->add('data-controller', 'skipped', $this->toStringable(''));
        $attributes->add('invalid name', 'skipped', false);
        $attributes->add('data-controller', ['skipped', 'also-skipped'], false);
        $attributes->add('data-controller', 'second', $this->toStringable('1'));

        $this->assertSame('first second', $attributes['data-controller']);
    }

    public function testAppendsAttributeTextWithoutNormalizingIt(): void
    {
        $attributes = $this->createAttributes(['data-value' => 'first  second']);
        $attributes->add('data-value', "second\tthird")->add('data-value', 0);

        $this->assertSame("first  second second\tthird 0", $attributes['data-value']);

        $attributes->add('data-value', '');
        $attributes->add('data-value', null);
        $attributes->add('data-value');

        $this->assertSame("first  second second\tthird 0", $attributes['data-value']);
    }

    #[DataProvider('provideInvalidAttributeNames')]
    public function testRejectsInvalidAttributeNamesWhenAdding(string $name): void
    {
        $attributes = $this->createAttributes();

        $this->expectException(\InvalidArgumentException::class);

        $attributes->add($name, 'value');
    }

    public function testSetAndUnsetProperties(): void
    {
        $attributes = $this->createAttributes();

        $this->assertSameAttributes([], $attributes);

        // Set and overwrite properties
        $attributes->set('foo', 'bar');
        $attributes->set('bar', 42);
        $attributes->set('foo', '{{baz}}');

        $this->assertSameAttributes(['foo' => '{{baz}}', 'bar' => '42'], $attributes);

        // Unset existing and non-existing properties
        $attributes->unset('foo');
        $attributes->unset('other');

        $this->assertSameAttributes(['bar' => '42', 'foo' => false, 'other' => false], $attributes);

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

        $this->assertSameAttributes(['bar' => '42', 'e' => ' ', 'f' => 'abc', 'g' => ' ', 'foo' => false, 'other' => false], $attributes);

        // Unset properties by setting them to false
        $attributes->set('bar', false);
        $attributes->setIfExists('f', false); // should not alter the list
        $attributes->mergeWith(['g' => false]);

        $this->assertSameAttributes(['e' => ' ', 'f' => 'abc', 'foo' => false, 'bar' => false, 'other' => false, 'g' => false], $attributes);

        // Uppercase names
        $attributes->set('E', 'UPPER');
        $attributes->unset('F');

        $this->assertSameAttributes(['e' => 'UPPER', 'foo' => false, 'bar' => false, 'other' => false, 'f' => false, 'g' => false], $attributes);
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
            $attributes = $this->createAttributes();
            $attributes->set('data-feature', condition: $value);

            $this->assertSameAttributes(['data-feature' => ''], $attributes);

            $attributes->unset('data-feature', condition: $value);
            $this->assertSameAttributes(['data-feature' => false], $attributes);
        }

        // Test falsy values do not fulfil the condition
        foreach ($falsyValues as $value) {
            $attributes = $this->createAttributes(['data-feature' => '']);
            $attributes->set('data-foo', condition: $value);

            $this->assertSameAttributes(['data-feature' => ''], $attributes);

            $attributes->unset('data-feature', condition: $value);
            $this->assertSameAttributes(['data-feature' => ''], $attributes);
        }
    }

    public function testAddAndRemoveClasses(): void
    {
        // Whitespaces should get normalized by default
        $attributes = $this->createAttributes(['class' => " \ffoo  bar\tother1\nother2\r  "]);

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
        $attributes = $this->createAttributes();
        $attributes->addClass('a', null);
        $attributes->addClass('b', false);
        $attributes->addClass('c', 0);
        $attributes->addClass('d', '');

        $this->assertSameAttributes([], $attributes);

        $attributes->addClass('a', condition: true);
        $attributes->addClass('b', condition: 1);
        $attributes->addClass('c', condition: 'true');
        $attributes->addClass('d', condition: '1');

        $this->assertSameAttributes(['class' => 'a b c d'], $attributes);

        $attributes->removeClass('a', null);
        $attributes->removeClass('b', false);
        $attributes->removeClass('c', 0);
        $attributes->removeClass('d', '');

        $this->assertSameAttributes(['class' => 'a b c d'], $attributes);

        $attributes->removeClass('a', condition: true);
        $attributes->removeClass('b', condition: 1);
        $attributes->removeClass('c', condition: 'true');
        $attributes->removeClass('d', condition: '1');

        $this->assertSameAttributes(['class' => false], $attributes);
    }

    public function testDoesNotOutputEmptyClassAttribute(): void
    {
        $attributes = $this->createAttributes();
        $attributes->addClass('');

        $this->assertSame('', $attributes->toString());

        $attributes->addClass('foo');
        $attributes->removeClass('foo');

        $this->assertSame('', $attributes->toString());
    }

    public function testAddAndRemoveStyles(): void
    {
        // Whitespaces should get normalized by default
        $attributes = $this->createAttributes(['style' => " \ffoo:  bar;\tother1\n:other2\r  ;"]);

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
        $attributes = $this->createAttributes(['style' => 'color:red;--color:red']);

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
        $attributes = $this->createAttributes();
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
        $attributes = $this->createAttributes();
        $attributes->addStyle('foo: a', null);
        $attributes->addStyle('foo: b', false);
        $attributes->addStyle('foo: c', 0);
        $attributes->addStyle('foo: d', '');

        $this->assertSameAttributes([], $attributes);

        $attributes->addStyle('a: foo', condition: true);
        $attributes->addStyle('b: foo', condition: 1);
        $attributes->addStyle('c: foo', condition: 'true');
        $attributes->addStyle('d: foo', condition: '1');

        $this->assertSameAttributes(['style' => 'a: foo; b: foo; c: foo; d: foo;'], $attributes);

        $attributes->removeStyle('a: foo', null);
        $attributes->removeStyle('b: foo', false);
        $attributes->removeStyle('c', 0);
        $attributes->removeStyle('d: foo', '');

        $this->assertSameAttributes(['style' => 'a: foo; b: foo; c: foo; d: foo;'], $attributes);

        $attributes->removeStyle('a: foo', condition: true);
        $attributes->removeStyle('b', condition: 1);
        $attributes->removeStyle('c: foo', condition: 'true');
        $attributes->removeStyle(['d'], condition: '1');

        $this->assertSameAttributes(['style' => false], $attributes);

        $attributes->addStyle('foo1:');
        $attributes->addStyle('foo2:;');
        $attributes->addStyle('foo3: ;');
        $attributes->addStyle(['foo4' => null]);
        $attributes->addStyle(['foo5' => false]);
        $attributes->addStyle(['foo6' => '']);
        $attributes->addStyle(['foo7' => ';']);

        $this->assertSameAttributes(['style' => false], $attributes);

        $attributes->addStyle('a: 0;');
        $attributes->addStyle('b: "";');
        $attributes->addStyle('c: false;');
        $attributes->addStyle(['d' => true]);
        $attributes->addStyle(['e' => 0]);
        $attributes->addStyle(['f' => '""']);
        $attributes->addStyle(['g' => '1']);
        $attributes->addStyle(['g' => null]);

        $this->assertSameAttributes(['style' => 'a: 0; b: ""; c: false; d: 1; e: 0; f: "";'], $attributes);

        $attributes->set('style', '--a:;');
        $attributes->addStyle('--b:;');
        $attributes->addStyle(['--c:']);
        $attributes->addStyle(['--d' => ' ']);
        $attributes->addStyle(['--e' => '']);
        $attributes->addStyle(['--f' => null]);
        $attributes->addStyle(['--g' => false]);

        $this->assertSameAttributes(
            ['style' => '--a: ; --b: ; --c: ; --d: ;'],
            $attributes,
            'Custom properties with empty values should not get stripped',
        );

        $attributes->addStyle(['--a' => '']);
        $attributes->addStyle(['--b' => null]);
        $attributes->addStyle(['--c' => false]);
        $attributes->addStyle(['--d' => '']);

        $this->assertSameAttributes(['style' => false], $attributes);
    }

    public function testDoesNotOutputEmptyStyleAttribute(): void
    {
        $attributes = $this->createAttributes();
        $attributes->addStyle('');

        $this->assertSame('', $attributes->toString());

        $attributes->addStyle('foo: bar');
        $attributes->addStyle('invalid style');
        $attributes->removeStyle('foo');

        $this->assertSame('', $attributes->toString());
    }

    public function testAllowsChaining(): void
    {
        $attributes = $this->createAttributes()
            ->addClass('block headline foo')
            ->removeClass('foo')
            ->set('style', 'color: red;')
            ->setIfExists('data-foo', null)
            ->addStyle('color: blue;')
        ;

        $this->assertSame(' class="block headline" style="color: blue;"', (string) $attributes);
    }

    public function testEscapesAttributesWhenRenderingAsString(): void
    {
        $attributes = $this->createAttributes([
            'A' => 'A B C',
            'b' => '{{b}}',
            'c' => 'foo&bar',
            'd' => 'foo&amp;bar',
            'e' => '&ZeroWidthSpace;',
            'property-without-value' => null,
            'data-json' => json_encode('foo &quot; bar'),
        ]);

        $expectedString = 'a="A B C" b="&#123;&#123;b&#125;&#125;" c="foo&amp;bar" d="foo&amp;amp;bar" e="&amp;ZeroWidthSpace;" property-without-value data-json="&quot;foo &amp;quot; bar&quot;"';

        $this->assertSame(" $expectedString", (string) $attributes);
        $this->assertSame(" $expectedString", $attributes->toString());
        $this->assertSame($expectedString, $attributes->toString(false));
    }

    public function testStripsLeadingWhitespaceIfEmpty(): void
    {
        $this->assertSame('', (string) $this->createAttributes());
        $this->assertSame('', $this->createAttributes()->toString());
        $this->assertSame('', $this->createAttributes()->toString(false));
    }

    #[DataProvider('provideBooleanAttributes')]
    public function testCorrectlySerializesBooleanAttributes(array $attrArray, string $attrString): void
    {
        $this->assertSame($attrString, $this->createAttributes($attrArray)->toString(false));
        $this->assertSameAttributes($attrArray, $this->createAttributes($attrString));
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
        $attributes = $this->createAttributes(['foo' => "\xC2"]);

        $this->assertSame("\xC2", $attributes['foo']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The value of property "foo" is not a valid UTF-8 string.');

        $attributes->toString();
    }

    public function testArrayAccess(): void
    {
        $attributes = $this->createAttributes(['foo' => '{{bar}}', 'baz' => 42]);

        $this->assertSame('{{bar}}', $attributes['foo']);
        $this->assertSame('42', $attributes['baz']);
        $this->assertTrue(isset($attributes['foo']));
        $this->assertFalse(isset($attributes['foobar']));
        $this->assertFalse(isset($attributes['FOO']));

        $attributes['other'] = true;
        $attributes['baz'] = false;
        unset($attributes['foo']);

        $this->assertSameAttributes(['other' => '', 'baz' => false, 'foo' => false], $attributes);
    }

    public function testThrowsWhenSettingInvalidNameUsingArrayAccess(): void
    {
        $attributes = $this->createAttributes();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('An HTML attribute name must be valid UTF-8 and not contain the characters >, /, = or whitespace, got ">foo".');

        $attributes['>foo'] = 'bar';
    }

    public function testThrowsIfPropertyDoesNotExistWhenUsingArrayAccess(): void
    {
        $attributes = $this->createAttributes();

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('The attribute property "foo" does not exist.');

        /** @phpstan-ignore expr.resultUnused */
        $attributes['foo'];
    }

    public function testCanBeJsonSerialized(): void
    {
        $attributes = $this->createAttributes(['foo' => 'bar', 'baz' => 42]);

        $this->assertSame('{"foo":"bar","baz":"42"}', json_encode($attributes, JSON_THROW_ON_ERROR));

        $attributes = $this->createAttributes('0=foo 1=bar');

        $this->assertSame('{"0":"foo","1":"bar"}', json_encode($attributes, JSON_THROW_ON_ERROR));
    }

    public function testIteratorStringKeys(): void
    {
        $attributes = $this->createAttributes('0=foo');

        foreach ($attributes as $key => $value) {
            $this->assertSame('0', $key);
        }
    }

    /**
     * @param iterable<string, \Stringable|bool|float|int|string|null>|string|HtmlAttributes|null $attributes
     */
    abstract protected function createAttributes(HtmlAttributes|iterable|string|null $attributes = null): HtmlAttributes;

    /**
     * @param array<array-key, string|false> $expected
     */
    abstract protected function assertSameAttributes(array $expected, HtmlAttributes $attributes, string $message = ''): void;

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
