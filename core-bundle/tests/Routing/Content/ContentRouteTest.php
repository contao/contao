<?php

namespace Contao\CoreBundle\Tests\Routing\Content;

use Contao\CoreBundle\Routing\Content\ContentRoute;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;

class ContentRouteTest extends TestCase
{
    public function testReturnsThePageModel()
    {
        $page = $this->mockPage();

        $route = new ContentRoute($page);

        $this->assertSame($page, $route->getPage());
    }

    public function testRoutePathMergesPageAliasWithUrlPrefixAndSuffix(): void
    {
        $route = new ContentRoute($this->mockPage());

        $this->assertSame('/foo/bar.baz', $route->getPath());

        $route->setUrlPrefix('prefix');
        $route->setUrlSuffix('.suffix');

        $this->assertSame('/prefix/bar.suffix', $route->getPath());

        $route->setPath('/path/{pattern}');

        $this->assertSame('/prefix/path/{pattern}.suffix', $route->getPath());
    }

    public function testReturnsTheUrlPrefix(): void
    {
        $route = new ContentRoute($this->mockPage());

        $this->assertSame('foo', $route->getUrlPrefix());

        $route->setUrlPrefix('prefix');

        $this->assertSame('prefix', $route->getUrlPrefix());
    }

    public function testReturnsTheUrlSuffix(): void
    {
        $route = new ContentRoute($this->mockPage());

        $this->assertSame('.baz', $route->getUrlSuffix());

        $route->setUrlSuffix('suffix');

        $this->assertSame('suffix', $route->getUrlSuffix());
    }

    public function testReturnsTheContent(): void
    {
        $content = (object) ['foo' => 'bar'];

        $route = new ContentRoute($this->mockPage(), $content);

        $this->assertSame($content, $route->getContent());

        $route->setContent('foo');
        $this->assertSame('foo', $route->getContent());
    }

    public function testAddsPageLanguageAsLocaleToRouteDefaults(): void
    {
        $route = new ContentRoute($this->mockPage());

        $this->assertSame('xy', $route->getDefault('_locale'));

        $route = new ContentRoute($this->mockPage(['rootLanguage' => 'en']));

        $this->assertSame('en', $route->getDefault('_locale'));
    }

    public function testSetsPageDomainAsRouteHost(): void
    {
        $route = new ContentRoute($this->mockPage());
        $this->assertSame('www.example.com', $route->getHost());
    }

    public function testSetsProtocolIfRootPageUsesSSL(): void
    {
        $route = new ContentRoute($this->mockPage(['rootUseSSL' => false]));
        $this->assertEmpty($route->getSchemes());

        $route = new ContentRoute($this->mockPage(['rootUseSSL' => true]));
        $this->assertSame(['https'], $route->getSchemes());
    }

    public function testCreatePageWithParametersAndRequiresItemIfConfigured(): void
    {
        $route = ContentRoute::createWithParameters($this->mockPage(['requireItem' => false]), '/items/news');

        $this->assertSame('/foo/bar{parameters}.baz', $route->getPath());
        $this->assertSame('/items/news', $route->getDefault('parameters'));
        $this->assertSame('(/.+)?', $route->getRequirement('parameters'));

        $route = ContentRoute::createWithParameters($this->mockPage(['requireItem' => true]), '/items/news');

        $this->assertSame('/foo/bar{parameters}.baz', $route->getPath());
        $this->assertSame('/items/news', $route->getDefault('parameters'));
        $this->assertSame('/.+', $route->getRequirement('parameters'));
    }

    /**
     * @return PageModel&MockObject $page
     */
    private function mockPage(array $properties = []): PageModel
    {
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
