<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing\ResponseContext;

use Contao\CoreBundle\Routing\ResponseContext\HtmlTag;
use Contao\CoreBundle\String\HtmlAttributes;
use PHPUnit\Framework\TestCase;

class HtmlTagTest extends TestCase
{
    public function testCreatesAndConfiguresTagsWithoutMutatingThem(): void
    {
        $script = HtmlTag::script('/app.js');
        $deferredScript = $script->withAttribute('defer');

        $this->assertSame('script', $script->getName());
        $this->assertSame('/app.js', $script->getAttributes()['src']);
        $this->assertFalse(isset($script->getAttributes()['defer']));
        $this->assertTrue(isset($deferredScript->getAttributes()['defer']));
        $this->assertNull($script->getContent());
        $this->assertFalse($script->escapesContent());

        $this->assertSame('stylesheet', HtmlTag::stylesheet('/app.css')->getAttributes()['rel']);
        $this->assertTrue(HtmlTag::inlineScript('alert(1)')->isInlineScript());
        $this->assertTrue(HtmlTag::inlineStyle('body {}')->isInlineStyle());
        $this->assertTrue(HtmlTag::title('<Title>')->escapesContent());
        $this->assertTrue(HtmlTag::create('noscript')->withContent('<p>Fallback</p>')->escapesContent());
        $this->assertFalse(HtmlTag::create('noscript')->withRawContent('<p>Fallback</p>')->escapesContent());
    }

    public function testRejectsInvalidTagNames(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTML tag name');

        HtmlTag::create('meta><script');
    }

    public function testAcceptsAttributesInEveryFactory(): void
    {
        $attributes = new HtmlAttributes(['data-factory' => 'create']);

        $this->assertSame('create', HtmlTag::create('base', attributes: $attributes)->getAttributes()['data-factory']);
        $this->assertSame('title', HtmlTag::title('Title', ['data-factory' => 'title'])->getAttributes()['data-factory']);
        $this->assertSame('meta', HtmlTag::meta(['data-factory' => 'meta'])->getAttributes()['data-factory']);
        $this->assertSame('link', HtmlTag::link(['data-factory' => 'link'])->getAttributes()['data-factory']);
        $this->assertSame('print', HtmlTag::stylesheet('/app.css', ['media' => 'print'])->getAttributes()['media']);
        $this->assertTrue(isset(HtmlTag::script('/app.js', ['defer' => true])->getAttributes()['defer']));
        $this->assertSame('module', HtmlTag::inlineScript('alert(1)', ['type' => 'module'])->getAttributes()['type']);
        $this->assertSame('screen', HtmlTag::inlineStyle('body {}', ['media' => 'screen'])->getAttributes()['media']);

        $this->assertSame('/app.js', HtmlTag::script('/app.js', ['src' => '/other.js'])->getAttributes()['src']);
        $this->assertSame('/app.css', HtmlTag::stylesheet('/app.css', ['href' => '/other.css'])->getAttributes()['href']);
        HtmlTag::create('base', attributes: $attributes)->withAttribute('mutated');
        $this->assertFalse(isset($attributes['mutated']));
    }

    public function testSuggestsSemanticIdentifiers(): void
    {
        $this->assertSame('script[src="/app.js"]', HtmlTag::script('/app.js', ['defer' => true])->getSuggestedIdentifier());
        $this->assertSame('link[rel="stylesheet"][href="/app.css"]', HtmlTag::stylesheet('/app.css')->getSuggestedIdentifier());
        $this->assertSame('meta[property="og:title"]', HtmlTag::meta(['property' => 'og:title', 'content' => 'Title'])->getSuggestedIdentifier());
        $this->assertSame('title', HtmlTag::title('Page title')->getSuggestedIdentifier());
        $this->assertMatchesRegularExpression('/^script\[[a-f0-9]+\]$/', HtmlTag::inlineScript('alert(1)')->getSuggestedIdentifier());
    }
}
