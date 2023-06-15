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
use Contao\CoreBundle\Image\Studio\FigureRenderer;
use Contao\CoreBundle\InsertTag\Flag\PhpFunctionFlag;
use Contao\CoreBundle\InsertTag\Flag\StringUtilFlag;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\InsertTag\InsertTagSubscription;
use Contao\CoreBundle\InsertTag\Resolver\IfLanguageInsertTag;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\InsertTags;
use Contao\System;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;

class InsertTagsTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TL_HOOKS']['replaceInsertTags'][] = [self::class, 'replaceInsertTagsHook'];

        $container = $this->getContainerWithContaoConfiguration($this->getTempDir());
        $container->set('contao.security.token_checker', $this->createMock(TokenChecker::class));
        $container->set('monolog.logger.contao.error', $this->createMock(LoggerInterface::class));
        $container->set('fragment.handler', $this->createMock(FragmentHandler::class));
        $container->setParameter('contao.insert_tags.allowed_tags', ['*']);
        $container->get('contao.framework')->setContainer($container);

        System::setContainer($container);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_HOOKS'], $GLOBALS['TL_MIME']);

        InsertTags::reset();

        $this->resetStaticProperties([System::class, Config::class]);

        parent::tearDown();
    }

    public function replaceInsertTagsHook(string $tag): string
    {
        $tagParts = explode('::', $tag, 2);

        if ('infinite-nested' === $tagParts[0]) {
            return '{{infinite-nested::'.((int) $tagParts[1] + 1).'}}';
        }

        if ('infinite-recursion' === $tagParts[0]) {
            return (string) (new InsertTags())->replaceInternal('{{infinite-recursion::'.((int) $tagParts[1] + 1).'}}', false, $this->createMock(InsertTagParser::class));
        }

        if ('infinite-try-catch' === $tagParts[0]) {
            try {
                return (string) (new InsertTags())->replaceInternal('{{infinite-try-catch::'.((int) $tagParts[1] + 1).'}}', false, $this->createMock(InsertTagParser::class));
            } catch (\RuntimeException $exception) {
                $this->assertSame('Maximum insert tag nesting level of 64 reached', $exception->getMessage());

                return '[{]infinite-try-catch::'.((int) $tagParts[1] + 1).'[}]';
            }
        }

        if ('infinite-retry' === $tagParts[0]) {
            try {
                return (string) (new InsertTags())->replaceInternal('{{infinite-retry::'.((int) $tagParts[1] + 1).'}}', false, $this->createMock(InsertTagParser::class));
            } catch (\RuntimeException $exception) {
                $this->assertSame('Maximum insert tag nesting level of 64 reached', $exception->getMessage());

                if ((int) $tagParts[1] >= 100) {
                    return (string) (new InsertTags())->replaceInternal('{{infinite-retry::'.((int) $tagParts[1] + 1).'}}', false, $this->createMock(InsertTagParser::class));
                }

                throw $exception;
            }
        }

        return str_replace(['[', ']'], ['{', '}'], $tagParts[1] ?? '');
    }

    /**
     * @dataProvider insertTagsProvider
     */
    public function testInsertTags(string $source, string $expected): void
    {
        InsertTags::reset();

        $insertTagParser = System::getContainer()->get('contao.insert_tag.parser');
        $insertTagParser->addFlagCallback('standardize', new StringUtilFlag(), 'standardize');
        $insertTagParser->addFlagCallback('ampersand', new StringUtilFlag(), 'ampersand');
        $insertTagParser->addFlagCallback('specialchars', new StringUtilFlag(), 'specialchars');
        $insertTagParser->addFlagCallback('utf8_strtolower', new StringUtilFlag(), 'utf8Strtolower');
        $insertTagParser->addFlagCallback('utf8_strtoupper', new StringUtilFlag(), 'utf8Strtoupper');
        $insertTagParser->addFlagCallback('utf8_romanize', new StringUtilFlag(), 'utf8Romanize');
        $insertTagParser->addFlagCallback('nl2br', new StringUtilFlag(), 'nl2Br');
        $insertTagParser->addFlagCallback('addslashes', new PhpFunctionFlag(), '__invoke');
        $insertTagParser->addFlagCallback('strtolower', new PhpFunctionFlag(), '__invoke');
        $insertTagParser->addFlagCallback('strtoupper', new PhpFunctionFlag(), '__invoke');
        $insertTagParser->addFlagCallback('ucfirst', new PhpFunctionFlag(), '__invoke');
        $insertTagParser->addFlagCallback('lcfirst', new PhpFunctionFlag(), '__invoke');
        $insertTagParser->addFlagCallback('ucwords', new PhpFunctionFlag(), '__invoke');
        $insertTagParser->addFlagCallback('trim', new PhpFunctionFlag(), '__invoke');
        $insertTagParser->addFlagCallback('rtrim', new PhpFunctionFlag(), '__invoke');
        $insertTagParser->addFlagCallback('ltrim', new PhpFunctionFlag(), '__invoke');
        $insertTagParser->addFlagCallback('urlencode', new PhpFunctionFlag(), '__invoke');
        $insertTagParser->addFlagCallback('rawurlencode', new PhpFunctionFlag(), '__invoke');

        $output = $insertTagParser->replaceInline($source);

        $this->assertSame($expected, $output);

        $output = (string) $insertTagParser->replaceChunked($source);

        $this->assertSame($expected, $output);
    }

    public function insertTagsProvider(): \Generator
    {
        yield 'Simple' => [
            'foo{{plain::bar}}baz',
            'foobarbaz',
        ];

        yield 'Nested' => [
            'foo{{plain::1{{plain::2}}3}}baz',
            'foo123baz',
        ];

        yield 'Nested broken after' => [
            'foo{{plain::1{{plain::2}}3}}broken}}baz',
            'foo123broken}}baz',
        ];

        yield 'Nested broken before' => [
            'foo{{broken{{plain::1{{plain::2}}3}}baz',
            'foo{{broken123baz',
        ];

        yield 'Nested invalid 1' => [
            'foo{{plain::1{{plain::2{3}}4}}baz',
            'foo{{plain::1{{plain::2{3}}4}}baz',
        ];

        yield 'Nested invalid 2' => [
            'foo{{plain::1{{plain::2}3}}4}}baz',
            'foo{{plain::1{{plain::2}3}}4}}baz',
        ];

        yield 'Nested invalid 3' => [
            'foo{{plain::1{{plain::2{}3}}4}}baz',
            'foo{{plain::1{{plain::2{}3}}4}}baz',
        ];

        yield 'Recursive' => [
            'foo{{ins::1[[plain::2]]3}}baz',
            'foo123baz',
        ];

        yield 'Recursive forward not supported' => [
            'foo{{ins::1[[plain::2}}3}}baz',
            'foo1{{plain::23}}baz',
        ];

        yield 'Recursive backward not supported' => [
            'foo{{ins::1{{plain::2]]3}}baz',
            'foo{{ins::12}}3baz',
        ];

        yield 'Flag addslashes' => [
            '{{plain::f\'oo|addslashes}}',
            'f\\\'oo',
        ];

        yield 'Flag standardize' => [
            '{{plain::foo & bar|standardize}}',
            'foo-bar',
        ];

        yield 'Flag ampersand' => [
            '{{plain::foo & bar|ampersand}}',
            'foo &amp; bar',
        ];

        yield 'Flag specialchars' => [
            '{{plain::foo & bar < baz|specialchars}}',
            'foo &amp; bar &lt; baz',
        ];

        yield 'Flag strtolower' => [
            '{{plain::FOO|strtolower}}',
            'foo',
        ];

        yield 'Flag utf8_strtolower' => [
            '{{plain::FÖO|utf8_strtolower}}',
            'föo',
        ];

        yield 'Flag strtoupper' => [
            '{{plain::foo|strtoupper}}',
            'FOO',
        ];

        yield 'Flag utf8_strtoupper' => [
            '{{plain::föo|utf8_strtoupper}}',
            'FÖO',
        ];

        yield 'Flag ucfirst' => [
            '{{plain::foo|ucfirst}}',
            'Foo',
        ];

        yield 'Flag lcfirst' => [
            '{{plain::FOO|lcfirst}}',
            'fOO',
        ];

        yield 'Flag ucwords' => [
            '{{plain::foo bar|ucwords}}',
            'Foo Bar',
        ];

        yield 'Flag trim' => [
            '{{plain:: foo |trim}}',
            'foo',
        ];

        yield 'Flag rtrim' => [
            '{{plain:: foo |rtrim}}',
            ' foo',
        ];

        yield 'Flag ltrim' => [
            '{{plain:: foo |ltrim}}',
            'foo ',
        ];

        yield 'Flag utf8_romanize' => [
            '{{plain::föo|utf8_romanize}}',
            'foo',
        ];

        yield 'Flag urlencode' => [
            '{{plain::foo & bar|urlencode}}',
            'foo+%26+bar',
        ];

        yield 'Flag rawurlencode' => [
            '{{plain::foo & bar|rawurlencode}}',
            'foo%20%26%20bar',
        ];
    }

    /**
     * @dataProvider provideFigureInsertTags
     *
     * @group legacy
     */
    public function testFigureInsertTag(string $input, array $expectedArguments): void
    {
        $usedArguments = [];

        $figureRenderer = $this->createMock(FigureRenderer::class);
        $figureRenderer
            ->expects($this->once())
            ->method('render')
            ->willReturnCallback(
                static function (...$arguments) use (&$usedArguments) {
                    $usedArguments = $arguments;

                    return '<figure>foo</figure>';
                }
            )
        ;

        $this->setContainerWithContaoConfiguration(['contao.image.studio.figure_renderer' => $figureRenderer]);

        $insertTagParser = new InsertTagParser($this->mockContaoFramework(), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class));
        $output = $insertTagParser->replaceInline($input);

        $this->assertSame('<figure>foo</figure>', $output);
        $this->assertSame($expectedArguments, $usedArguments);
    }

    public function provideFigureInsertTags(): \Generator
    {
        $defaultTemplate = '@ContaoCore/Image/Studio/figure.html.twig';

        yield 'without any configuration' => [
            '{{figure::123}}',
            ['123', null, [], $defaultTemplate],
        ];

        yield 'with size' => [
            '{{figure::files/cat.jpg?size=_my_size}}',
            ['files/cat.jpg', '_my_size', [], $defaultTemplate],
        ];

        yield 'with custom template' => [
            '{{figure::files/cat.jpg?template=foo.html.twig}}',
            ['files/cat.jpg', null, [], 'foo.html.twig'],
        ];

        yield 'with nested options' => [
            '{{figure::1000?size=5&metadata[title]=foo%20bar&options[attr][class]=baz}}',
            [
                '1000',
                5,
                [
                    'metadata' => ['title' => 'foo bar'],
                    'options' => ['attr' => ['class' => 'baz']],
                ],
                $defaultTemplate,
            ],
        ];

        yield 'complex configuration' => [
            '{{figure::files/foo.jpg?size=_my_size&metadata[alt]=alt&template=my_template.html.twig&enableLightbox=1}}',
            [
                'files/foo.jpg',
                '_my_size',
                [
                    'metadata' => ['alt' => 'alt'],
                    'enableLightbox' => 1,
                ],
                'my_template.html.twig',
            ],
        ];

        yield 'wrapped basic entities' => [
            '{{figure::123?size[]=800&amp;size[]=600&amp;metadata[alt]=alt&amp;enableLightbox=1}}',
            [
                '123',
                [800, 600],
                [
                    'metadata' => ['alt' => 'alt'],
                    'enableLightbox' => 1,
                ],
                $defaultTemplate,
            ],
        ];

        yield 'HTML' => [
            '{{figure::123?metadata[caption]=<script>alert(1)</script>}}',
            [
                '123',
                null,
                [
                    'metadata' => ['caption' => '&lt;script&gt;alert(1)&lt;/script&gt;'],
                ],
                $defaultTemplate,
            ],
        ];

        yield 'urlencoded HTML' => [
            '{{figure::123?metadata[caption]=%3Cscript%3Ealert%281%29%3C%2Fscript%3E}}',
            [
                '123',
                null,
                [
                    'metadata' => ['caption' => '&lt;script&gt;alert(1)&lt;/script&gt;'],
                ],
                $defaultTemplate,
            ],
        ];
    }

    /**
     * @dataProvider provideInvalidFigureInsertTags
     *
     * @group legacy
     */
    public function testFigureInsertTagReturnsEmptyStringIfInvalid(string $input, bool $invalidConfiguration): void
    {
        $figureRenderer = $this->createMock(FigureRenderer::class);
        $figureRenderer
            ->expects($invalidConfiguration ? $this->once() : $this->never())
            ->method('render')
            ->willThrowException(new \InvalidArgumentException('bad call'))
        ;

        $this->setContainerWithContaoConfiguration(['contao.image.studio.figure_renderer' => $figureRenderer]);

        $insertTagParser = new InsertTagParser($this->mockContaoFramework(), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class));
        $output = $insertTagParser->replaceInline($input);

        $this->assertSame('', $output);
    }

    public function provideInvalidFigureInsertTags(): \Generator
    {
        yield 'missing resource' => [
            '{{figure}}', false,
        ];

        yield 'too many arguments' => [
            '{{figure::5?size=1::other}}', false,
        ];

        yield 'invalid configuration' => [
            '{{figure::1?foo=bar}}', true,
        ];
    }

    /**
     * @dataProvider allowedInsertTagsProvider
     *
     * @group legacy
     */
    public function testAllowedInsertTags(string $source, string $expected, array $allowedTags): void
    {
        System::getContainer()->setParameter('contao.insert_tags.allowed_tags', $allowedTags);

        InsertTags::reset();

        $output = (string) (new InsertTags())->replaceInternal($source, false, $this->createMock(InsertTagParser::class));

        $this->assertSame($expected, $output);
    }

    public function allowedInsertTagsProvider(): \Generator
    {
        yield 'All allowed' => [
            'foo{{plain1::1}}bar{{plain2::2}}baz',
            'foo1bar2baz',
            ['*'],
        ];

        yield 'First allowed' => [
            'foo{{plain1::1}}bar{{plain2::2}}baz',
            'foo1bar{{plain2::2}}baz',
            ['plain1'],
        ];

        yield 'Second allowed' => [
            'foo{{plain1::1}}bar{{plain2::2}}baz',
            'foo{{plain1::1}}bar2baz',
            ['plain2'],
        ];

        yield 'Both allowed' => [
            'foo{{plain1::1}}bar{{plain2::2}}baz',
            'foo1bar2baz',
            ['plain1', 'plain2'],
        ];

        yield 'None allowed' => [
            'foo{{plain1::1}}bar{{plain2::2}}baz',
            'foo{{plain1::1}}bar{{plain2::2}}baz',
            [],
        ];

        yield 'Wildcard start' => [
            'foo{{plain1::1}}bar{{plain2::2}}baz',
            'foo{{plain1::1}}bar2baz',
            ['*lain2'],
        ];

        yield 'Wildcard end' => [
            'foo{{plain1::1}}bar{{plain2::2}}baz{{plain}}',
            'foo1bar2baz{{plain}}',
            ['plain*'],
        ];

        yield 'Wildcard center' => [
            'foo{{plain::1}}bar{{plain::2}}baz{{plin}}',
            'foo1bar2baz{{plin}}',
            ['pl*in'],
        ];
    }

    /**
     * @dataProvider encodeHtmlAttributesProvider
     *
     * @group legacy
     */
    public function testEncodeHtmlAttributes(string $source, string $expected): void
    {
        $reflectionClass = new \ReflectionClass(InsertTags::class);

        /** @var InsertTags $insertTags */
        $insertTags = $reflectionClass->newInstanceWithoutConstructor();
        $insertTagParser = new InsertTagParser($this->mockContaoFramework(), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class), $insertTags);
        $insertTagParser->addFlagCallback('attr', new StringUtilFlag(), 'attr');
        $insertTagParser->addFlagCallback('urlattr', new StringUtilFlag(), 'urlattr');
        $output = $insertTagParser->replaceInline($source);

        $this->assertSame($expected, $output);
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
     *
     * @group legacy
     */
    public function testRemovesLanguageInsertTags(string $source, string $expected, string $pageLanguage = 'en'): void
    {
        $request = $this->createMock(Request::class);
        $request
            ->method('getLocale')
            ->willReturn($pageLanguage)
        ;

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

        $reflectionClass = new \ReflectionClass(InsertTags::class);

        /** @var InsertTags $insertTags */
        $insertTags = $reflectionClass->newInstanceWithoutConstructor();
        $insertTagParser = new InsertTagParser($this->mockContaoFramework(), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class), $insertTags);

        System::getContainer()->set('contao.insert_tag.parser', $insertTagParser);

        $insertTagParser->addBlockSubscription(new InsertTagSubscription(
            new IfLanguageInsertTag($requestStack),
            'replaceInsertTag',
            'iflng',
            'iflng',
            true,
            false,
        ));

        $insertTagParser->addBlockSubscription(new InsertTagSubscription(
            new IfLanguageInsertTag($requestStack),
            'replaceInsertTag',
            'ifnlng',
            'ifnlng',
            true,
            false,
        ));

        $this->assertSame($expected, $insertTagParser->replaceInline($source));
        $this->assertSame($expected.$expected, $insertTagParser->replaceInline($source.$source));

        $this->assertSame($expected, $insertTagParser->replace($source));
        $this->assertSame($expected.$expected, $insertTagParser->replace($source.$source));

        // Test case insensitivity
        $source = str_replace('lng', 'LnG', $source);

        if (str_contains($source, 'LnG')) {
            $this->expectDeprecation('%sInsert tags with uppercase letters%s');
        }

        $this->assertSame($expected, $insertTagParser->replaceInline($source));
        $this->assertSame($expected.$expected, $insertTagParser->replaceInline($source.$source));

        $this->assertSame($expected, $insertTagParser->replace($source));
        $this->assertSame($expected.$expected, $insertTagParser->replace($source.$source));

        $source = '<a href="'.htmlspecialchars($source).'" title="'.htmlspecialchars($source).'">';
        $expected = '<a href="'.htmlspecialchars($expected).'" title="'.htmlspecialchars($expected).'">';

        $this->assertSame($expected, $insertTagParser->replaceInline($source));
        $this->assertSame($expected.$expected, $insertTagParser->replaceInline($source.$source));

        $this->assertSame($expected, $insertTagParser->replace($source));
        $this->assertSame($expected.$expected, $insertTagParser->replace($source.$source));
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

        yield [
            '{{iflng::de}}DE{{iflng::de_DE}}DE_DE{{iflng::de*}}DE*{{iflng::de_*}}DE_*{{iflng::deu-DE}}DEU-DE{{iflng::dex-DE}}DEX-DE{{iflng}}',
            'DE_DEDE*DE_*DEU-DE',
            'deu-DE',
        ];
    }

    public function testInfiniteNestedInsertTag(): void
    {
        InsertTags::reset();

        $insertTagParser = new InsertTagParser($this->mockContaoFramework(), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class));

        System::getContainer()->set('contao.insert_tag.parser', $insertTagParser);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Maximum insert tag nesting level of 64 reached');

        $insertTagParser->replaceInline('{{infinite-nested::1}}');
    }

    public function testInfiniteRecursionInsertTag(): void
    {
        InsertTags::reset();

        $insertTagParser = new InsertTagParser($this->mockContaoFramework(), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Maximum insert tag nesting level of 64 reached');

        $insertTagParser->replaceInline('{{infinite-recursion::1}}');
    }

    public function testInfiniteRecursionWithCatchInsertTag(): void
    {
        InsertTags::reset();

        $insertTagParser = new InsertTagParser($this->mockContaoFramework(), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class));
        $output = $insertTagParser->replaceInline('{{infinite-try-catch::1}}');

        $this->assertSame('[{]infinite-try-catch::65[}]', $output);
    }

    public function testInfiniteRecursionWithCatchAndRetryInsertTag(): void
    {
        InsertTags::reset();

        $insertTagParser = new InsertTagParser($this->mockContaoFramework(), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Maximum insert tag nesting level of 64 reached');

        $insertTagParser->replaceInline('{{infinite-retry::1}}');
    }

    public function testPcreBacktrackLimit(): void
    {
        InsertTags::reset();

        $insertTagParser = new InsertTagParser($this->mockContaoFramework(), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class));
        $insertTag = '{{'.str_repeat('a', (int) \ini_get('pcre.backtrack_limit') * 2).'::replaced}}';

        $this->assertSame(
            'replaced',
            $insertTagParser->replaceInline($insertTag),
        );
    }

    public function testPcreErrorIsConvertedToException(): void
    {
        InsertTags::reset();

        $resourceLocator = $this->createMock(FileLocatorInterface::class);
        $resourceLocator
            ->method('locate')
            ->willReturn([])
        ;

        System::getContainer()->set('contao.resource_locator', $resourceLocator);

        $insertTagParser = new InsertTagParser($this->mockContaoFramework(), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class));
        $insertTag = '{{'.str_repeat('a', 1024).'::replaced}}';

        $backtrackLimit = \ini_get('pcre.backtrack_limit');
        ini_set('pcre.backtrack_limit', '0');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('PCRE: Backtrack limit exhausted');

        try {
            $insertTagParser->replaceInline($insertTag);
        } finally {
            ini_set('pcre.backtrack_limit', $backtrackLimit);
        }
    }

    private function setContainerWithContaoConfiguration(array $configuration = []): void
    {
        $container = $this->getContainerWithContaoConfiguration();
        $container->setParameter('contao.insert_tags.allowed_tags', ['*']);
        $container->set('contao.security.token_checker', $this->createMock(TokenChecker::class));

        foreach ($configuration as $name => $value) {
            $container->set($name, $value);
        }

        System::setContainer($container);
    }
}
