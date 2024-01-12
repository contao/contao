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

        $requestContext = $this->createMock(RequestContext::class);
        $requestContext
            ->expects($this->never())
            ->method($this->anything())
        ;

        $content = $this->mockClassWithProperties(ArticleModel::class);

        $resolver = new StringResolver($insertTagParser, $requestContext);
        $result = $resolver->resolve($content);

        $this->assertTrue($result->isAbstained());
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

        $requestContext = $this->createMock(RequestContext::class);
        $requestContext
            ->expects($this->once())
            ->method('getBaseUrl')
            ->willReturn($baseUrl)
        ;

        $resolver = new StringResolver($insertTagParser, $requestContext);
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
    }
}
