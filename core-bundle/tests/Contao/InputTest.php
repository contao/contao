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

use Contao\Config;
use Contao\CoreBundle\String\SimpleTokenParser;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class InputTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected function setUp(): void
    {
        parent::setUp();

        include __DIR__.'/../../src/Resources/contao/config/default.php';

        $GLOBALS['TL_CONFIG']['allowedTags'] = ($GLOBALS['TL_CONFIG']['allowedTags'] ?? '').'<use>';

        $GLOBALS['TL_CONFIG']['allowedAttributes'] = serialize(
            array_merge(
                unserialize($GLOBALS['TL_CONFIG']['allowedAttributes'] ?? ''),
                [['key' => 'use', 'value' => 'xlink:href']]
            )
        );

        $container = new ContainerBuilder();
        $container->setParameter('kernel.charset', 'UTF-8');
        $container->setParameter('contao.sanitizer.allowed_url_protocols', ['http', 'https', 'mailto', 'tel']);

        System::setContainer($container);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_CONFIG']);

        $this->resetStaticProperties([System::class]);

        parent::tearDown();
    }

    /**
     * @dataProvider stripTagsProvider
     */
    public function testStripTags(string $source, string $expected): void
    {
        $allowedTags = Config::get('allowedTags');
        $allowedAttributes = Config::get('allowedAttributes');

        $this->assertSame($expected, Input::stripTags($source, $allowedTags, $allowedAttributes));
    }

    public function stripTagsProvider(): \Generator
    {
        yield 'Encodes tags' => [
            'Text <with> tags',
            'Text &lt;with&#62; tags',
        ];

        yield 'Keeps allowed tags' => [
            'Text <with> <span> tags',
            'Text &lt;with&#62; <span> tags',
        ];

        yield 'Removes attributes' => [
            'foo <span onerror=alert(1)> bar',
            'foo <span> bar',
        ];

        yield 'Keeps allowed attributes' => [
            'foo <span onerror="foo" title="baz" href="bar"> bar',
            'foo <span title="baz"> bar',
        ];

        yield 'Keeps underscores in allowed attributes' => [
            'foo <span data-foo_bar="baz"> bar',
            'foo <span data-foo_bar="baz"> bar',
        ];

        yield 'Reformats attributes' => [
            "<span \n \t title = \nwith-spaces class\n=' with \" and &#039; quotes' lang \t =\"with &quot; and ' quotes \t \n \" data-boolean-flag data-int = 0>",
            "<span title=\"with-spaces\" class=\" with &quot; and &#039; quotes\" lang=\"with &quot; and &#039; quotes \t \n \" data-boolean-flag=\"\" data-int=\"0\">",
        ];

        yield 'Encodes insert tags in attributes' => [
            '<a href = {{br}} title = {{br}}>',
            '<a href="{{br|urlattr}}" title="{{br|attr}}">',
        ];

        yield 'Encodes nested insert tags' => [
            '<a href="{{email_url::{{link_url::1}}}}">',
            '<a href="{{email_url::{{link_url::1|urlattr}}|urlattr}}">',
        ];

        yield 'Does not allow colon in URLs' => [
            '<a href="ja{{noop}}vascript:alert(1)">',
            '<a href="ja{{noop|urlattr}}vascript%3Aalert(1)">',
        ];

        yield 'Allows colon for absolute URLs' => [
            '<a href="http://example.com"><a href="https://example.com"><a href="mailto:john@example.com"><a href="tel:0123456789">',
            '<a href="http://example.com"><a href="https://example.com"><a href="mailto:john@example.com"><a href="tel:0123456789">',
        ];

        yield 'Does not allow colon in URLs insert tags' => [
            '<a href="{{email_url::javascript:alert(1)|attr}}">',
            '<a href="{{email_url::javascript:alert(1)|urlattr}}">',
        ];

        yield 'Does not get tricked by stripping null escapes' => [
            '<img src="foo{{bar}\\0}baz">',
            '<img src="foo{{bar&#125;&#92;0&#125;baz">',
        ];

        yield 'Does not get tricked by stripping insert tags' => [
            '<img src="foo{{bar}{{noop}}}baz">',
            '<img src="foo{{bar&#125;{{noop|urlattr}}&#125;baz">',
        ];

        yield 'Do not destroy JSON attributes' => [
            '<span data-myjson=\'{"foo":{"bar":"baz"}}\'>',
            '<span data-myjson="{&quot;foo&quot;:{&quot;bar&quot;:&quot;baz&quot;&#125;&#125;">',
        ];

        yield 'Do not destroy nested JSON attributes' => [
            '<span data-myjson=\'[{"foo":{"bar":"baz"}},12.3,"string"]\'>',
            '<span data-myjson="[{&quot;foo&quot;:{&quot;bar&quot;:&quot;baz&quot;&#125;&#125;,12.3,&quot;string&quot;]">',
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
            '<span data-myjson="{&quot;foo&quot;:{&quot;{{bar::&quot;:&quot;baz&quot;|attr}}">',
        ];

        yield 'Allows for comments' => [
            '<!-- my comment --> <span non-allowed="should be removed">',
            '<!-- my comment --> <span>',
        ];

        yield 'Encodes comments contents' => [
            '<!-- my comment <script>alert(1)</script> --> <span non-allowed="should be removed">',
            '<!-- my comment &lt;script&#62;alert(1)&lt;/script&#62; --> <span>',
        ];

        yield 'Does not encode allowed elements in comments' => [
            '<!-- my comment <span non-allowed="should be removed" title="--&#62;"> --> <span non-allowed="should be removed">',
            '<!-- my comment <span title="--&#62;"> --> <span>',
        ];

        yield 'Normalize short comments' => [
            '<!--> a <!---> b <!----> c <!-----> d',
            '<!----> a <!----> b <!----> c <!-----> d',
        ];

        yield 'Nested comments' => [
            '<!-- a <!-- b --> c --> d <!-- a> <!-- b> --> c> --> d>',
            '<!-- a &#60;!-- b --> c --&#62; d <!-- a&#62; &#60;!-- b&#62; --> c&#62; --&#62; d&#62;',
        ];

        yield 'Style tag' => [
            '<style not-allowed="x" media="(min-width: 10px)"> body { background: #fff; color: rgba(1, 2, 3, 0.5) } #header::after { content: "> <!--"; } @media print { #header { display: none; }}</style>>>',
            '<style media="(min-width: 10px)"> body { background: #fff; color: rgba(1, 2, 3, 0.5) } #header::after { content: "> <!--"; } @media print { #header { display: none; }}</style>&#62;&#62;',
        ];

        yield 'Style tag with comment' => [
            '<style not-allowed="x" media="(min-width: 10px)"><!-- body { background: #fff; color: rgba(1, 2, 3, 0.5) } #header::after { content: "> <!--"; } @media print { #header { display: none; }}--></style>>>',
            '<style media="(min-width: 10px)"><!-- body { background: #fff; color: rgba(1, 2, 3, 0.5) } #header::after { content: "> <!--"; } @media print { #header { display: none; }}--></style>&#62;&#62;',
        ];

        yield 'Style nested in comment' => [
            '<!-- <style> --> content: ""; <span non-allowed="x"> <style> --> content: ""; <span non-allowed="x">',
            '<!-- <style> --> content: &#34;&#34;; <span> <style> --> content: ""; <span non-allowed="x">',
        ];

        yield 'Allows namespaced attributes' => [
            '<use xlink:href="http://example.com">',
            '<use xlink:href="http://example.com">',
        ];

        yield 'Does not allow colon in namespaced URL attributes' => [
            '<use xlink:href="ja{{noop}}vascript:alert(1)">',
            '<use xlink:href="ja{{noop|urlattr}}vascript%3Aalert(1)">',
        ];

        yield [
            '<form action="javascript:alert(document.domain)"><input type="submit" value="XSS" /></form>',
            '<form><input></form>',
        ];

        yield [
            '<img src onerror=alert(document.domain)>',
            '<img src="">',
        ];

        yield [
            '<SCRIPT SRC=http://xss.rocks/xss.js></SCRIPT>',
            '&lt;SCRIPT SRC&#61;http://xss.rocks/xss.js&#62;&lt;/SCRIPT&#62;',
        ];

        yield [
            'javascript:/*--></title></style></textarea></script></xmp><svg/onload=\'+/"/+/onmouseover=1/+/[*/[]/+alert(1)//\'>',
            'javascript:/*--&#62;&lt;/title&#62;</style></textarea>&lt;/script&#62;&lt;/xmp&#62;&lt;svg/onload&#61;&#39;+/&#34;/+/onmouseover&#61;1/+/[*/[]/+alert(1)//&#39;&#62;',
        ];

        yield [
            '<IMG SRC="javascript:alert(\'XSS\');">',
            '<img src="javascript%3Aalert(&#039;XSS&#039;);">',
        ];

        yield [
            '<IMG SRC=JaVaScRiPt:alert(\'XSS\')>',
            '<img src="JaVaScRiPt%3Aalert(&#039;XSS&#039;)">',
        ];

        yield [
            '<IMG SRC=javascript:alert(&quot;XSS&quot;)>',
            '<img src="javascript%3Aalert(&quot;XSS&quot;)">',
        ];

        yield [
            '<IMG SRC=`javascript:alert("RSnake says, \'XSS\'")`>',
            '<img src="`javascript%3Aalert(&quot;RSnake">',
        ];

        yield [
            '\<a onmouseover="alert(document.cookie)"\>xxs link\</a\>',
            '\<a>xxs link\&lt;/a\&#62;',
        ];

        yield [
            '\<a onmouseover=alert(document.cookie)\>xxs link\</a\>',
            '\<a>xxs link\&lt;/a\&#62;',
        ];

        yield [
            '<IMG """><SCRIPT>alert("XSS")</SCRIPT>"\>',
            '<img>',
        ];

        yield [
            '<img src=x onerror="&#0000106&#0000097&#0000118&#0000097&#0000115&#0000099&#0000114&#0000105&#0000112&#0000116&#0000058&#0000097&#0000108&#0000101&#0000114&#0000116&#0000040&#0000039&#0000088&#0000083&#0000083&#0000039&#0000041">',
            '<img src="x">',
        ];

        yield [
            '<IMG SRC=&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;&#97;&#108;&#101;&#114;&#116;&#40;&#39;&#88;&#83;&#83;&#39;&#41;>',
            '<img src="&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;%3A&#97;&#108;&#101;&#114;&#116;&#40;&#39;&#88;&#83;&#83;&#39;&#41;">',
        ];

        yield [
            '<IMG SRC=&#0000106&#0000097&#0000118&#0000097&#0000115&#0000099&#0000114&#0000105&#0000112&#0000116&#0000058&#0000097&#0000108&#0000101&#0000114&#0000116&#0000040&#0000039&#0000088&#0000083&#0000083&#0000039&#0000041>',
            '<img src="&amp;#0000106&amp;#0000097&amp;#0000118&amp;#0000097&amp;#0000115&amp;#0000099&amp;#0000114&amp;#0000105&amp;#0000112&amp;#0000116&amp;#0000058&amp;#0000097&amp;#0000108&amp;#0000101&amp;#0000114&amp;#0000116&amp;#0000040&amp;#0000039&amp;#0000088&amp;#0000083&amp;#0000083&amp;#0000039&amp;#0000041">',
        ];

        yield [
            '<IMG SRC="jav&#x0A;ascript:alert(\'XSS\');">',
            '<img src="jav&#x0A;ascript%3Aalert(&#039;XSS&#039;);">',
        ];

        yield [
            '<IMG SRC=" &#14; javascript:alert(\'XSS\');">',
            '<img src=" &#14; javascript%3Aalert(&#039;XSS&#039;);">',
        ];

        yield [
            '<SCRIPT/SRC="http://xss.rocks/xss.js"></SCRIPT>',
            '&lt;SCRIPT/SRC&#61;&#34;http://xss.rocks/xss.js&#34;&#62;&lt;/SCRIPT&#62;',
        ];

        yield [
            '<BODY onload!#$%&()*~+-_.,:;?@[/|\]^`=alert("XSS")>',
            '&lt;BODY onload!#$%&()*~+-_.,:;?@[/|\]^`&#61;alert(&#34;XSS&#34;)&#62;',
        ];

        yield [
            '<<SCRIPT>alert("XSS");//\<</SCRIPT>',
            '&lt;&lt;SCRIPT&#62;alert(&#34;XSS&#34;);//\&lt;&lt;/SCRIPT&#62;',
        ];

        yield [
            '<IMG SRC="`(\'XSS\')"`',
            '',
        ];

        yield [
            '</TITLE><SCRIPT>alert("XSS");</SCRIPT>',
            '&lt;/TITLE&#62;&lt;SCRIPT&#62;alert(&#34;XSS&#34;);&lt;/SCRIPT&#62;',
        ];

        yield [
            '<INPUT TYPE="IMAGE" SRC="javascript:alert(\'XSS\');">',
            '<input>',
        ];

        yield [
            '<BODY BACKGROUND="javascript:alert(\'XSS\')">',
            '&lt;BODY BACKGROUND&#61;&#34;javascript:alert(&#39;XSS&#39;)&#34;&#62;',
        ];

        yield [
            '<IMG DYNSRC="javascript:alert(\'XSS\')">',
            '<img>',
        ];

        yield [
            '<IMG LOWSRC="javascript:alert(\'XSS\')">',
            '<img>',
        ];

        yield [
            '<svg/onload=alert(\'XSS\')>',
            '&lt;svg/onload&#61;alert(&#39;XSS&#39;)&#62;',
        ];

        yield [
            '<LINK REL="stylesheet" HREF="javascript:alert(\'XSS\');">',
            '&lt;LINK REL&#61;&#34;stylesheet&#34; HREF&#61;&#34;javascript:alert(&#39;XSS&#39;);&#34;&#62;',
        ];
    }

    /**
     * @dataProvider stripTagsNoTagsAllowedProvider
     */
    public function testStripTagsNoTagsAllowed(string $source, string $expected): void
    {
        $this->assertSame($expected, Input::stripTags($source));
    }

    public function stripTagsNoTagsAllowedProvider(): \Generator
    {
        yield 'Encodes tags' => [
            'Text <with> tags',
            'Text &lt;with> tags',
        ];

        yield 'Does not encode other special characters' => [
            'xLmpwZw==',
            'xLmpwZw==',
        ];
    }

    public function testStripTagsAllAttributesAllowed(): void
    {
        $html = '<dIv class=gets-normalized bar-foo-something = \'keep\'><spAN class=gets-normalized bar-foo-something = \'keep\'>foo</SPan></DiV>';
        $expected = '<div class="gets-normalized" bar-foo-something="keep"><span>foo</span></div>';

        $this->assertSame($expected, Input::stripTags($html, '<div><span>', serialize([['key' => 'div', 'value' => '*']])));
    }

    public function testStripTagsAllAttributesAllowedAllTags(): void
    {
        $html = '<spAN class=no-normalization-happens-if-all-is-allowed>foo</SPan>';

        $this->assertSame($html, Input::stripTags($html, '<span>', serialize([['key' => '*', 'value' => '*']])));
    }

    public function testStripTagsNoAttributesAllowed(): void
    {
        $html = '<dIv class=gets-normalized bar-foo-something = \'keep\'><spAN class=gets-normalized bar-foo-something = \'keep\'>foo</SPan></DiV><notallowed></notallowed>';
        $expected = '<div><span>foo</span></div>&lt;notallowed&#62;&lt;/notallowed&#62;';

        $this->assertSame($expected, Input::stripTags($html, '<div><span>', serialize([['key' => '', 'value' => '']])));
        $this->assertSame($expected, Input::stripTags($html, '<div><span>', serialize([[]])));
        $this->assertSame($expected, Input::stripTags($html, '<div><span>', serialize([])));
        $this->assertSame($expected, Input::stripTags($html, '<div><span>', serialize(null)));
        $this->assertSame($expected, Input::stripTags($html, '<div><span>', ''));
    }

    public function testStripTagsScriptAllowed(): void
    {
        $this->assertSame(
            '<script>alert(foo > bar);</script>foo &#62; bar',
            Input::stripTags('<script>alert(foo > bar);</script>foo > bar', '<div><span><script>', '')
        );

        $this->assertSame(
            '<script><!-- alert(foo > bar); --></script>foo &#62; bar',
            Input::stripTags('<script><!-- alert(foo > bar); --></script>foo > bar', '<div><span><script>', '')
        );

        $this->assertSame(
            '<script><!-- alert(foo > bar); </script>foo &#62; bar',
            Input::stripTags('<scrIpt type="VBScript"><!-- alert(foo > bar); </SCRiPT >foo > bar', '<div><span><script>', '')
        );
    }

    /**
     * @group legacy
     */
    public function testStripTagsMissingAttributesParameter(): void
    {
        $html = '<dIv class=gets-normalized bar-foo-something = \'keep\'><spAN notallowed="x" class=gets-normalized bar-foo-something = \'keep\'>foo</SPan></DiV><notallowed></notallowed>';
        $expected = '<div class="gets-normalized"><span class="gets-normalized">foo</span></div>&lt;notallowed&#62;&lt;/notallowed&#62;';

        $this->expectDeprecation('%sUsing Contao\Input::stripTags() with $strAllowedTags but without $strAllowedAttributes has been deprecated%s');

        $this->assertSame($expected, Input::stripTags($html, '<div><span>'));
    }

    /**
     * @group legacy
     *
     * @dataProvider simpleTokensWithHtmlProvider
     */
    public function testSimpleTokensWithHtml(string $source, array $tokens, string $expected): void
    {
        $simpleTokenParser = new SimpleTokenParser(new ExpressionLanguage());

        System::getContainer()->set('contao.string.simple_token_parser', $simpleTokenParser);

        // Input encode the source
        Input::resetCache();
        $_POST = ['html' => $source];
        $html = Input::postHtml('html', true);
        $_POST = [];
        Input::resetCache();

        $this->assertSame($expected, $simpleTokenParser->parse($html, $tokens));

        $this->expectDeprecation('%sparseSimpleTokens()%shas been deprecated%s');

        $this->assertSame($expected, StringUtil::parseSimpleTokens($html, $tokens));
    }

    public function simpleTokensWithHtmlProvider(): \Generator
    {
        yield 'Token only' => [
            'foo##foo##baz',
            ['foo' => 'bar'],
            'foobarbaz',
        ];

        yield 'Token between tags' => [
            '<b>##foo##</b>',
            ['foo' => 'bar'],
            '<b>bar</b>',
        ];

        yield 'Token in attribute' => [
            '<b title="##foo##">##foo##</b>',
            ['foo' => 'bar'],
            '<b title="bar">bar</b>',
        ];

        yield 'Token in URL attribute' => [
            '<a href="##foo##">##foo##</a>',
            ['foo' => 'bar'],
            '<a href="bar">bar</a>',
        ];

        yield 'Condition only' => [
            'a{if foo != ""}b{else}c{endif}d{if bar<6}e{else}f{endif}g{if bar>4}h{else}i{endif}j',
            ['foo' => 'bar', 'bar' => 5],
            'abdeghj',
        ];

        yield 'Condition between tags' => [
            '<b>a{if foo != ""}b{else}c{endif}d{if bar<6}e{else}f{endif}g{if bar>4}h{else}i{endif}j</b>',
            ['foo' => 'bar', 'bar' => 5],
            '<b>abdeghj</b>',
        ];

        yield 'Condition in attribute' => [
            '<b title=\'a{if foo != ""}b{else}c{endif}d{if bar<6}e{else}f{endif}g{if bar&gt;4}h{else}i{endif}j\'></b>',
            ['foo' => 'bar', 'bar' => 5],
            '<b title="abdeghj"></b>',
        ];

        yield 'Condition in URL attribute' => [
            '<a href=\'a{if foo != ""}b{else}c{endif}d{if bar<6}e{else}f{endif}g{if bar&gt;4}h{else}i{endif}j\'></a>',
            ['foo' => 'bar', 'bar' => 5],
            '<a href="abdeghj"></a>',
        ];

        yield 'Condition encoded in attribute' => [
            '<b title=\'a{if foo != &quot;&quot;}b{else}c{endif}d{if bar&lt;6}e{else}f{endif}g{if bar&gt;4}h{else}i{endif}j\'></b>',
            ['foo' => 'bar', 'bar' => 5],
            '<b title="abdeghj"></b>',
        ];

        yield 'Condition encoded in URL attribute' => [
            '<a href=\'a{if foo != &quot;&quot;}b{else}c{endif}d{if bar&lt;6}e{else}f{endif}g{if bar&gt;4}h{else}i{endif}j\'></a>',
            ['foo' => 'bar', 'bar' => 5],
            '<a href="abdeghj"></a>',
        ];
    }
}
