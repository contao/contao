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
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\String\SimpleTokenParser;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Input;
use Contao\InputEncodingMode;
use Contao\System;
use Contao\Widget;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class InputTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupServerEnvGetPost();

        include __DIR__.'/../../contao/config/default.php';

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
        $container->set('request_stack', new RequestStack());
        $container->set('contao.routing.scope_matcher', $this->createMock(ScopeMatcher::class));

        System::setContainer($container);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_CONFIG']);

        $_COOKIE = [];

        $this->restoreServerEnvGetPost();
        $this->resetStaticProperties([System::class, Input::class]);

        parent::tearDown();
    }

    /**
     * @group legacy
     *
     * @dataProvider encodeInputProvider
     */
    public function testCleansTheGlobalArrays(string $source, string $expected): void
    {
        $_GET = $_POST = $_COOKIE = [$source => 1];

        if ($source !== $expected) {
            $this->expectDeprecation('%scleanKey()" has been deprecated%s');
        }

        Input::initialize();

        $this->assertSame($expected, array_keys($_GET)[0]);
        $this->assertSame($expected, array_keys($_POST)[0]);
        $this->assertSame($expected, array_keys($_COOKIE)[0]);
    }

    /**
     * @group legacy
     *
     * @dataProvider encodeInputProvider
     */
    public function testGetAndPostEncoded(string $source, string $expected, string|null $expectedEncoded = null): void
    {
        $expectedEncoded ??= $expected;

        $this->assertSame($expected, Input::encodeInput($source, InputEncodingMode::encodeLessThanSign));
        $this->assertSame($expectedEncoded, Input::encodeInput($source, InputEncodingMode::encodeAll));

        Config::set('allowedTags', '');
        Config::set('allowedAttributes', '');

        System::getContainer()->set('request_stack', $stack = new RequestStack());
        $stack->push(new Request(['key' => $source], ['key' => $source], [], ['key' => $source]));

        $this->assertSame($expected, Input::get('key', true));
        $this->assertSame($expected, Input::post('key', true));
        $this->assertSame($expected, Input::cookie('key', true));

        $this->assertSame($expectedEncoded, Input::get('key', false));
        $this->assertSame($expectedEncoded, Input::post('key', false));
        $this->assertSame($expectedEncoded, Input::cookie('key', false));

        $this->assertSame($source, Input::postUnsafeRaw('key'));

        $stack->pop();
        $_GET = $_POST = $_COOKIE = ['key' => $source];

        $this->expectDeprecation('%sGetting data from $_%s has been deprecated%s');

        $this->assertSame($expected, Input::get('key', true));
        $this->assertSame($expected, Input::post('key', true));
        $this->assertSame($expected, Input::cookie('key', true));

        $this->assertSame($expectedEncoded, Input::get('key', false));
        $this->assertSame($expectedEncoded, Input::post('key', false));
        $this->assertSame($expectedEncoded, Input::cookie('key', false));

        $this->assertSame($source, Input::postUnsafeRaw('key'));

        $this->expectDeprecation('%sstripTags() without setting allowed tags and allowed attributes has been deprecated%s');
        $this->assertSame($expected, Input::postHtml('key', true));
        $this->assertSame($expectedEncoded, Input::postHtml('key', false));
    }

    /**
     * @group legacy
     *
     * @dataProvider encodeInputProvider
     */
    public function testBackendRoundtrip(string $source, string $expected, string|null $expectedEncoded = null): void
    {
        $expectedEncoded ??= $expected;

        $specialchars = (new \ReflectionClass(Widget::class))->getMethod('specialcharsValue')->invoke(...);

        // html_entity_decode simulates the browser here
        $_POST = [
            'decoded' => html_entity_decode($specialchars(null, $expected)),
            'encoded' => html_entity_decode($specialchars(null, $expectedEncoded)),
        ];

        Config::set('allowedTags', '');
        Config::set('allowedAttributes', '');

        $this->assertSame($expected, Input::post('decoded', true));
        $this->assertSame($expectedEncoded, Input::post('encoded', false));

        $this->expectDeprecation('%sstripTags() without setting allowed tags and allowed attributes has been deprecated%s');
        $this->assertSame($expected, Input::postHtml('decoded', true));
        $this->assertSame($expectedEncoded, Input::postHtml('encoded', false));
    }

    /**
     * @group legacy
     */
    public function testEncodesInsertTags(): void
    {
        $source = '{{ foo }}';
        $encoded = '&#123;&#123; foo &#125;&#125;';

        $_GET = $_POST = $_COOKIE = [
            'key' => $source,
            $source => 'value',
        ];

        Input::initialize();

        // Insert tags do not get encoded in keys
        $this->assertSame($source, array_keys($_GET)[1]);
        $this->assertSame($source, array_keys($_POST)[1]);
        $this->assertSame($source, array_keys($_COOKIE)[1]);

        $this->assertSame($encoded, Input::get('key', true));
        $this->assertSame($encoded, Input::post('key', true));
        $this->assertSame($encoded, Input::postHtml('key', true));
        $this->assertSame($encoded, Input::cookie('key', true));

        $this->assertSame($encoded, Input::get('key', false));
        $this->assertSame($encoded, Input::post('key', false));
        $this->assertSame($encoded, Input::cookie('key', false));

        $this->assertSame($encoded, Input::postRaw('key'));
        $this->assertSame($source, Input::postUnsafeRaw('key'));

        $this->expectDeprecation('%spostHtml() with $blnDecodeEntities set to false has been deprecated%s');
        $this->assertSame($encoded, Input::postHtml('key', false));
    }

    public function encodeInputProvider(): \Generator
    {
        yield [
            'foo',
            'foo',
        ];

        yield [
            '<span>',
            '&#60;span>',
            '&#60;span&#62;',
        ];

        yield [
            '<script>',
            '&#60;script>',
            '&#60;script&#62;',
        ];

        yield [
            '&',
            '&',
        ];

        yield [
            '[&amp;],&amp;,[&lt;],&lt;,[&gt;],&gt;,[&nbsp;],&nbsp;,[&shy;],&shy;',
            '[&amp;],&amp;,[&lt;],&lt;,[&gt;],&gt;,[&nbsp;],&nbsp;,[&shy;],&shy;',
        ];

        yield [
            '[<]',
            '[&#60;]',
        ];

        yield [
            '&ouml;',
            '&ouml;',
        ];

        yield [
            '&#246;',
            '&#246;',
            '&&#35;246;',
        ];

        yield [
            '&#xF6;',
            '&#xF6;',
            '&&#35;xF6;',
        ];

        yield [
            '&quot;',
            '&quot;',
        ];

        yield [
            '&#0;',
            '&#0;',
            '&&#35;0;',
        ];

        yield [
            '&#x0;',
            '&#x0;',
            '&&#35;x0;',
        ];

        yield [
            "\0",
            "\u{FFFD}",
        ];

        yield [
            '\0',
            '&#92;0',
        ];

        yield [
            " \x001",
            " \u{FFFD}1",
        ];

        yield [
            "&##aa \x00\x01\x02\t\n\r ;",
            "&##aa \u{FFFD}\x01\x02\t\n\n ;",
            "&&#35;&#35;aa \u{FFFD}\x01\x02\t\n\n ;",
        ];

        yield [
            "a\rb\nc\r\nd\n\re\r",
            "a\nb\nc\nd\n\ne\n",
        ];

        yield [
            '">>>"<<<"',
            '">>>"&#60;&#60;&#60;"',
            '&#34;&#62;&#62;&#62;&#34;&#60;&#60;&#60;&#34;',
        ];

        yield [
            '<!--',
            '&#60;!--',
            '&#60;!--',
        ];

        yield [
            '&#x26;lt;!--',
            '&#x26;lt;!--',
            '&&#35;x26;lt;!--',
        ];

        yield [
            "I   l i k e   J a v a\tS c r i p t",
            "I   l i k e   J a v a\tS c r i p t",
        ];

        yield [
            "B-Win \n Dow Jones, Apple \n T-Mobile",
            "B-Win \n Dow Jones, Apple \n T-Mobile",
        ];
    }

    /**
     * @dataProvider encodeNoneModeProvider
     *
     * @group legacy
     */
    public function testEncodeNoneMode(string $source, string $expected, string|null $expectedEncoded = null): void
    {
        $expectedEncoded ??= $expected;

        $this->assertSame($expected, Input::encodeInput($source, InputEncodingMode::encodeNone, false));
        $this->assertSame($expectedEncoded, Input::encodeInput($source, InputEncodingMode::encodeNone, true));
        $this->assertSame($expected.$expected, Input::encodeInput($source.$source, InputEncodingMode::encodeNone, false));
        $this->assertSame($expectedEncoded.$expectedEncoded, Input::encodeInput($source.$source, InputEncodingMode::encodeNone, true));

        System::getContainer()->set('request_stack', $stack = new RequestStack());
        $stack->push(new Request([], ['key' => $source]));

        $this->assertSame($expectedEncoded, Input::postRaw('key'));

        $stack->pop();
        $_POST = ['key' => $source];

        $this->expectDeprecation('%sGetting data from $_POST%shas been deprecated%s');

        $this->assertSame($expectedEncoded, Input::postRaw('key'));
    }

    public function encodeNoneModeProvider(): \Generator
    {
        yield ['', ''];
        yield ['foo', 'foo'];
        yield ['\X \0 \X', '\X &#92;0 \X'];
        yield ["a\rb\r\nc\n\rd\ne", "a\nb\nc\n\nd\ne"];
        yield ['{}', '{}'];
        yield ['{{}}', '{{}}', '&#123;&#123;&#125;&#125;'];
        yield ['{{{}}}', '{{{}}}', '&#123;&#123;{&#125;&#125;}'];
        yield ['{{{{}}}}', '{{{{}}}}', '&#123;&#123;&#123;&#123;&#125;&#125;&#125;&#125;'];
        yield ["\0", "\u{FFFD}"];
        yield ["\x80", "\u{FFFD}"];
        yield ["\xFF", "\u{FFFD}"];
        yield ["\xC2\x7F", "\u{FFFD}\x7F"];
        yield ["\xC2\x80", "\xC2\x80"];
        yield ["\xDF\xBF", "\xDF\xBF"];
        yield ["\xE0\xA0\x7F", "\u{FFFD}\x7F"];
        yield ["\xE0\xA0\x80", "\xE0\xA0\x80"];
        yield ["\xEF\xBF\xBF", "\xEF\xBF\xBF"];
        yield ["\xF0\x90\x80\x7F", "\u{FFFD}\x7F"];
        yield ["\xF0\x90\x80\x80", "\xF0\x90\x80\x80"];
        yield ["\xF4\x8F\xBF\xBF", "\xF4\x8F\xBF\xBF"];
        yield ["\xFA\x80\x80\x80\x80", "\u{FFFD}\u{FFFD}\u{FFFD}\u{FFFD}\u{FFFD}"];
        yield ["\xFB\xBF\xBF\xBF\xBF", "\u{FFFD}\u{FFFD}\u{FFFD}\u{FFFD}\u{FFFD}"];
        yield ["\xFD\x80\x80\x80\x80\x80", "\u{FFFD}\u{FFFD}\u{FFFD}\u{FFFD}\u{FFFD}\u{FFFD}"];
        yield ["\xFD\xBF\xBF\xBF\xBF\xBF", "\u{FFFD}\u{FFFD}\u{FFFD}\u{FFFD}\u{FFFD}\u{FFFD}"];

        /** @see https://github.com/php/php-src/issues/8360 */
        if (\PHP_VERSION_ID >= 80106) {
            yield ["\xDF\xC0", "\u{FFFD}\u{FFFD}"];
            yield ["\xEF\xBF\xC0", "\u{FFFD}\u{FFFD}"];
            yield ["\xF4\x8F\xBF\xC0", "\u{FFFD}\u{FFFD}"];
        }
    }

    /**
     * @dataProvider stripTagsProvider
     *
     * @group legacy
     */
    public function testStripTags(string $source, string $expected, string|null $expectedEncoded = null): void
    {
        $expectedEncoded ??= str_replace(['{{', '}}'], ['&#123;&#123;', '&#125;&#125;'], $expected);

        $allowedTags = Config::get('allowedTags');
        $allowedAttributes = Config::get('allowedAttributes');

        $this->assertSame($expected, Input::stripTags($source, $allowedTags, $allowedAttributes));

        System::getContainer()->set('request_stack', $stack = new RequestStack());
        $stack->push(new Request([], ['key' => $source]));

        $this->assertSame($expectedEncoded, Input::postHtml('key', true));

        $stack->pop();
        $_POST = ['key' => $source];

        $this->expectDeprecation('%sGetting data from $_POST%shas been deprecated%s');

        $this->assertSame($expectedEncoded, Input::postHtml('key', true));
    }

    public function stripTagsProvider(): \Generator
    {
        yield 'Encodes tags' => [
            'Text <with> tags',
            'Text &#60;with&#62; tags',
        ];

        yield 'Keeps allowed tags' => [
            'Text <with> <span> tags',
            'Text &#60;with&#62; <span> tags',
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
            '<!-- my comment &#60;script&#62;alert(1)&#60;/script&#62; --> <span>',
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
            '&#60;SCRIPT SRC&#61;http://xss.rocks/xss.js&#62;&#60;/SCRIPT&#62;',
        ];

        yield [
            'javascript:/*--></title></style></textarea></script></xmp><svg/onload=\'+/"/+/onmouseover=1/+/[*/[]/+alert(1)//\'>',
            'javascript:/*--&#62;&#60;/title&#62;</style></textarea>&#60;/script&#62;&#60;/xmp&#62;&#60;svg/onload&#61;&#39;+/&#34;/+/onmouseover&#61;1/+/[*/[]/+alert(1)//&#39;&#62;',
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
            '\<a>xxs link\&#60;/a\&#62;',
        ];

        yield [
            '\<a onmouseover=alert(document.cookie)\>xxs link\</a\>',
            '\<a>xxs link\&#60;/a\&#62;',
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
            '&#60;SCRIPT/SRC&#61;&#34;http://xss.rocks/xss.js&#34;&#62;&#60;/SCRIPT&#62;',
        ];

        yield [
            '<BODY onload!#$%&()*~+-_.,:;?@[/|\]^`=alert("XSS")>',
            '&#60;BODY onload!#$%&()*~+-_.,:;?@[/|\]^`&#61;alert(&#34;XSS&#34;)&#62;',
        ];

        yield [
            '<<SCRIPT>alert("XSS");//\<</SCRIPT>',
            '&#60;&#60;SCRIPT&#62;alert(&#34;XSS&#34;);//\&#60;&#60;/SCRIPT&#62;',
        ];

        yield [
            '<IMG SRC="`(\'XSS\')"`',
            '',
        ];

        yield [
            '</TITLE><SCRIPT>alert("XSS");</SCRIPT>',
            '&#60;/TITLE&#62;&#60;SCRIPT&#62;alert(&#34;XSS&#34;);&#60;/SCRIPT&#62;',
        ];

        yield [
            '<INPUT TYPE="IMAGE" SRC="javascript:alert(\'XSS\');">',
            '<input>',
        ];

        yield [
            '<BODY BACKGROUND="javascript:alert(\'XSS\')">',
            '&#60;BODY BACKGROUND&#61;&#34;javascript:alert(&#39;XSS&#39;)&#34;&#62;',
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
            '&#60;svg/onload&#61;alert(&#39;XSS&#39;)&#62;',
        ];

        yield [
            '<LINK REL="stylesheet" HREF="javascript:alert(\'XSS\');">',
            '&#60;LINK REL&#61;&#34;stylesheet&#34; HREF&#61;&#34;javascript:alert(&#39;XSS&#39;);&#34;&#62;',
        ];
    }

    /**
     * @group legacy
     *
     * @dataProvider stripTagsNoTagsAllowedProvider
     */
    public function testStripTagsNoTagsAllowed(string $source, string $expected): void
    {
        $this->expectDeprecation('%sstripTags() without setting allowed tags and allowed attributes has been deprecated%s');

        $this->assertSame($expected, Input::stripTags($source));
    }

    public function stripTagsNoTagsAllowedProvider(): \Generator
    {
        yield 'Encodes tags' => [
            'Text <with> tags',
            'Text &#60;with> tags',
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

    /**
     * @group legacy
     */
    public function testStripTagsNoAttributesAllowed(): void
    {
        $html = '<dIv class=gets-normalized bar-foo-something = \'keep\'><spAN class=gets-normalized bar-foo-something = \'keep\'>foo</SPan></DiV><notallowed></notallowed>';
        $expected = '<div><span>foo</span></div>&#60;notallowed&#62;&#60;/notallowed&#62;';

        $this->assertSame($expected, Input::stripTags($html, '<div><span>', serialize([['key' => '', 'value' => '']])));
        $this->assertSame($expected, Input::stripTags($html, '<div><span>', serialize([[]])));
        $this->assertSame($expected, Input::stripTags($html, '<div><span>', serialize([])));
        $this->assertSame($expected, Input::stripTags($html, '<div><span>', serialize(null)));

        $this->expectDeprecation('%sstripTags() without setting allowed tags and allowed attributes has been deprecated%s');
        $this->assertSame($expected, Input::stripTags($html, '<div><span>', ''));
    }

    /**
     * @group legacy
     */
    public function testStripTagsScriptAllowed(): void
    {
        $this->expectDeprecation('%sstripTags() without setting allowed tags and allowed attributes has been deprecated%s');

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
     *
     * @dataProvider simpleTokensWithHtmlProvider
     */
    public function testSimpleTokensWithHtml(string $source, array $tokens, string $expected): void
    {
        $simpleTokenParser = new SimpleTokenParser(new ExpressionLanguage());

        System::getContainer()->set('contao.string.simple_token_parser', $simpleTokenParser);

        // Input encode the source
        $_POST = ['html' => $source];
        $html = Input::postHtml('html', true);
        $_POST = [];

        $this->assertSame($expected, $simpleTokenParser->parse($html, $tokens));
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

    /**
     * @group legacy
     */
    public function testPostAndGetKeys(): void
    {
        $data = ['key1' => 'string-key', '123' => 'integer-key'];

        System::getContainer()->set('request_stack', $stack = new RequestStack());
        $stack->push(new Request());

        $this->assertSame([], Input::getKeys());

        $stack->pop();
        $stack->push(new Request($data));
        $_POST = $_GET = $data;

        $this->assertSame(['key1', '123'], Input::getKeys());

        Input::setGet('key2', 'value');
        Input::setPost('key2', 'value');

        $this->assertSame(['key1', '123', 'key2'], Input::getKeys());
        $this->assertSame(['key1', 123, 'key2'], array_keys($_GET));
        $this->assertSame(['key1', 123, 'key2'], array_keys($_POST));

        Input::setGet('key1', null);
        Input::setPost('key1', null);

        $this->assertSame(['123', 'key2'], Input::getKeys());
        $this->assertSame([123, 'key2'], array_keys($_GET));
        $this->assertSame([123, 'key2'], array_keys($_POST));

        // Duplicating the request should keep the setGet information intact
        $stack->push($stack->getCurrentRequest()->duplicate());

        $this->assertSame(['123', 'key2'], Input::getKeys());
        $this->assertSame([123, 'key2'], array_keys($_GET));
        $this->assertSame([123, 'key2'], array_keys($_POST));

        $stack->pop();
        $stack->pop();
        $stack->push(new Request($data, $data, [], [], [], ['REQUEST_METHOD' => 'POST']));

        $this->assertSame(['key1', '123'], Input::getKeys());
        $this->assertTrue(Input::isPost());
        $this->assertSame([123, 'key2'], array_keys($_GET));
        $this->assertSame([123, 'key2'], array_keys($_POST));

        $stack->pop();
        $_POST = $_GET = [];

        $this->expectDeprecation('%sGetting data from $_%shas been deprecated%s');

        $this->assertSame([], Input::getKeys());
        $this->assertFalse(Input::isPost());

        $_POST = $_GET = $data;

        $this->assertSame(['key1', '123'], Input::getKeys());
        $this->assertTrue(Input::isPost());

        Input::setGet('key2', 'value');
        Input::setPost('key2', 'value');

        $this->assertSame(['key1', '123', 'key2'], Input::getKeys());
        $this->assertSame(['key1', 123, 'key2'], array_keys($_GET));
        $this->assertSame(['key1', 123, 'key2'], array_keys($_POST));

        Input::setGet('key1', null);
        Input::setPost('key1', null);

        $this->assertSame(['123', 'key2'], Input::getKeys());
        $this->assertSame([123, 'key2'], array_keys($_GET));
        $this->assertSame([123, 'key2'], array_keys($_POST));

        $stack->push(new Request($data, [], [], [], [], ['REQUEST_METHOD' => 'POST']));

        $this->assertTrue(Input::isPost(), 'isPost() should return true, even if the post data was empty');
    }

    public function testAutoItemAttribute(): void
    {
        System::getContainer()->set('request_stack', $stack = new RequestStack());
        $stack->push(new Request());

        $this->assertSame([], Input::getKeys());

        Input::setGet('auto_item', 'foo');

        $this->assertSame(['auto_item'], Input::getKeys());
        $this->assertSame('foo', Input::get('auto_item'));
        $this->assertSame('foo', $stack->getCurrentRequest()->attributes->get('auto_item'));
        $this->assertSame('foo', $_GET['auto_item']);

        Input::setGet('key', 'value');

        $this->assertSame(['auto_item', 'key'], Input::getKeys());
        $this->assertSame('value', Input::get('key'));
        $this->assertFalse($stack->getCurrentRequest()->attributes->has('key'));
        $this->assertSame('value', $stack->getCurrentRequest()->attributes->get('_contao_input')['setGet']['key']);
        $this->assertSame('value', $_GET['key']);

        Input::setGet('auto_item', null);

        $this->assertSame(['key'], Input::getKeys());
        $this->assertNull(Input::get('auto_item'));
        $this->assertFalse($stack->getCurrentRequest()->attributes->has('auto_item'));
        $this->assertArrayNotHasKey('auto_item', $_GET);
    }

    /**
     * @group legacy
     */
    public function testArrayValuesFromGetAndPost(): void
    {
        $data = ['key' => ['value1', 'value2']];

        System::getContainer()->set('request_stack', $stack = new RequestStack());
        $stack->push(new Request($data, $data, [], $data));

        $this->assertSame(['value1', 'value2'], Input::get('key'));
        $this->assertSame(['value1', 'value2'], Input::post('key'));
        $this->assertSame(['value1', 'value2'], Input::cookie('key'));
    }
}
