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
    }

    public function testAddsPageLanguageAsLocaleToRouteDefaults(): void
    {
        $route = new PageRoute($this->mockPageModel());

        $this->assertSame('xx', $route->getDefault('_locale'));

        $route = new PageRoute($this->mockPageModel(['rootLanguage' => 'en-US']));

        $this->assertSame('en_US', $route->getDefault('_locale'));
    }

    public function testSetsPageDomainAsRouteHost(): void
    {
        $route = new PageRoute($this->mockPageModel());

        $this->assertSame('www.example.com', $route->getHost());
    }

    public function testSetsProtocolIfRootPageUsesSSL(): void
    {
        $route = new PageRoute($this->mockPageModel(['rootUseSSL' => '']));

        $this->assertSame(['http'], $route->getSchemes());

        $route = new PageRoute($this->mockPageModel(['rootUseSSL' => true]));

        $this->assertSame(['https'], $route->getSchemes());
    }

    /**
     * @return PageModel&MockObject
     */
    private function mockPageModel(array $properties = []): PageModel
    {
        return $this->mockClassWithProperties(
            PageModel::class,
            array_merge(
                [
                    'id' => 17,
                    'alias' => 'bar',
                    'domain' => 'www.example.com',
                    'rootLanguage' => 'xx',
                    'rootUseSSL' => true,
                    'urlPrefix' => 'foo',
                    'urlSuffix' => '.baz',
                ],
                $properties
            )
        );
    }
}
