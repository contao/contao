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
use Contao\CoreBundle\Fragment\FragmentHandler;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\ChunkedText;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\InsertTag\ParsedInsertTag;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\InsertTags;
use Contao\System;
use Monolog\Logger;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ControllerReference;

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
        $container->set('monolog.logger.contao.error', $this->createMock(Logger::class));
        $container->set('request_stack', $requestStack);

        System::setContainer($container);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_MIME']);

        $this->resetStaticProperties([InsertTags::class, System::class, Config::class]);

        parent::tearDown();
    }

    public function testReplace(): void
    {
        $parser = new InsertTagParser($this->createMock(ContaoFramework::class));

        $this->assertSame('<br>', $parser->replace('{{br}}'));
        $this->assertSame([[ChunkedText::TYPE_RAW, '<br>']], iterator_to_array($parser->replaceChunked('{{br}}')));
    }

    public function testReplaceUnknown(): void
    {
        $parser = new InsertTagParser($this->createMock(ContaoFramework::class));

        $this->assertSame('{{doesnotexist}}', $parser->replace('{{doesnotexist}}'));
        $this->assertSame([[ChunkedText::TYPE_TEXT, '{{doesnotexist}}']], iterator_to_array($parser->replaceChunked('{{doesnotexist}}')));
    }

    public function testRender(): void
    {
        $parser = new InsertTagParser($this->createMock(ContaoFramework::class));

        $this->assertSame('<br>', $parser->render('br')->getValue());

        $this->expectExceptionMessage('Rendering a single insert tag has to return a single raw chunk');

        $parser->render('br}}foo{{br');
    }

    public function testParseTag(): void
    {
        $insertTagParser = new InsertTagParser($this->createMock(ContaoFramework::class));

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
        //$insertTagParser = new InsertTagParser($this->createMock(ContaoFramework::class));
        //$sequence = $insertTagParser->parse('foo{{insert_tag::a{{first}}b::a{{second}}b?foo=bar&baz[]={{value|valflag}}&baz[]=1.23|flag1|flag2}}bar{{baz}}');

        //var_dump($sequence);
    }

    /**
     * @group legacy
     */
    public function testRenderMixedCase(): void
    {
        $parser = new InsertTagParser($this->createMock(ContaoFramework::class));

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

        $parser = new InsertTagParser($this->createMock(ContaoFramework::class));

        $this->assertSame('<esi {{fragment::{{br}}}}>', $parser->replace('{{fragment::{{br}}}}'));
        $this->assertSame([[ChunkedText::TYPE_RAW, '<esi {{fragment::{{br}}}}>']], iterator_to_array($parser->replaceChunked('{{fragment::{{br}}}}')));

        $this->assertSame('<br>', $parser->replaceInline('{{fragment::{{br}}}}'));
        $this->assertSame([[ChunkedText::TYPE_RAW, '<br>']], iterator_to_array($parser->replaceInlineChunked('{{fragment::{{br}}}}')));
    }
}
