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
use Contao\CoreBundle\Routing\Content\PageRouteProvider;
use Contao\CoreBundle\Routing\Page\PageRouteFactory;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Routing\Route;

class PageRouteProviderTest extends TestCase
{
    /**
     * @var PageRouteFactory|MockObject
     */
    private $routeFactory;

    /**
     * @var PageRouteProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->routeFactory = $this->createMock(PageRouteFactory::class);
        $this->provider = new PageRouteProvider($this->routeFactory);
    }

    public function testSupportsPages(): void
    {
        $this->assertTrue($this->provider->supportsContent($this->mockPage()));
        $this->assertFalse($this->provider->supportsContent($this->mockClassWithProperties(ArticleModel::class)));
    }

    public function testCreatesParameteredContentRoute(): void
    {
        $page = $this->mockPage();
        $route = new Route('/');

        $this->routeFactory
            ->expects($this->once())
            ->method('createRoute')
            ->with($page)
            ->willReturn($route)
        ;

        $this->assertSame($route, $this->provider->getRouteForContent($page));
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
