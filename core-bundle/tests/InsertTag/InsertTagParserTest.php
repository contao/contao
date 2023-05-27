<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\InsertTag;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\ChunkedText;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\InsertTag\ParsedInsertTag;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\InsertTags;
use Contao\System;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;

class InsertTagParserTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $container = $this->getContainerWithContaoConfiguration($this->getTempDir());
        $container->set('contao.security.token_checker', $this->createMock(TokenChecker::class));
        $container->set('monolog.logger.contao.error', $this->createMock(LoggerInterface::class));
        $container->set('request_stack', $requestStack);

        System::setContainer($container);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_MIME'], $GLOBALS['TL_HOOKS']);

        $this->resetStaticProperties([InsertTags::class, System::class, Config::class]);

        parent::tearDown();
    }

    public function testReplace(): void
    {
        $parser = new InsertTagParser($this->createMock(ContaoFramework::class), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class));

        $this->assertSame('<br>', $parser->replace('{{br}}'));
        // TODO: $this->assertSame([[ChunkedText::TYPE_RAW, '<br>']], iterator_to_array($parser->replaceChunked('{{br}}')));
    }

    public function testReplaceUnknown(): void
    {
        $parser = new InsertTagParser($this->createMock(ContaoFramework::class), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class));

        $this->assertSame('{{doesnotexist}}', $parser->replace('{{doesnotexist}}'));
        $this->assertSame([[ChunkedText::TYPE_TEXT, '{{doesnotexist}}']], iterator_to_array($parser->replaceChunked('{{doesnotexist}}')));
    }

    /**
     * @group legacy
     */
    public function testRender(): never
    {
        // TODO:
        $this->markTestSkipped();

        /*
        $parser = new InsertTagParser($this->createMock(ContaoFramework::class), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class));

        $this->assertSame('<br>', $parser->render('br'));
        $this->assertSame('', $parser->render('env::empty-insert-tag'));

        $this->expectExceptionMessage('Rendering a single insert tag has to return a single raw chunk');
        $this->expectDeprecation('%sInvalid insert tag name%s');

        $parser->renderTag('br}}foo{{br');
        */
    }

    public function testParseTag(): void
    {
        $insertTagParser = new InsertTagParser($this->createMock(ContaoFramework::class), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class));

        $insertTag = $insertTagParser->parseTag('insert_tag::first::second::foo=bar::baz[]=1::baz[]=1.23|flag1|flag2');

        $this->assertInstanceOf(ResolvedInsertTag::class, $insertTag);
        $this->assertSame('insert_tag', $insertTag->getName());
        $this->assertSame('first', $insertTag->getParameters()->get(0));
        $this->assertSame('second', $insertTag->getParameters()->get(1));
        $this->assertSame('bar', $insertTag->getParameters()->get('foo'));
        $this->assertSame(1, $insertTag->getParameters()->get('baz[]'));
        $this->assertSame(1, $insertTag->getParameters()->all('baz[]')[0]);
        $this->assertSame(1.23, $insertTag->getParameters()->all('baz[]')[1]);
        $this->assertSame('flag1', $insertTag->getFlags()[0]->getName());
        $this->assertSame('flag2', $insertTag->getFlags()[1]->getName());

        $insertTag = $insertTagParser->parseTag('insert_tag::param::foo={{bar::param|flag1}}|flag2');

        $this->assertInstanceOf(ParsedInsertTag::class, $insertTag);
        $this->assertSame('insert_tag', $insertTag->getName());
        $this->assertSame('param', $insertTag->getParameters()->get(0)->get(0));
        $this->assertSame('bar', $insertTag->getParameters()->get(1)->get(1)->getName());
        $this->assertSame('bar', $insertTag->getParameters()->get('foo')->get(0)->getName());
        $this->assertSame('flag1', $insertTag->getParameters()->get('foo')->get(0)->getFlags()[0]->getName());
        $this->assertSame('flag2', $insertTag->getFlags()[0]->getName());
    }

    public function testParse(): void
    {
        // $insertTagParser = new InsertTagParser($this->createMock(ContaoFramework::class), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class));
        // $sequence = $insertTagParser->parse('foo{{insert_tag::a{{first}}b::a{{second}}b?foo=bar&baz[]={{value|valflag}}&baz[]=1.23|flag1|flag2}}bar{{baz}}');

        // var_dump($sequence);
    }

    /**
     * @group legacy
     */
    public function testRenderMixedCase(): never
    {
        // TODO:
        $this->markTestSkipped();

        /*
        $parser = new InsertTagParser($this->createMock(ContaoFramework::class), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class));

        $this->expectDeprecation('%sInsert tags with uppercase letters%s');

        $this->assertSame('<br>', $parser->render('bR'));
        */
    }

    public function testReplaceFragment(): never
    {
        // TODO:
        $this->markTestSkipped();

        /*
        $handler = $this->createMock(FragmentHandler::class);
        $handler
            ->method('render')
            ->willReturnCallback(static fn (ControllerReference $reference) => '<esi '.$reference->attributes['insertTag'].'>')
        ;

        System::getContainer()->set('fragment.handler', $handler);

        $parser = new InsertTagParser($this->createMock(ContaoFramework::class), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class));

        $this->assertSame('<esi {{fragment::{{br}}}}>', $parser->replace('{{fragment::{{br}}}}'));
        $this->assertSame([[ChunkedText::TYPE_RAW, '<esi {{fragment::{{br}}}}>']], iterator_to_array($parser->replaceChunked('{{fragment::{{br}}}}')));

        $this->assertSame('<br>', $parser->replaceInline('{{fragment::{{br}}}}'));
        $this->assertSame([[ChunkedText::TYPE_RAW, '<br>']], iterator_to_array($parser->replaceInlineChunked('{{fragment::{{br}}}}')));
        */
    }

    /**
     * @dataProvider getLegacyReplaceInsertTagsHooks
     */
    public function testLegacyReplaceInsertTagsHook(string $source, string $expected, \Closure $hook): void
    {
        $GLOBALS['TL_HOOKS']['replaceInsertTags'] = [
            [
                new class($hook) {
                    public function __construct(private \Closure $hook)
                    {
                    }

                    /**
                     * @phpstan-ignore-next-line
                     */
                    public function __invoke(&$a, &$b, $c, &$d, &$e, $f, &$g, &$h)
                    {
                        return ($this->hook)($a, $b, $c, $d, $e, $f, $g, $h);
                    }

                    public function __toString(): string
                    {
                        return self::class;
                    }
                },
                '__invoke',
            ],
        ];

        $parser = new InsertTagParser($this->createMock(ContaoFramework::class), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class));
        $this->assertSame($expected, $parser->replaceInline($source));
    }

    public function getLegacyReplaceInsertTagsHooks(): \Generator
    {
        yield [
            'foo {{tag}} bar',
            'foo baz bar',
            function ($tag) {
                $this->assertSame('tag', $tag);

                return 'baz';
            },
        ];

        yield [
            'prefix{{first::foo|bar|baz}}middle{{second::a:b::{{br|inner}}|outer}}middle{{br}}suffix',
            'prefix[first::foo]middle[second::a:b::<br>]middle<br>suffix',
            function ($tag, $useCache, $cachedValue, $flags, $tags, $cache, $_rit, $_cnt) {
                $this->assertFalse($useCache);
                $this->assertEmpty($cache);
                $this->assertSame('prefix', $tags[0]);
                $this->assertSame('first::foo|bar|baz', $tags[1]);
                $this->assertSame('middle', $tags[2]);
                $this->assertSame('middle', $tags[4]);
                $this->assertSame('br', $tags[5]); // Might change?
                $this->assertSame('suffix', $tags[6]);
                $this->assertSame(7, $_cnt);

                if ('first::foo' === $tag) {
                    $this->assertSame(0, $_rit);
                    $this->assertSame(['bar', 'baz'], $flags);
                    $this->assertSame('second::a:b::{{br|inner}}|outer', $tags[3]);
                } else {
                    $this->assertSame('second::a:b::<br>', $tag);
                    $this->assertSame(2, $_rit);
                    $this->assertSame(['outer'], $flags);
                    $this->assertSame('second::a:b::<br>|outer', $tags[3]);
                }

                return "[$tag]";
            },
        ];

        yield [
            'a{{conditional}}b{{br}}c{{conditional_end}}d',
            'ad',
            function ($tag, $useCache, $cachedValue, $flags, $tags, $cache, &$_rit, $_cnt) {
                $this->assertSame('conditional', $tag);
                $this->assertSame(0, $_rit);
                $this->assertSame([], $flags);
                $_rit = array_search('conditional_end', $tags, true) - 1;
                $this->assertSame('conditional_end', $tags[$_rit + 1]);

                return '';
            },
        ];
    }

    /**
     * @dataProvider getLegacyInsertTagFlagsHooks
     */
    public function testLegacyInsertTagFlagsHook(string $source, string $expected, \Closure $hook): void
    {
        $GLOBALS['TL_HOOKS']['insertTagFlags'] = [
            [
                new class($hook) {
                    public function __construct(private \Closure $hook)
                    {
                    }

                    /**
                     * @phpstan-ignore-next-line
                     */
                    public function __invoke(&$a, &$b, &$c, &$d, &$e, &$f, $g, &$h, &$i)
                    {
                        return ($this->hook)($a, $b, $c, $d, $e, $f, $g, $h, $i);
                    }

                    public function __toString(): string
                    {
                        return self::class;
                    }
                },
                '__invoke',
            ],
        ];

        $parser = new InsertTagParser($this->createMock(ContaoFramework::class), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class));
        $this->assertSame($expected, $parser->replaceInline($source));
    }

    public function getLegacyInsertTagFlagsHooks(): \Generator
    {
        yield [
            'foo{{br|flag}}bar',
            'foo<BR>bar',
            function ($flag, $tag, $cachedValue, $flags, $useCache, $tags, $cache, $_rit, $_cnt) {
                $this->assertSame('flag', $flag);
                $this->assertSame('br', $tag);
                $this->assertSame('<br>', $cachedValue);
                $this->assertSame(['flag'], $flags);
                $this->assertFalse($useCache);
                $this->assertSame(['foo', 'br|flag', 'bar'], $tags);
                $this->assertSame([], $cache);
                $this->assertSame(0, $_rit);
                $this->assertSame(3, $_cnt);

                return '<BR>';
            },
        ];

        yield [
            'foo{{br|flag}}bar{{br|foo|bar}}baz',
            'foo<BR>bar<OE>baz',
            function ($flag, $tag, $cachedValue, $flags, $useCache, $tags, $cache, $_rit, $_cnt) {
                $this->assertSame('br', $tag);
                $this->assertFalse($useCache);
                $this->assertSame(['foo', 'br|flag', 'bar', 'br|foo|bar', 'baz'], $tags);
                $this->assertSame([], $cache);
                $this->assertSame(5, $_cnt);

                if ('flag' === $flag) {
                    $this->assertSame(['flag'], $flags);
                    $this->assertSame(0, $_rit);
                    $this->assertSame('<br>', $cachedValue);

                    return strtoupper($cachedValue);
                }

                if ('foo' === $flag) {
                    $this->assertSame(['foo', 'bar'], $flags);
                    $this->assertSame(2, $_rit);
                    $this->assertSame('<br>', $cachedValue);

                    return strtoupper($cachedValue);
                }

                $this->assertSame(['foo', 'bar'], $flags);
                $this->assertSame(2, $_rit);
                $this->assertSame('<BR>', $cachedValue);
                $this->assertSame('bar', $flag);

                return str_rot13($cachedValue);
            },
        ];
    }
}
