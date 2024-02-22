<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing\Content;

use Contao\ArticleModel;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Routing\Content\StringResolver;
use Contao\CoreBundle\Routing\Content\StringUrl;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\UrlHelper;

class StringResolverTest extends TestCase
{
    public function testAbstainsIfContentIsNotAStringUrl(): void
    {
        $insertTagParser = $this->createMock(InsertTagParser::class);
        $insertTagParser
            ->expects($this->never())
            ->method($this->anything())
        ;

        $urlHelper = new UrlHelper(new RequestStack());
        $content = $this->mockClassWithProperties(ArticleModel::class);

        $resolver = new StringResolver($insertTagParser, $urlHelper);
        $result = $resolver->resolve($content);

        $this->assertNull($result);
    }

    /**
     * @dataProvider resolvesStringUrlProvider
     */
    public function testResolvesStringUrl(StringUrl $content, string $insertTagResult, string $baseUrl, string $expected): void
    {
        $insertTagParser = $this->createMock(InsertTagParser::class);
        $insertTagParser
            ->expects($this->once())
            ->method('replaceInline')
            ->with($content->value)
            ->willReturn($insertTagResult)
        ;

        $requestStack = new RequestStack();
        $requestStack->push(Request::create($baseUrl));

        $urlHelper = new UrlHelper($requestStack);

        $resolver = new StringResolver($insertTagParser, $urlHelper);
        $result = $resolver->resolve($content);

        $this->assertTrue($result->hasTargetUrl());
        $this->assertSame($expected, $result->getTargetUrl());
    }

    public function resolvesStringUrlProvider(): \Generator
    {
        yield 'Returns an absolute URL' => [
            new StringUrl('https://example.com/foo/bar'),
            'https://example.com/foo/bar',
            'https://foobar.com',
            'https://example.com/foo/bar',
        ];

        yield 'Replaces insert tags' => [
            new StringUrl('{{link_url::42}}'),
            'https://example.com/foo/bar',
            'https://foobar.com',
            'https://example.com/foo/bar',
        ];

        yield 'Makes a relative URL absolute' => [
            new StringUrl('{{link_url::42}}'),
            '/foo/bar',
            'https://foobar.com',
            'https://foobar.com/foo/bar',
        ];

        yield 'Correctly handles mailto: links' => [
            new StringUrl('mailto:info@example.org'),
            'mailto:info@example.org',
            'https://foobar.com',
            'mailto:info@example.org',
        ];
    }
}
