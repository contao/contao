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
        return explode('::', $tag, 2)[1];
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
    }
}
