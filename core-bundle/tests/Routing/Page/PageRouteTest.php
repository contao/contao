<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing\Page;

use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Routing\RedirectRoute;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;

class PageRouteTest extends TestCase
{
    public function testReturnsThePageModel(): void
    {
        $pageModel = $this->mockPageModel();
        $route = new PageRoute($pageModel);

        $this->assertSame($pageModel, $route->getPageModel());
    }

    public function testRoutePathMergesPageAliasWithUrlPrefixAndSuffix(): void
    {
        $route = new PageRoute($this->mockPageModel());

        $this->assertSame('/foo/bar.baz', $route->getPath());

        $route->setUrlPrefix('prefix');
        $route->setUrlSuffix('.suffix');

        $this->assertSame('/prefix/bar.suffix', $route->getPath());

        $route->setPath('/path/{pattern}');

        $this->assertSame('/prefix/path/{pattern}.suffix', $route->getPath());
    }

    public function testIgnoresPageAliasForAbsolutePath(): void
    {
        $route = new PageRoute($this->mockPageModel(), '/bar/{baz}');

        $this->assertSame('/foo/bar/{baz}.baz', $route->getPath());

        $route->setUrlPrefix('');
        $route->setUrlSuffix('.html');

        $this->assertSame('/bar/{baz}.html', $route->getPath());
    }

    public function testAppendsRelativePathToPageAlias(): void
    {
        $route = new PageRoute($this->mockPageModel(), '{foo}/{bar}');

        $this->assertSame('/foo/bar/{foo}/{bar}.baz', $route->getPath());

        $route->setUrlPrefix('');
        $route->setUrlSuffix('.html');

        $this->assertSame('/bar/{foo}/{bar}.html', $route->getPath());
    }

    public function testReturnsTheUrlPrefix(): void
    {
        $route = new PageRoute($this->mockPageModel());

        $this->assertSame('foo', $route->getUrlPrefix());

        $route->setUrlPrefix('prefix');

        $this->assertSame('prefix', $route->getUrlPrefix());
    }

    public function testReturnsTheUrlSuffix(): void
    {
        $route = new PageRoute($this->mockPageModel());

        $this->assertSame('.baz', $route->getUrlSuffix());

        $route->setUrlSuffix('suffix');

        $this->assertSame('suffix', $route->getUrlSuffix());
    }

    public function testReturnsTheContent(): void
    {
        $content = (object) ['foo' => 'bar'];

        $route = new PageRoute($this->mockPageModel());

        $this->assertNull($route->getContent());

        $route = new PageRoute($this->mockPageModel());
        $route->setContent($content);

        $this->assertSame($content, $route->getContent());

        $route = new PageRoute($this->mockPageModel());
        $route->setContent('foo');

        $this->assertSame('foo', $route->getContent());
    }

    public function testAddsPageLanguageAsLocaleToRouteDefaults(): void
    {
        $route = new PageRoute($this->mockPageModel());

        $this->assertSame('xy', $route->getDefault('_locale'));

        $route = new PageRoute($this->mockPageModel(['rootLanguage' => 'en']));

        $this->assertSame('en', $route->getDefault('_locale'));
    }

    public function testSetsPageDomainAsRouteHost(): void
    {
        $route = new PageRoute($this->mockPageModel());

        $this->assertSame('www.example.com', $route->getHost());
    }

    public function testSetsProtocolIfRootPageUsesSSL(): void
    {
        $route = new PageRoute($this->mockPageModel(['rootUseSSL' => false]));

        $this->assertEmpty($route->getSchemes());

        $route = new PageRoute($this->mockPageModel(['rootUseSSL' => true]));

        $this->assertSame(['https'], $route->getSchemes());
    }

    public function testSetsTargetUrlInOptions(): void
    {
        $route = new PageRoute($this->mockPageModel());

        $this->assertFalse($route->hasOption(RedirectRoute::TARGET_URL));

        $route->setTargetUrl('https://example.com');

        $this->assertTrue($route->hasOption(RedirectRoute::TARGET_URL));
        $this->assertSame('https://example.com', $route->getOption(RedirectRoute::TARGET_URL));
    }

    /**
     * @return PageModel&MockObject
     */
    private function mockPageModel(array $properties = []): PageModel
    {
        /** @var PageModel&MockObject */
        return $this->mockClassWithProperties(
            PageModel::class,
            array_merge(
                [
                    'id' => 17,
                    'alias' => 'bar',
                    'domain' => 'www.example.com',
                    'rootLanguage' => 'xy',
                    'rootUseSSL' => true,
                    'urlPrefix' => 'foo',
                    'urlSuffix' => '.baz',
                ],
                $properties
            )
        );
    }
}
