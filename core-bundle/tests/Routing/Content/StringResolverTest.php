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
use Symfony\Component\Routing\RequestContext;

class StringResolverTest extends TestCase
{
    public function testAbstainsIfContentIsNotAStringUrl(): void
    {
        $insertTagParser = $this->createMock(InsertTagParser::class);
        $insertTagParser
            ->expects($this->never())
            ->method($this->anything())
        ;

        $requestStack = new RequestStack();
        $requestContext = new RequestContext();
        $urlHelper = new UrlHelper($requestStack, $requestContext);
        $content = $this->mockClassWithProperties(ArticleModel::class);

        $resolver = new StringResolver($insertTagParser, $urlHelper, $requestStack, $requestContext);
        $result = $resolver->resolve($content);

        $this->assertNull($result);
    }

    /**
     * @dataProvider stringUrlProvider
     */
    public function testResolvesStringUrlFromRequestStack(StringUrl $content, string $insertTagResult, string $baseUrl, string $expected): void
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

        $requestContext = new RequestContext();
        $urlHelper = new UrlHelper($requestStack, $requestContext);

        $resolver = new StringResolver($insertTagParser, $urlHelper, $requestStack, $requestContext);
        $result = $resolver->resolve($content);

        $this->assertTrue($result->hasTargetUrl());
        $this->assertSame($expected, $result->getTargetUrl());
    }

    /**
     * @dataProvider stringUrlProvider
     */
    public function testResolvesStringUrlFromRequestContext(StringUrl $content, string $insertTagResult, string $baseUrl, string $expected): void
    {
        $insertTagParser = $this->createMock(InsertTagParser::class);
        $insertTagParser
            ->expects($this->once())
            ->method('replaceInline')
            ->with($content->value)
            ->willReturn($insertTagResult)
        ;

        $requestStack = new RequestStack();
        $requestContext = RequestContext::fromUri($baseUrl);
        $urlHelper = new UrlHelper($requestStack, $requestContext);

        $resolver = new StringResolver($insertTagParser, $urlHelper, $requestStack, $requestContext);
        $result = $resolver->resolve($content);

        $this->assertTrue($result->hasTargetUrl());
        $this->assertSame($expected, $result->getTargetUrl());
    }

    public function stringUrlProvider(): \Generator
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

        yield 'Makes protocol-relative URL absolute' => [
            new StringUrl('{{link_url::42}}'),
            '//example.com/foo/bar',
            'https://example.com',
            'https://example.com/foo/bar',
        ];

        yield 'Correctly handles mailto: links' => [
            new StringUrl('mailto:info@example.org'),
            'mailto:info@example.org',
            'https://foobar.com',
            'mailto:info@example.org',
        ];
    }
}
