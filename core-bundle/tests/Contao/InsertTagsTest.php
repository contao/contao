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
}
