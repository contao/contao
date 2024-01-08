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
use Contao\CoreBundle\InsertTag\InsertTagSubscription;
use Contao\CoreBundle\InsertTag\ParsedInsertTag;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\Resolver\FragmentInsertTag;
use Contao\CoreBundle\InsertTag\Resolver\IfLanguageInsertTag;
use Contao\CoreBundle\InsertTag\Resolver\LegacyInsertTag;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\InsertTags;
use Contao\System;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
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
        $parser->addSubscription(new InsertTagSubscription(new LegacyInsertTag(System::getContainer()), '__invoke', 'br', null, true, false));

        $this->assertSame('<br>', $parser->replace('{{br}}'));
        $this->assertSame([[ChunkedText::TYPE_RAW, '<br>']], iterator_to_array($parser->replaceChunked('{{br}}')));
    }

    public function testReplaceUnknown(): void
    {
        $parser = new InsertTagParser($this->createMock(ContaoFramework::class), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class));
        System::getContainer()->set('contao.insert_tag.parser', $parser);

        $this->assertSame('{{doesnotexist}}', $parser->replace('{{doesnotexist}}'));
        $this->assertSame([[ChunkedText::TYPE_TEXT, '{{doesnotexist}}']], iterator_to_array($parser->replaceChunked('{{doesnotexist}}')));
    }

    /**
     * @group legacy
     */
    public function testRender(): void
    {
        $parser = new InsertTagParser($this->createMock(ContaoFramework::class), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class));
        $parser->addSubscription(new InsertTagSubscription(new LegacyInsertTag(System::getContainer()), '__invoke', 'br', null, true, false));
        $parser->addSubscription(new InsertTagSubscription(new LegacyInsertTag(System::getContainer()), '__invoke', 'env', null, true, false));
        System::getContainer()->set('contao.insert_tag.parser', $parser);

        $this->assertSame('<br>', $parser->render('br'));
        $this->assertSame('', $parser->render('env::empty-insert-tag'));
        $this->assertSame('{{does_not_exist}}', $parser->render('does_not_exist'));

        $this->expectExceptionMessage('Rendering a single insert tag has to return a single chunk');
        $this->expectDeprecation('%sInvalid insert tag name%s');

        $parser->renderTag('br}}foo{{br');
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
        $this->assertSame('1', $insertTag->getParameters()->get('baz[]'));
        $this->assertSame(1, $insertTag->getParameters()->getScalar('baz[]'));
        $this->assertSame('1', $insertTag->getParameters()->all('baz[]')[0]);
        $this->assertSame(1, $insertTag->getParameters()->allScalar('baz[]')[0]);
        $this->assertSame('1.23', $insertTag->getParameters()->all('baz[]')[1]);
        $this->assertSame(1.23, $insertTag->getParameters()->allScalar('baz[]')[1]);
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
        $insertTagParser = new InsertTagParser($this->createMock(ContaoFramework::class), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class));
        $sequence = $insertTagParser->parse('foo{{insert_tag::a{{first}}b::a{{second}}b::foo=bar::baz[]={{value|valflag}}::baz[]=1.23|flag1|flag2}}bar{{baz}}');

        $this->assertSame('foo', $sequence->get(0));
        $this->assertSame('insert_tag', $sequence->get(1)->getName());
        $this->assertSame('a{{first}}b', $sequence->get(1)->getParameters()->get(0)->serialize());
        $this->assertSame('first', $sequence->get(1)->getParameters()->get(0)->get(1)->getName());
        $this->assertSame('a{{second}}b', $sequence->get(1)->getParameters()->get(1)->serialize());
        $this->assertSame('second', $sequence->get(1)->getParameters()->get(1)->get(1)->getName());
        $this->assertSame('bar', $sequence->get(1)->getParameters()->get('foo')->get(0));
        $this->assertSame('{{value|valflag}}', $sequence->get(1)->getParameters()->get('baz[]')->serialize());
        $this->assertSame('value', $sequence->get(1)->getParameters()->get('baz[]')->get(0)->getName());
        $this->assertSame('valflag', $sequence->get(1)->getParameters()->get('baz[]')->get(0)->getFlags()[0]->getName());
        $this->assertSame('1.23', $sequence->get(1)->getParameters()->all('baz[]')[1]->serialize());
        $this->assertSame('flag1', $sequence->get(1)->getFlags()[0]->getName());
        $this->assertSame('flag2', $sequence->get(1)->getFlags()[1]->getName());
        $this->assertSame('bar', $sequence->get(2));
        $this->assertSame('baz', $sequence->get(3)->getName());
    }

    /**
     * @group legacy
     */
    public function testRenderMixedCase(): void
    {
        $parser = new InsertTagParser($this->createMock(ContaoFramework::class), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class));
        $parser->addSubscription(new InsertTagSubscription(new LegacyInsertTag(System::getContainer()), '__invoke', 'br', null, true, false));

        $this->expectDeprecation('%sInsert tags with uppercase letters%s');

        $this->assertSame('<br>', $parser->render('bR'));
    }

    public function testReplaceFragment(): void
    {
        $handler = $this->createMock(FragmentHandler::class);
        $handler
            ->method('render')
            ->willReturnCallback(static fn (ControllerReference $reference) => '<esi '.$reference->attributes['insertTag'].'>')
        ;

        System::getContainer()->set('fragment.handler', $handler);

        $parser = new InsertTagParser($this->createMock(ContaoFramework::class), $this->createMock(LoggerInterface::class), $handler, $this->createMock(RequestStack::class));
        $parser->addSubscription(new InsertTagSubscription(new FragmentInsertTag(), '__invoke', 'fragment', null, true, true));
        $parser->addSubscription(new InsertTagSubscription(new LegacyInsertTag(System::getContainer()), '__invoke', 'br', null, true, false));

        $this->assertSame('<esi {{fragment::{{br}}}}>', $parser->replace('{{fragment::{{br}}}}'));
        $this->assertSame([[ChunkedText::TYPE_RAW, '<esi {{fragment::{{br}}}}>']], iterator_to_array($parser->replaceChunked('{{fragment::{{br}}}}')));

        $this->assertSame('<br>', $parser->replaceInline('{{fragment::{{br}}}}'));
        $this->assertSame([[ChunkedText::TYPE_RAW, '<br>']], iterator_to_array($parser->replaceInlineChunked('{{fragment::{{br}}}}')));
    }

    /**
     * @dataProvider getLegacyReplaceInsertTagsHooks
     */
    public function testLegacyReplaceInsertTagsHook(string $source, string $expected, \Closure $hook): void
    {
        $GLOBALS['TL_HOOKS']['replaceInsertTags'] = [
            [
                new class($hook) {
                    public function __construct(private readonly \Closure $hook)
                    {
                    }

                    public function __invoke(string &$a, bool &$b, string $c, array &$d, array &$e, array $f, int &$g, int|string &$h): string
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
        $parser->addSubscription(new InsertTagSubscription(new LegacyInsertTag(System::getContainer()), '__invoke', 'br', null, true, false));
        $parser->addBlockSubscription(new InsertTagSubscription(new IfLanguageInsertTag($this->createMock(RequestStack::class)), '__invoke', 'ifnlng', 'ifnlng', true, false));
        System::getContainer()->set('contao.insert_tag.parser', $parser);

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

        yield [
            'a{{ifnlng::xx}}b{{tag}}c{{ifnlng}}d',
            'abTAGcd',
            function ($tag, $useCache, $cachedValue, $flags) {
                $this->assertSame('tag', $tag);
                $this->assertSame([], $flags);

                return 'TAG';
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
                    public function __construct(private readonly \Closure $hook)
                    {
                    }

                    public function __invoke(string &$a, string &$b, string &$c, array &$d, bool &$e, array &$f, array $g, int &$h, int &$i): string
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
        $parser->addSubscription(new InsertTagSubscription(new LegacyInsertTag(System::getContainer()), '__invoke', 'br', null, true, false));

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
                $this->assertSame(['', 'br|flag', ''], $tags);
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
                $this->assertSame(['', 'flag' === $flag ? 'br|flag' : 'br|foo|bar', ''], $tags);
                $this->assertSame([], $cache);
                $this->assertSame(3, $_cnt);

                if ('flag' === $flag) {
                    $this->assertSame(['flag'], $flags);
                    $this->assertSame(0, $_rit);
                    $this->assertSame('<br>', $cachedValue);

                    return strtoupper($cachedValue);
                }

                $this->assertSame(['foo', 'bar'], $flags);
                $this->assertSame(0, $_rit);

                if ('foo' === $flag) {
                    $this->assertSame('<br>', $cachedValue);

                    return strtoupper($cachedValue);
                }

                $this->assertSame('<BR>', $cachedValue);
                $this->assertSame('bar', $flag);

                return str_rot13($cachedValue);
            },
        ];
    }
}
