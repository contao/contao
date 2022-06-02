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

class InsertTagParserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = $this->getContainerWithContaoConfiguration($this->getTempDir());
        $container->set('contao.security.token_checker', $this->createMock(TokenChecker::class));

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

    public function testRender(): void
    {
        $parser = new InsertTagParser($this->createMock(ContaoFramework::class));

        $this->assertSame('<br>', $parser->render('br'));

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
}
