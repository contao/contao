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

    public function provideAttributeStrings(): \Generator
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

        yield 'special html parsing rules' => [
            "/X===.._-/\n/y/",
            ['x' => '==.._-/', 'y' => ''],
        ];

        yield 'skip closing and keep opening tags as per html parsing rules' => [
            '>foo=<bar>baz>/</<ignore=<this-one',
            ['foo' => '<bar', 'baz' => ''],
        ];

        yield 'skip unclosed attributes completely' => [
            'foo="bar" baz="42 bar=\'123\'> <div class=H4x0r',
            ['foo' => 'bar'],
        ];

        yield 'decode values' => [
            'foo=&quot; bar="b&auml;z"',
            ['foo' => '"', 'bar' => 'bäz'],
        ];

        yield 'no attributes' => [
            '',
            [],
        ];

        yield 'just spaces' => [
            '  ',
            [],
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
            iterator_to_array(new HtmlAttributes($properties))
        );

        $this->assertSame(
            $expectedProperties,
            iterator_to_array(new HtmlAttributes(new \ArrayIterator($properties)))
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
        ]);

        $attributesB = new HtmlAttributes([
            'bar-bar' => 42,
            'foo-foo' => 'foo',
        ]);

        $attributesC = 'BAZ123 = "" class="class2"';

        $attributesD = [
            'other' => null,
            'foo-foo' => false,
            'class' => 'class1 class3',
        ];

        $expectedProperties = [
            'foo_bar' => 'bar',
            'class' => 'class1 class2 class3',
            'bar-bar' => '42',
            'baz123' => '',
            'other' => '',
        ];

        $attributes = $attributesA->mergeWith($attributesB)->mergeWith($attributesC)->mergeWith($attributesD);

        $this->assertSame($expectedProperties, iterator_to_array($attributes));
    }

    /**
     * @dataProvider provideInvalidAttributeNames
     */
    public function testSkipsInvalidAttributeNamesWhenParsingString(string $name): void
    {
        $attributes = new HtmlAttributes(sprintf('foo="bar" %s="bar" baz=42', $name));

        $this->assertSame(['foo' => 'bar', 'baz' => '42'], iterator_to_array($attributes));
    }

    /**
     * @dataProvider provideInvalidAttributeNames
     */
    public function testRejectsInvalidAttributeNamesWhenConstructingFromArray(string $name): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/A HTML attribute name must only consist of the characters \[a-z0-9_-\], must start with a letter, must not end with a underscore\/hyphen and must not contain two underscores\/hyphens in a row, got ".*"\./');

        new HtmlAttributes([$name => 'bar']);
    }

    /**
     * @dataProvider provideInvalidAttributeNames
     */
    public function testRejectsInvalidAttributeNamesWhenSetting(string $name): void
    {
        $attributes = new HtmlAttributes();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/A HTML attribute name must only consist of the characters \[a-z0-9_-\], must start with a letter, must not end with a underscore\/hyphen and must not contain two underscores\/hyphens in a row, got ".*"\./');

        $attributes->set($name, 'bar');
    }

    public function provideInvalidAttributeNames(): \Generator
    {
        yield 'invalid character' => ['föö'];
        yield 'invalid non-utf8 character' => ["f\xC2"];
        yield 'does not start with a-z' => ['2foo'];
        yield 'ends with a hyphen' => ['foo-'];
        yield 'contains two hyphens in a row' => ['foo--bar'];
        yield 'ends with an underscore' => ['foo_'];
        yield 'contains two underscores in a row' => ['foo__bar'];
        yield 'opening tag only' => ['<'];
        yield 'contains opening tag as first char' => ['<foo'];
        yield 'contains opening tag as second char' => ['f<oo'];
        yield 'contains opening tag as last char' => ['foo<'];
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

        // Set values that should be used
        $attributes->setIfExists('e', ' ');
        $attributes->setIfExists('f', 'abc');

        $this->assertSame(['bar' => '42', 'e' => ' ', 'f' => 'abc'], iterator_to_array($attributes));

        // Unset properties by setting them to false
        $attributes->set('bar', false);
        $attributes->setIfExists('f', false); // should not alter the list

        $this->assertSame(['e' => ' ', 'f' => 'abc'], iterator_to_array($attributes));
    }

    public function testSetAndUnsetConditionalProperties(): void
    {
        $attributes = new HtmlAttributes();

        $attributes->set('data-feature1', condition: null);
        $attributes->set('data-feature2', condition: false);
        $attributes->set('data-feature3', condition: 0);

        $attributes->set(
            'data-feature5',
            condition: new class() implements \Stringable {
                public function __toString(): string
                {
                    return '';
                }
            },
        );

        $this->assertSame([], iterator_to_array($attributes));

        $attributes->set('data-feature1', condition: true);
        $attributes->set('data-feature2', condition: 1);
        $attributes->set('data-feature3', condition: 'true');
        $attributes->set('data-feature4', condition: '1');

        $this->assertSame(['data-feature1' => '', 'data-feature2' => '', 'data-feature3' => '', 'data-feature4' => ''], iterator_to_array($attributes));

        $attributes->unset('data-feature1', null);
        $attributes->unset('data-feature2', false);
        $attributes->unset('data-feature3', 0);
        $attributes->unset('data-feature4', '');

        $this->assertSame(['data-feature1' => '', 'data-feature2' => '', 'data-feature3' => '', 'data-feature4' => ''], iterator_to_array($attributes));

        $attributes->unset('data-feature1', true);
        $attributes->unset('data-feature2', 1);
        $attributes->unset('data-feature3', 'true');
        $attributes->unset('data-feature4', '1');

        $this->assertSame([], iterator_to_array($attributes));
    }

    public function testAddAndRemoveClasses(): void
    {
        // Whitespaces should get normalized by default
        $attributes = new HtmlAttributes(['class' => " \ffoo  bar\tother1\nother2\r  "]);

        $this->assertSame('foo bar other1 other2', $attributes['class']);

        // And remove classes
        $attributes->addClass('baz', 'foo foobar');
        $attributes->removeClass(' other1', 'thing other2');

        $this->assertSame('foo bar baz foobar', $attributes['class']);
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

    public function testAllowsChaining(): void
    {
        $attributes = (new HtmlAttributes())
            ->addClass('block', 'headline', 'foo')
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
            'property-without-value' => null,
        ]);

        $expectedString = 'a="A B C" b="&#123;&#123;b&#125;&#125;" c="foo&amp;bar" d="foo&amp;amp;bar" property-without-value';

        $this->assertSame(" $expectedString", $attributes->__toString());
        $this->assertSame(" $expectedString", $attributes->toString());
        $this->assertSame($expectedString, $attributes->toString(false));
    }

    public function testStripsLeadingWhitespaceIfEmpty(): void
    {
        $this->assertSame('', (new HtmlAttributes())->__toString());
        $this->assertSame('', (new HtmlAttributes())->toString());
        $this->assertSame('', (new HtmlAttributes())->toString(false));
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
        $this->expectExceptionMessage('A HTML attribute name must only consist of the characters [a-z0-9_-], must start with a letter, must not end with a underscore/hyphen and must not contain two underscores/hyphens in a row, got "foo--2000".');

        $attributes['foo--2000'] = 'bar';
    }

    public function testThrowsIfPropertyDoesNotExistWhenUsingArrayAccess(): void
    {
        $attributes = new HtmlAttributes();

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('The attribute property "foo" does not exist.');

        /** @phpstan-ignore-next-line */
        $attributes['foo'];
    }

    public function testCanBeJsonSerialized(): void
    {
        $attributes = new HtmlAttributes(['foo' => 'bar', 'baz' => 42]);

        $this->assertSame('{"foo":"bar","baz":"42"}', json_encode($attributes, JSON_THROW_ON_ERROR));
    }
}
