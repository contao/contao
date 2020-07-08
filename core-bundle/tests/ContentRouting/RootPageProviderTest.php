<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\ContentRouting;

use Contao\CoreBundle\ContentRouting\ContentRoute;
use Contao\CoreBundle\ContentRouting\RootPageProvider;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Statement;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class RootPageProviderTest extends TestCase
{
    /**
     * @var PageModel&Adapter&MockObject
     */
    private $pageModelAdapter;

    /**
     * @var Connection&MockObject
     */
    private $connection;

    /**
     * @var RootPageProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->pageModelAdapter = $this->mockAdapter(['findFirstPublishedByPid']);
        $this->connection = $this->createMock(Connection::class);

        $framework = $this->mockContaoFramework([PageModel::class => $this->pageModelAdapter]);

        $this->provider = new RootPageProvider($framework, $this->connection);
    }

    public function testThrowsExceptionIfPageTypeIsNotSupported(): void
    {
        $this->expectException(RouteNotFoundException::class);

        /** @var PageModel&MockObject $page */
        $page = $this->mockClassWithProperties(PageModel::class, ['type' => 'foobar']);

        $this->provider->getRouteForPage($page);
    }

    public function testThrowsExceptionIfFirstPageOfRootIsNotFound(): void
    {
        $this->expectException(RouteNotFoundException::class);

        /** @var PageModel&MockObject $page */
        $page = $this->mockClassWithProperties(PageModel::class, ['id' => 17, 'type' => 'root']);

        $this->pageModelAdapter
            ->expects($this->once())
            ->method('findFirstPublishedByPid')
            ->with(17)
            ->willReturn(null)
        ;

        $this->provider->getRouteForPage($page);
    }

    public function testCreatesRedirectRouteToFirstPage(): void
    {
        /** @var PageModel&MockObject $page */
        $page = $this->mockClassWithProperties(PageModel::class, ['id' => 17, 'type' => 'root', 'alias' => 'root', 'urlPrefix' => 'en', 'urlSuffix' => '.html']);

        /** @var PageModel&MockObject $nextPage */
        $nextPage = $this->mockClassWithProperties(PageModel::class, ['id' => 18, 'pid' => 17, 'type' => 'root']);
        $nextPage
            ->expects($this->once())
            ->method('getAbsoluteUrl')
            ->willReturn('https://www.example.org/en/foobar.html')
        ;

        $this->pageModelAdapter
            ->expects($this->once())
            ->method('findFirstPublishedByPid')
            ->with(17)
            ->willReturn($nextPage)
        ;

        $route = $this->provider->getRouteForPage($page);

        $this->assertInstanceOf(ContentRoute::class, $route);

        $this->assertSame('/en/root.html', $route->getPath());
        $this->assertSame(RedirectController::class, $route->getDefault('_controller'));
        $this->assertSame('https://www.example.org/en/foobar.html', $route->getDefault('path'));
        $this->assertFalse($route->getDefault('permanent'));
    }

    public function testReturnsUrlSuffixesFromDatabase(): void
    {
        $statement = $this->createMock(Statement::class);
        $statement
            ->expects($this->once())
            ->method('fetchAll')
            ->with(FetchMode::COLUMN)
            ->willReturn(['foo', 'bar'])
        ;

        $this->connection
            ->expects($this->once())
            ->method('query')
            ->with("SELECT DISTINCT urlSuffix FROM tl_page WHERE type='root'")
            ->willReturn($statement)
        ;

        $this->assertSame(['foo', 'bar'], $this->provider->getUrlSuffixes());
    }

    public function testDoesNotSupportContentComposition(): void
    {
        $this->assertFalse($this->provider->supportsContentComposition());
    }
}
