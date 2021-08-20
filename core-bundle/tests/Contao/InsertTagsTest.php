<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\CoreBundle\Tests\TestCase;
use Contao\InsertTags;
use Contao\PageModel;
use Contao\System;

/**
 * Tests the InsertTags class.
 *
 * @group legacy
 */
class InsertTagsTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        include_once __DIR__.'/../../src/Resources/contao/helper/functions.php';

        $GLOBALS['TL_CONFIG']['characterSet'] = 'UTF-8';
        $GLOBALS['TL_HOOKS']['replaceInsertTags'][] = [__CLASS__, 'replaceInsertTagsHook'];

        System::setContainer($this->mockContainerWithContaoScopes());
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($GLOBALS['TL_CONFIG'], $GLOBALS['TL_HOOKS']);
    }

    public function replaceInsertTagsHook($tag)
    {
        return explode('::', $tag, 2)[1];
    }

    /**
     * Tests encoding insert tags in html attributes.
     *
     * @dataProvider encodeHtmlAttributesProvider
     *
     * @param string $source
     * @param string $expected
     */
    public function testEncodeHtmlAttributes($source, $expected)
    {
        $reflectionClass = new \ReflectionClass(InsertTags::class);

        /** @var InsertTags $insertTags */
        $insertTags = $reflectionClass->newInstanceWithoutConstructor();

        $this->assertSame($expected, $insertTags->replace($source, false));
    }

    /**
     * Provides the data for the testStripTags() method.
     *
     * @return array
     */
    public function encodeHtmlAttributesProvider()
    {
        return [
            'Simple tag' => [
                'bar{{plain::foo}}baz',
                'barfoobaz',
            ],
            'Quote in plain text' => [
                'foo{{plain::"}}bar',
                'foo"bar',
            ],
            'Quote before tag' => [
                '{{plain::"}}<span>',
                '"<span>',
            ],
            'Quote after tag' => [
                '<span>{{plain::"}}',
                '<span>"',
            ],
            'Quote in attribute' => [
                '<span title=\'{{plain::"}}\'>',
                '<span title=\'&quot;\'>',
            ],
            'Quote in unquoted attribute' => [
                '<span title={{plain::"}}>',
                '<span title=&quot;>',
            ],
            'Quote in single quoted attribute' => [
                '<span title="{{plain::\'}}">',
                '<span title="&#039;">',
            ],
            'Quote outside attribute' => [
                '<span title="" {{plain::"}}>',
                '<span title="" &quot;>',
            ],
            'Trick tag detection' => [
                '<span title=">" class=\'{{plain::"}}\'>',
                '<span title=">" class=\'&quot;\'>',
            ],
            'Trick tag detection with slash' => [
                '<span/title=">"/class=\'{{plain::"}}\'>',
                '<span/title=">"/class=\'&quot;\'>',
            ],
            'Trick tag detection with two tags' => [
                '<span /="notanattribute title="> {{plain::\'}} " > {{plain::\'}}',
                '<span /="notanattribute title="> &#039; " > \'',
            ],
            'Trick tag detection with not a tag' => [
                '<önotag{{plain::"}} <-notag {{plain::"}}',
                '<önotag" <-notag "',
            ],
            'Trick tag detection with closing tag' => [
                '</span =="><span title=">{{plain::<>}}<span title=">{{plain::<>}}">',
                '</span =="><span title="><><span title=">&lt;&gt;">',
            ],
            'Trick tag detection with not a tag or comment' => [
                '<-span <x =="><span title=">{{plain::<>}}<span title=">{{plain::<>}}">',
                '<-span <x =="><span title="><><span title=">&lt;&gt;">',
            ],
            'Trick tag detection with bogus / comment' => [
                '</-span <x =="><span title=">{{plain::<>}}<span title=">{{plain::<>}}">',
                '</-span <x =="><span title=">&lt;&gt;<span title="><>">',
            ],
            'Trick tag detection with bogus ? comment' => [
                '<?span <x =="><span title=">{{plain::<>}}<span title=">{{plain::<>}}">',
                '<?span <x =="><span title=">&lt;&gt;<span title="><>">',
            ],
            'Trick tag detection with bogus ! comment' => [
                '<!span <x =="><span title=">{{plain::<>}}<span title=">{{plain::<>}}">',
                '<!span <x =="><span title=">&lt;&gt;<span title="><>">',
            ],
            'Trick tag detection with bogus !- comment' => [
                '<!-span <x =="><span title=">{{plain::<>}}<span title=">{{plain::<>}}">',
                '<!-span <x =="><span title=">&lt;&gt;<span title="><>">',
            ],
            'Trick tag detection with comment' => [
                '<!-- <span title="-->{{plain::<>}}<span title=">{{plain::<>}}">',
                '<!-- <span title="--><><span title=">&lt;&gt;">',
            ],
            'Trick tag detection with script' => [
                '<script><span title="</SCRIPT/>{{plain::<>}}<span title=">{{plain::<>}}">',
                '<script><span title="</SCRIPT/><><span title=">&lt;&gt;">',
            ],
            'Trick tag detection with textarea' => [
                '<textArea foo=><span title="</TEXTaREA>{{plain::<>}}<span title=">{{plain::<>}}">',
                '<textArea foo=><span title="</TEXTaREA><><span title=">&lt;&gt;">',
            ],
            'Not trick tag detection with pre' => [
                '<pre foo=><span title="</pre>{{plain::<>}}<span title=">{{plain::<>}}">',
                '<pre foo=><span title="</pre>&lt;&gt;<span title="><>">',
            ],
            'Do not URL encode inside regular attributes' => [
                '<a title="sixteen{{plain:::}}nine">',
                '<a title="sixteen:nine">',
            ],
            'URL encode inside source attributes' => [
                '<a href="sixteen{{plain:::}}nine">',
                '<a href="sixteen%3Anine">',
            ],
            'URL encode inside source attributes with existing flag' => [
                '<img src="sixteen{{plain:::|strtoupper}}nine">',
                '<img src="sixteen%3Anine">',
            ],
            'URL encode inside source attributes with existing specialchars flag' => [
                '<a href="sixteen{{plain:::|attr}}nine">',
                '<a href="sixteen%3Anine">',
            ],
            'URL encode inside source attributes with existing flags' => [
                '<a href="sixteen{{plain:::|attr|strtoupper}}nine">',
                '<a href="sixteen%3Anine">',
            ],
            'Allow safe protocols in URL attributes' => [
                '<a href="{{plain::https://example.com/}}"><a href="{{plain::http://example.com/}}"><a href="{{plain::ftp://example.com/}}"><a href="{{plain::mailto:test@example.com}}"><a href="{{plain::tel:+0123456789}}"><a href="{{plain::data:text/plain,test}}">',
                '<a href="https://example.com/"><a href="http://example.com/"><a href="ftp://example.com/"><a href="mailto:test@example.com"><a href="tel:+0123456789"><a href="data:text/plain,test">',
            ],
            'Trick attributes detection with slash' => [
                '<a/href="sixteen{{plain:::}}nine">',
                '<a/href="sixteen%3Anine">',
            ],
            'Trick attributes detection with non-attribute' => [
                '<ahref=" href="sixteen{{plain:::}}nine">',
                '<ahref=" href="sixteen%3Anine">',
            ],
            'Trick attributes detection with dot' => [
                '<a.href=" href="sixteen{{plain:::}}nine">',
                '<a.href=" href="sixteen%3Anine">',
            ],
            'Unclosed iflng' => [
                '<span title="{{iflng::xx}}">{{iflng}} class="broken-out">',
                '<span title=""> class="broken-out">',
            ],
            'Unclosed ifnlng' => [
                '<span title="{{ifnlng::xx}}">{{ifnlng}} class="broken-out">',
                '<span title=""> class="broken-out">',
            ],
            'Unclosed insert tag' => [
                '<span title="{{xx">}} class="broken-out">',
                '<span title="[{]xx">}} class="broken-out">',
            ],
            'Trick comments detection with insert tag' => [
                '<!-- {{plain::--}}> got you! -->',
                '<!-- [{]plain::--[}]> got you! -->',
            ],
            'Do not destroy JSON attributes' => [
                '<span data-myjson=\'{"foo":{"bar":"baz"}}\'>',
                '<span data-myjson=\'{"foo":{"bar":"baz"&#125;&#125;\'>',
            ],
            'Do not destroy nested JSON attributes' => [
                '<span data-myjson=\'[{"foo":{"bar":"baz"}},12.3,"string"]\'>',
                '<span data-myjson=\'[{"foo":{"bar":"baz"&#125;&#125;,12.3,"string"]\'>',
            ],
            'Do not destroy quoted JSON attributes' => [
                '<span data-myjson="{&quot;foo&quot;:{&quot;bar&quot;:&quot;baz&quot;}}">',
                '<span data-myjson="{&quot;foo&quot;:{&quot;bar&quot;:&quot;baz&quot;&#125;&#125;">',
            ],
            'Do not destroy nested quoted JSON attributes' => [
                '<span data-myjson="[{&quot;foo&quot;:{&quot;bar&quot;:&quot;baz&quot;}},12.3,&quot;string&quot;]">',
                '<span data-myjson="[{&quot;foo&quot;:{&quot;bar&quot;:&quot;baz&quot;&#125;&#125;,12.3,&quot;string&quot;]">',
            ],
            'Trick insert tag detection with JSON' => [
                '<span data-myjson=\'{"foo":{"{{bar::":"baz"}}\'>',
                '<span data-myjson=\'{"foo":{"&quot;:&quot;baz&quot;\'>',
            ],
        ];
    }

    /**
     * @dataProvider languageInsertTagsProvider
     */
    public function testRemovesLanguageInsertTags($source, $expected, $pageLanguage = 'en')
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

    public function languageInsertTagsProvider()
    {
        return [
            [
                'no insert tag',
                'no insert tag',
            ],
            [
                '{{iflng::de}}DE{{iflng}}',
                '',
            ],
            [
                '{{iflng::en}}EN{{iflng}}',
                'EN',
            ],
            [
                '{{iflng::de}}DE{{iflng}}',
                'DE',
                'de',
            ],
            [
                '{{iflng::de,en}}DE,EN{{iflng}}',
                'DE,EN',
            ],
            [
                '{{iflng::en*}}EN*{{iflng}}',
                'EN*',
            ],
            [
                '{{iflng::en*}}EN*{{iflng}}',
                'EN*',
                'en_US',
            ],
            [
                '{{iflng::ru}}RU{{iflng::de}}DE{{iflng}}',
                '',
            ],
            [
                '{{iflng::ru}}RU{{iflng::en}}EN{{iflng}}',
                'EN',
            ],
            [
                '{{iflng::ru}}RU{{iflng::de}}DE{{iflng}}',
                'DE',
                'de',
            ],
            [
                '{{iflng::ru}}RU{{iflng::de,en}}DE,EN{{iflng}}',
                'DE,EN',
            ],
            [
                '{{iflng::ru}}RU{{iflng::en*}}EN*{{iflng}}',
                'EN*',
            ],
            [
                '{{iflng::ru}}RU{{iflng::en*}}EN*{{iflng}}',
                'EN*',
                'en_US',
            ],
            [
                '{{iflng::ru}}RU{{iflng::de}}DE{{iflng}}',
                'RU',
                'ru',
            ],
            [
                '{{iflng::ru}}RU{{iflng::en}}EN{{iflng}}',
                'RU',
                'ru',
            ],
            [
                '{{iflng::ru}}RU{{iflng::de}}DE{{iflng}}',
                'RU',
                'ru',
            ],
            [
                '{{iflng::ru}}RU{{iflng::de,en}}DE,EN{{iflng}}',
                'RU',
                'ru',
            ],
            [
                '{{iflng::ru}}RU{{iflng::en*}}EN*{{iflng}}',
                'RU',
                'ru',
            ],
            [
                '{{ifnlng::de}}DE{{ifnlng}}',
                'DE',
            ],
            [
                '{{ifnlng::en}}EN{{ifnlng}}',
                '',
            ],
            [
                '{{ifnlng::de}}DE{{ifnlng}}',
                '',
                'de',
            ],
            [
                '{{ifnlng::de,en}}DE,EN{{ifnlng}}',
                '',
            ],
            [
                '{{ifnlng::en*}}EN*{{ifnlng}}',
                '',
            ],
            [
                '{{ifnlng::en*}}EN*{{ifnlng}}',
                '',
                'en_US',
            ],
            [
                '{{ifnlng::ru}}RU{{ifnlng::de}}DE{{ifnlng}}',
                'RUDE',
            ],
            [
                '{{ifnlng::ru}}RU{{ifnlng::en}}EN{{ifnlng}}',
                'RU',
            ],
            [
                '{{ifnlng::ru}}RU{{ifnlng::de}}DE{{ifnlng}}',
                'RU',
                'de',
            ],
            [
                '{{ifnlng::ru}}RU{{ifnlng::de,en}}DE,EN{{ifnlng}}',
                'RU',
            ],
            [
                '{{ifnlng::ru}}RU{{ifnlng::en*}}EN*{{ifnlng}}',
                'RU',
            ],
            [
                '{{ifnlng::ru}}RU{{ifnlng::en*}}EN*{{ifnlng}}',
                'RU',
                'en_US',
            ],
            [
                '{{ifnlng::ru}}RU{{ifnlng::de}}DE{{ifnlng}}',
                'DE',
                'ru',
            ],
            [
                '{{ifnlng::ru}}RU{{ifnlng::en}}EN{{ifnlng}}',
                'EN',
                'ru',
            ],
            [
                '{{ifnlng::ru}}RU{{ifnlng::de}}DE{{ifnlng}}',
                'DE',
                'ru',
            ],
            [
                '{{ifnlng::ru}}RU{{ifnlng::de,en}}DE,EN{{ifnlng}}',
                'DE,EN',
                'ru',
            ],
            [
                '{{ifnlng::ru}}RU{{ifnlng::en*}}EN*{{ifnlng}}',
                'EN*',
                'ru',
            ],
            [
                '{{ifnlng::de}}not DE{{ifnlng::en}}not EN{{ifnlng}}',
                'not DE',
            ],
            [
                '{{ifnlng::de}}not DE{{ifnlng::en}}not EN{{ifnlng}}',
                'not EN',
                'de',
            ],
            [
                '{{iflng::de}}should{{iflngg}}not{{iflng-x}}stop{{iflng:}}the{{ifnlng}}conditional{{iflng}}until here',
                'until here',
            ],
        ];
    }
}
