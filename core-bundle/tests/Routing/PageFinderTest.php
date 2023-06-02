<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing;

use Contao\CoreBundle\Routing\PageFinder;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

class PageFinderTest extends TestCase
{
    public function testFindRootPageForHostReturnsNullIfRoutingHasNoPageModel(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $pageFinder = new PageFinder($framework, $this->mockRequestMatcher(null));
        $result = $pageFinder->findRootPageForHostAndLanguage('www.example.org');

        $this->assertNull($result);
    }

    public function testFindRootPageForHostReturnsNullIfRoutingThrowsException(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $requestMatcher = $this->createMock(RequestMatcherInterface::class);
        $requestMatcher
            ->method('matchRequest')
            ->willThrowException($this->createMock(ExceptionInterface::class))
        ;

        $pageFinder = new PageFinder($framework, $requestMatcher);
        $result = $pageFinder->findRootPageForHostAndLanguage('www.example.org');

        $this->assertNull($result);
    }

    public function testFindRootPageForHostReturnsMatchedRootPage(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, ['type' => 'root']);

        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $pageFinder = new PageFinder($framework, $this->mockRequestMatcher($pageModel));
        $result = $pageFinder->findRootPageForHostAndLanguage('www.example.org');

        $this->assertSame($pageModel, $result);
    }

    public function testFindRootPageForHostQueriesForRootPage(): void
    {
        $rootPage = $this->mockClassWithProperties(PageModel::class, ['id' => 42, 'type' => 'root']);

        $regularPage = $this->mockClassWithProperties(PageModel::class, ['type' => 'regular', 'rootId' => 42]);
        $regularPage
            ->expects($this->once())
            ->method('loadDetails')
            ->willReturnSelf()
        ;

        $pageAdapter = $this->mockAdapter(['findPublishedById']);
        $pageAdapter
            ->expects($this->once())
            ->method('findPublishedById')
            ->with(42)
            ->willReturn($rootPage)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $pageFinder = new PageFinder($framework, $this->mockRequestMatcher($regularPage));
        $result = $pageFinder->findRootPageForHostAndLanguage('www.example.org');

        $this->assertSame($rootPage, $result);
    }

    public function testFindRootPageForRequestCreatesNewRequest(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, ['type' => 'root']);
        $request = new Request();

        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $requestMatcher = $this->createMock(RequestMatcherInterface::class);
        $requestMatcher
            ->expects($this->once())
            ->method('matchRequest')
            ->with($this->callback(static fn (Request $r) => $r !== $request))
            ->willReturn(['pageModel' => $pageModel])
        ;

        $pageFinder = new PageFinder($framework, $requestMatcher);
        $result = $pageFinder->findRootPageForRequest($request);

        $this->assertSame($pageModel, $result);
    }

    public function testFindRootPageForRequestWillUseExistingPageModel(): void
    {
        $rootPage = $this->mockClassWithProperties(PageModel::class, ['id' => 42, 'type' => 'root']);

        $regularPage = $this->mockClassWithProperties(PageModel::class, ['type' => 'regular', 'rootId' => 42]);
        $regularPage
            ->expects($this->once())
            ->method('loadDetails')
            ->willReturnSelf()
        ;

        $pageAdapter = $this->mockAdapter(['findPublishedById']);
        $pageAdapter
            ->expects($this->once())
            ->method('findPublishedById')
            ->with(42)
            ->willReturn($rootPage)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $request = new Request();
        $request->attributes->set('pageModel', $regularPage);

        $pageFinder = new PageFinder($framework, $this->mockRequestMatcher(false));
        $result = $pageFinder->findRootPageForRequest($request);

        $this->assertSame($rootPage, $result);
    }

    /**
     * @return RequestMatcherInterface&MockObject
     */
    private function mockRequestMatcher(PageModel|false|null $pageModel): RequestMatcherInterface
    {
        $requestMatcher = $this->createMock(RequestMatcherInterface::class);

        if (false === $pageModel) {
            $requestMatcher
                ->expects($this->never())
                ->method('matchRequest')
            ;
        } else {
            $requestMatcher
                ->expects($this->once())
                ->method('matchRequest')
                ->with($this->callback(
                    function (Request $request) {
                        $this->assertSame('http://www.example.org', $request->getSchemeAndHttpHost());

                        return true;
                    }
                ))
                ->willReturn($pageModel ? ['pageModel' => $pageModel] : [])
            ;
        }

        return $requestMatcher;
    }
}
