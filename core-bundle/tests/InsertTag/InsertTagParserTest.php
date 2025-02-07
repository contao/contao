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
use Contao\CoreBundle\Tests\Fixtures\Helper\HookHelper;
use Contao\CoreBundle\Tests\TestCase;
use Contao\InsertTags;
use Contao\System;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Contracts\Translation\TranslatorInterface;

class InsertTagParserTest extends TestCase
{
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
        unset($GLOBALS['TL_MIME'], $GLOBALS['TL_HOOKS'], $GLOBALS['objPage']);

        $this->resetStaticProperties([InsertTags::class, System::class, Config::class]);

        parent::tearDown();
    }

    public function testReplace(): void
    {
        $parser = $this->getInsertTagParser();

        $this->assertSame('<br>', $parser->replace('{{br}}'));
        $this->assertSame([[ChunkedText::TYPE_RAW, '<br>']], iterator_to_array($parser->replaceChunked('{{br}}')));
    }

    public function testReplaceUnknown(): void
    {
        $parser = $this->getInsertTagParser();

        $this->assertSame('{{doesnotexist}}', $parser->replace('{{doesnotexist}}'));
        $this->assertSame([[ChunkedText::TYPE_TEXT, '{{doesnotexist}}']], iterator_to_array($parser->replaceChunked('{{doesnotexist}}')));
    }

    public function testRender(): void
    {
        $parser = $this->getInsertTagParser();
        $parser->addSubscription(new InsertTagSubscription(new LegacyInsertTag(System::getContainer()), '__invoke', 'env', null, true, false));

        $this->assertSame('<br>', $parser->renderTag('br')->getValue());
        $this->assertSame('', $parser->renderTag('env::empty-insert-tag')->getValue());
        $this->assertSame('{{does_not_exist}}', $parser->renderTag('does_not_exist')->getValue());

        $this->expectExceptionMessage('Rendering a single insert tag has to return a single chunk');
        $this->expectUserDeprecationMessageMatches('/Invalid insert tag name/');

        $parser->renderTag('br}}foo{{br');
    }

    public function testParseTag(): void
    {
        $parser = $this->getInsertTagParser();

        $insertTag = $parser->parseTag('insert_tag::first::second::foo=bar::baz[]=1::baz[]=1.23|flag1|flag2');

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

        $insertTag = $parser->parseTag('insert_tag::param::foo={{bar::param|flag1}}|flag2');

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
        $parser = $this->getInsertTagParser();
        $sequence = $parser->parse('foo{{insert_tag::a{{first}}b::a{{second}}b::foo=bar::baz[]={{value|valflag}}::baz[]=1.23|flag1|flag2}}bar{{baz}}');

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

    public function testRenderMixedCase(): void
    {
        $parser = $this->getInsertTagParser();

        $this->expectUserDeprecationMessageMatches('/Insert tags with uppercase letters/');

        $this->assertSame('<br>', $parser->renderTag('bR')->getValue());
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

    public function testSimpleInsertTag(): void
    {
        $parser = $this->getInsertTagParser();

        HookHelper::registerInsertTagsHook(
            function (string $tag) {
                $this->assertSame('tag', $tag);

                return 'baz';
            },
        );

        $result = $parser->replaceInline('foo {{tag}} bar');
        $this->assertSame('foo baz bar', $result);
    }

    public function testNestedInsertTags(): void
    {
        $parser = $this->getInsertTagParser();

        HookHelper::registerInsertTagsHook(
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
        );

        $result = $parser->replaceInline('prefix{{first::foo|bar|baz}}middle{{second::a:b::{{br|inner}}|outer}}middle{{br}}suffix');
        $this->assertSame('prefix[first::foo]middle[second::a:b::<br>]middle<br>suffix', $result);
    }

    public function testConditionalInsertTag(): void
    {
        $parser = $this->getInsertTagParser();

        HookHelper::registerInsertTagsHook(
            function ($tag, $useCache, $cachedValue, $flags, $tags, $cache, &$_rit) {
                $this->assertSame('conditional', $tag);
                $this->assertSame(0, $_rit);
                $this->assertSame([], $flags);
                $_rit = array_search('conditional_end', $tags, true) - 1;
                $this->assertSame('conditional_end', $tags[$_rit + 1]);

                return '';
            },
        );

        $result = $parser->replaceInline('a{{conditional}}b{{br}}c{{conditional_end}}d');
        $this->assertSame('ad', $result);
    }

    public function testInsertTagInConditionalIflng(): void
    {
        $parser = $this->getInsertTagParser();

        HookHelper::registerInsertTagsHook(
            function (string $tag, $useCache, $cachedValue, $flags) {
                $this->assertSame('tag', $tag);
                $this->assertSame([], $flags);

                return 'TAG';
            },
        );

        $result = $parser->replaceInline('a{{ifnlng::xx}}b{{tag}}c{{ifnlng}}d');
        $this->assertSame('abTAGcd', $result);
    }

    public function testSimpleInsertTagFlag(): void
    {
        $parser = $this->getInsertTagParser();

        HookHelper::registerHook(
            'insertTagFlags',
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
        );

        $result = $parser->replaceInline('foo{{br|flag}}bar');
        $this->assertSame('foo<BR>bar', $result);
    }

    public function testMultipleInsertTagFlags(): void
    {
        $parser = $this->getInsertTagParser();

        HookHelper::registerHook(
            'insertTagFlags',
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
        );

        $result = $parser->replaceInline('foo{{br|flag}}bar{{br|foo|bar}}baz');
        $this->assertSame('foo<BR>bar<OE>baz', $result);
    }

    public function testBlockSubscriptionEndTagExists(): void
    {
        $parser = new InsertTagParser($this->createMock(ContaoFramework::class), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class));
        $parser->addSubscription(new InsertTagSubscription(new LegacyInsertTag(System::getContainer()), '__invoke', 'foo', null, true, false));
        $parser->addBlockSubscription(new InsertTagSubscription(new IfLanguageInsertTag($this->createMock(TranslatorInterface::class)), '__invoke', 'foo_start', 'foo_end', true, false));
        System::getContainer()->set('contao.insert_tag.parser', $parser);

        $this->assertTrue($parser->hasInsertTag('foo'));
        $this->assertTrue($parser->hasInsertTag('foo_start'));
        $this->assertTrue($parser->hasInsertTag('foo_end'));
        $this->assertFalse($parser->hasInsertTag('bar'));

        $parser->addBlockSubscription(new InsertTagSubscription(new IfLanguageInsertTag($this->createMock(TranslatorInterface::class)), '__invoke', 'foo_start', 'foo_end_different', true, false));

        $this->assertTrue($parser->hasInsertTag('foo_start'));
        $this->assertFalse($parser->hasInsertTag('foo_end'));
        $this->assertTrue($parser->hasInsertTag('foo_end_different'));
    }

    private function getInsertTagParser(): InsertTagParser
    {
        $parser = new InsertTagParser($this->createMock(ContaoFramework::class), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class));
        $parser->addSubscription(new InsertTagSubscription(new LegacyInsertTag(System::getContainer()), '__invoke', 'br', null, true, false));
        $parser->addBlockSubscription(new InsertTagSubscription(new IfLanguageInsertTag($this->createMock(TranslatorInterface::class)), '__invoke', 'ifnlng', 'ifnlng', true, false));
        System::getContainer()->set('contao.insert_tag.parser', $parser);

        return $parser;
    }
}
