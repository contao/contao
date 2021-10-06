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

use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\InsertTags;
use Contao\PageModel;
use Contao\System;

/**
 * @group legacy
 */
class InsertTagsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TL_HOOKS']['replaceInsertTags'][] = [self::class, 'replaceInsertTagsHook'];

        $container = $this->getContainerWithContaoConfiguration($this->getTempDir());

        $container->set('contao.security.token_checker', $this->createMock(TokenChecker::class));

        System::setContainer($container);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_HOOKS']);

        parent::tearDown();
    }

    public function replaceInsertTagsHook(string $tag): string
    {
        return explode('::', $tag, 2)[1] ?? '';
    }

    /**
     * @dataProvider encodeHtmlAttributesProvider
     */
    public function testEncodeHtmlAttributes(string $source, string $expected): void
    {
        $reflectionClass = new \ReflectionClass(InsertTags::class);

        /** @var InsertTags $insertTags */
        $insertTags = $reflectionClass->newInstanceWithoutConstructor();

        $this->assertSame($expected, $insertTags->replace($source, false));
    }

    public function encodeHtmlAttributesProvider(): \Generator
    {
        yield 'Simple tag' => [
            'bar{{plain::foo}}baz',
            'barfoobaz',
        ];

        yield 'Quote in plain text' => [
            'foo{{plain::"}}bar',
            'foo"bar',
        ];

        yield 'Quote before tag' => [
            '{{plain::"}}<span>',
            '"<span>',
        ];

        yield 'Quote after tag' => [
            '<span>{{plain::"}}',
            '<span>"',
        ];

        yield 'Quote in attribute' => [
            '<span title=\'{{plain::"}}\'>',
            '<span title=\'&quot;\'>',
        ];

        yield 'Quote in unquoted attribute' => [
            '<span title={{plain::"}}>',
            '<span title=&quot;>',
        ];

        yield 'Quote in single quoted attribute' => [
            '<span title="{{plain::\'}}">',
            '<span title="&#039;">',
        ];

        yield 'Quote outside attribute' => [
            '<span title="" {{plain::"}}>',
            '<span title="" &quot;>',
        ];

        yield 'Trick tag detection' => [
            '<span title=">" class=\'{{plain::"}}\'>',
            '<span title=">" class=\'&quot;\'>',
        ];

        yield 'Trick tag detection with slash' => [
            '<span/title=">"/class=\'{{plain::"}}\'>',
            '<span/title=">"/class=\'&quot;\'>',
        ];

        yield 'Trick tag detection with two tags' => [
            '<span /="notanattribute title="> {{plain::\'}} " > {{plain::\'}}',
            '<span /="notanattribute title="> &#039; " > \'',
        ];

        yield 'Trick tag detection with not a tag' => [
            '<önotag{{plain::"}} <-notag {{plain::"}}',
            '<önotag" <-notag "',
        ];

        yield 'Trick tag detection with closing tag' => [
            '</span =="><span title=">{{plain::<>}}<span title=">{{plain::<>}}">',
            '</span =="><span title="><><span title=">&lt;&gt;">',
        ];

        yield 'Trick tag detection with not a tag or comment' => [
            '<-span <x =="><span title=">{{plain::<>}}<span title=">{{plain::<>}}">',
            '<-span <x =="><span title="><><span title=">&lt;&gt;">',
        ];

        yield 'Trick tag detection with bogus / comment' => [
            '</-span <x =="><span title=">{{plain::<>}}<span title=">{{plain::<>}}">',
            '</-span <x =="><span title=">&lt;&gt;<span title="><>">',
        ];

        yield 'Trick tag detection with bogus ? comment' => [
            '<?span <x =="><span title=">{{plain::<>}}<span title=">{{plain::<>}}">',
            '<?span <x =="><span title=">&lt;&gt;<span title="><>">',
        ];

        yield 'Trick tag detection with bogus ! comment' => [
            '<!span <x =="><span title=">{{plain::<>}}<span title=">{{plain::<>}}">',
            '<!span <x =="><span title=">&lt;&gt;<span title="><>">',
        ];

        yield 'Trick tag detection with bogus !- comment' => [
            '<!-span <x =="><span title=">{{plain::<>}}<span title=">{{plain::<>}}">',
            '<!-span <x =="><span title=">&lt;&gt;<span title="><>">',
        ];

        yield 'Trick tag detection with comment' => [
            '<!-- <span title="-->{{plain::<>}}<span title=">{{plain::<>}}">',
            '<!-- <span title="--><><span title=">&lt;&gt;">',
        ];

        yield 'Trick tag detection with script' => [
            '<script><span title="</SCRIPT/>{{plain::<>}}<span title=">{{plain::<>}}">',
            '<script><span title="</SCRIPT/><><span title=">&lt;&gt;">',
        ];

        yield 'Trick tag detection with textarea' => [
            '<textArea foo=><span title="</TEXTaREA>{{plain::<>}}<span title=">{{plain::<>}}">',
            '<textArea foo=><span title="</TEXTaREA><><span title=">&lt;&gt;">',
        ];

        yield 'Not trick tag detection with pre' => [
            '<pre foo=><span title="</pre>{{plain::<>}}<span title=">{{plain::<>}}">',
            '<pre foo=><span title="</pre>&lt;&gt;<span title="><>">',
        ];

        yield 'Do not URL encode inside regular attributes' => [
            '<a title="sixteen{{plain:::}}nine">',
            '<a title="sixteen:nine">',
        ];

        yield 'URL encode inside source attributes' => [
            '<a href="sixteen{{plain:::}}nine">',
            '<a href="sixteen%3Anine">',
        ];

        yield 'URL encode inside source attributes with existing flag' => [
            '<img src="sixteen{{plain:::|strtoupper}}nine">',
            '<img src="sixteen%3Anine">',
        ];

        yield 'URL encode inside source attributes with existing specialchars flag' => [
            '<a href="sixteen{{plain:::|attr}}nine">',
            '<a href="sixteen%3Anine">',
        ];

        yield 'URL encode inside source attributes with existing flags' => [
            '<a href="sixteen{{plain:::|attr|strtoupper}}nine">',
            '<a href="sixteen%3Anine">',
        ];

        yield 'Allow safe protocols in URL attributes' => [
            '<a href="{{plain::https://example.com/}}"><a href="{{plain::http://example.com/}}"><a href="{{plain::ftp://example.com/}}"><a href="{{plain::mailto:test@example.com}}"><a href="{{plain::tel:+0123456789}}"><a href="{{plain::data:text/plain,test}}">',
            '<a href="https://example.com/"><a href="http://example.com/"><a href="ftp://example.com/"><a href="mailto:test@example.com"><a href="tel:+0123456789"><a href="data:text/plain,test">',
        ];

        yield 'Trick attributes detection with slash' => [
            '<a/href="sixteen{{plain:::}}nine">',
            '<a/href="sixteen%3Anine">',
        ];

        yield 'Trick attributes detection with non-attribute' => [
            '<ahref=" href="sixteen{{plain:::}}nine">',
            '<ahref=" href="sixteen%3Anine">',
        ];

        yield 'Trick attributes detection with dot' => [
            '<a.href=" href="sixteen{{plain:::}}nine">',
            '<a.href=" href="sixteen%3Anine">',
        ];

        yield 'Unclosed iflng' => [
            '<span title="{{iflng::xx}}">{{iflng}} class="broken-out">',
            '<span title=""> class="broken-out">',
        ];

        yield 'Unclosed ifnlng' => [
            '<span title="{{ifnlng::xx}}">{{ifnlng}} class="broken-out">',
            '<span title=""> class="broken-out">',
        ];

        yield 'Unclosed insert tag' => [
            '<span title="{{xx">}} class="broken-out">',
            '<span title="[{]xx">}} class="broken-out">',
        ];

        yield 'Trick comments detection with insert tag' => [
            '<!-- {{plain::--}}> got you! -->',
            '<!-- [{]plain::--[}]> got you! -->',
        ];

        yield 'Do not destroy JSON attributes' => [
            '<span data-myjson=\'{"foo":{"bar":"baz"}}\'>',
            '<span data-myjson=\'{"foo":{"bar":"baz"&#125;&#125;\'>',
        ];

        yield 'Do not destroy nested JSON attributes' => [
            '<span data-myjson=\'[{"foo":{"bar":"baz"}},12.3,"string"]\'>',
            '<span data-myjson=\'[{"foo":{"bar":"baz"&#125;&#125;,12.3,"string"]\'>',
        ];

        yield 'Do not destroy quoted JSON attributes' => [
            '<span data-myjson="{&quot;foo&quot;:{&quot;bar&quot;:&quot;baz&quot;}}">',
            '<span data-myjson="{&quot;foo&quot;:{&quot;bar&quot;:&quot;baz&quot;&#125;&#125;">',
        ];

        yield 'Do not destroy nested quoted JSON attributes' => [
            '<span data-myjson="[{&quot;foo&quot;:{&quot;bar&quot;:&quot;baz&quot;}},12.3,&quot;string&quot;]">',
            '<span data-myjson="[{&quot;foo&quot;:{&quot;bar&quot;:&quot;baz&quot;&#125;&#125;,12.3,&quot;string&quot;]">',
        ];

        yield 'Trick insert tag detection with JSON' => [
            '<span data-myjson=\'{"foo":{"{{bar::":"baz"}}\'>',
            '<span data-myjson=\'{"foo":{"&quot;:&quot;baz&quot;\'>',
        ];
    }

    /**
     * @dataProvider languageInsertTagsProvider
     */
    public function testRemovesLanguageInsertTags(string $source, string $expected, string $pageLanguage = 'en'): void
    {
        $page = $this->createMock(PageModel::class);
        $page
            ->method('__get')
            ->with('language')
            ->willReturn($pageLanguage)
        ;

        $GLOBALS['objPage'] = $page;

        $reflectionClass = new \ReflectionClass(InsertTags::class);

        /** @var InsertTags $insertTags */
        $insertTags = $reflectionClass->newInstanceWithoutConstructor();

        $this->assertSame($expected, $insertTags->replace($source, false));
        $this->assertSame($expected.$expected, $insertTags->replace($source.$source, false));

        $this->assertSame($expected, $insertTags->replace($source));
        $this->assertSame($expected.$expected, $insertTags->replace($source.$source));

        // Test case insensitivity
        $source = str_replace('lng', 'LnG', $source);

        $this->assertSame($expected, $insertTags->replace($source, false));
        $this->assertSame($expected.$expected, $insertTags->replace($source.$source, false));

        $this->assertSame($expected, $insertTags->replace($source));
        $this->assertSame($expected.$expected, $insertTags->replace($source.$source));

        $source = '<a href="'.htmlspecialchars($source).'" title="'.htmlspecialchars($source).'">';
        $expected = '<a href="'.htmlspecialchars($expected).'" title="'.htmlspecialchars($expected).'">';

        $this->assertSame($expected, $insertTags->replace($source, false));
        $this->assertSame($expected.$expected, $insertTags->replace($source.$source, false));

        $this->assertSame($expected, $insertTags->replace($source));
        $this->assertSame($expected.$expected, $insertTags->replace($source.$source));

        unset($GLOBALS['objPage']);
    }

    public function languageInsertTagsProvider(): \Generator
    {
        yield [
            'no insert tag',
            'no insert tag',
        ];

        yield [
            '{{iflng::de}}DE{{iflng}}',
            '',
        ];

        yield [
            '{{iflng::en}}EN{{iflng}}',
            'EN',
        ];

        yield [
            '{{iflng::de}}DE{{iflng}}',
            'DE',
            'de',
        ];

        yield [
            '{{iflng::de,en}}DE,EN{{iflng}}',
            'DE,EN',
        ];

        yield [
            '{{iflng::en*}}EN*{{iflng}}',
            'EN*',
        ];

        yield [
            '{{iflng::en*}}EN*{{iflng}}',
            'EN*',
            'en_US',
        ];

        yield [
            '{{iflng::ru}}RU{{iflng::de}}DE{{iflng}}',
            '',
        ];

        yield [
            '{{iflng::ru}}RU{{iflng::en}}EN{{iflng}}',
            'EN',
        ];

        yield [
            '{{iflng::ru}}RU{{iflng::de}}DE{{iflng}}',
            'DE',
            'de',
        ];

        yield [
            '{{iflng::ru}}RU{{iflng::de,en}}DE,EN{{iflng}}',
            'DE,EN',
        ];

        yield [
            '{{iflng::ru}}RU{{iflng::en*}}EN*{{iflng}}',
            'EN*',
        ];

        yield [
            '{{iflng::ru}}RU{{iflng::en*}}EN*{{iflng}}',
            'EN*',
            'en_US',
        ];

        yield [
            '{{iflng::ru}}RU{{iflng::de}}DE{{iflng}}',
            'RU',
            'ru',
        ];

        yield [
            '{{iflng::ru}}RU{{iflng::en}}EN{{iflng}}',
            'RU',
            'ru',
        ];

        yield [
            '{{iflng::ru}}RU{{iflng::de}}DE{{iflng}}',
            'RU',
            'ru',
        ];

        yield [
            '{{iflng::ru}}RU{{iflng::de,en}}DE,EN{{iflng}}',
            'RU',
            'ru',
        ];

        yield [
            '{{iflng::ru}}RU{{iflng::en*}}EN*{{iflng}}',
            'RU',
            'ru',
        ];

        yield [
            '{{ifnlng::de}}DE{{ifnlng}}',
            'DE',
        ];

        yield [
            '{{ifnlng::en}}EN{{ifnlng}}',
            '',
        ];

        yield [
            '{{ifnlng::de}}DE{{ifnlng}}',
            '',
            'de',
        ];

        yield [
            '{{ifnlng::de,en}}DE,EN{{ifnlng}}',
            '',
        ];

        yield [
            '{{ifnlng::en*}}EN*{{ifnlng}}',
            '',
        ];

        yield [
            '{{ifnlng::en*}}EN*{{ifnlng}}',
            '',
            'en_US',
        ];

        yield [
            '{{ifnlng::ru}}RU{{ifnlng::de}}DE{{ifnlng}}',
            'RUDE',
        ];

        yield [
            '{{ifnlng::ru}}RU{{ifnlng::en}}EN{{ifnlng}}',
            'RU',
        ];

        yield [
            '{{ifnlng::ru}}RU{{ifnlng::de}}DE{{ifnlng}}',
            'RU',
            'de',
        ];

        yield [
            '{{ifnlng::ru}}RU{{ifnlng::de,en}}DE,EN{{ifnlng}}',
            'RU',
        ];

        yield [
            '{{ifnlng::ru}}RU{{ifnlng::en*}}EN*{{ifnlng}}',
            'RU',
        ];

        yield [
            '{{ifnlng::ru}}RU{{ifnlng::en*}}EN*{{ifnlng}}',
            'RU',
            'en_US',
        ];

        yield [
            '{{ifnlng::ru}}RU{{ifnlng::de}}DE{{ifnlng}}',
            'DE',
            'ru',
        ];

        yield [
            '{{ifnlng::ru}}RU{{ifnlng::en}}EN{{ifnlng}}',
            'EN',
            'ru',
        ];

        yield [
            '{{ifnlng::ru}}RU{{ifnlng::de}}DE{{ifnlng}}',
            'DE',
            'ru',
        ];

        yield [
            '{{ifnlng::ru}}RU{{ifnlng::de,en}}DE,EN{{ifnlng}}',
            'DE,EN',
            'ru',
        ];

        yield [
            '{{ifnlng::ru}}RU{{ifnlng::en*}}EN*{{ifnlng}}',
            'EN*',
            'ru',
        ];

        yield [
            '{{ifnlng::de}}not DE{{ifnlng::en}}not EN{{ifnlng}}',
            'not DE',
        ];

        yield [
            '{{ifnlng::de}}not DE{{ifnlng::en}}not EN{{ifnlng}}',
            'not EN',
            'de',
        ];

        yield [
            '{{iflng::de}}should{{iflngg}}not{{iflng-x}}stop{{iflng:}}the{{ifnlng}}conditional{{iflng}}until here',
            'until here',
        ];
    }
}
