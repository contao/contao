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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

class PageFinderTest extends TestCase
{
    public function testGetCurrentPageFromRequest(): void
    {
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->never())
            ->method('getCurrentRequest')
        ;

        $pageFinder = new PageFinder(
            $this->createContaoFrameworkStub(),
            $this->createStub(RequestMatcherInterface::class),
            $requestStack,
        );

        $pageModel = $this->createStub(PageModel::class);

        $request = Request::create('https://localhost');
        $request->attributes->set('pageModel', $pageModel);

        $this->assertSame($pageModel, $pageFinder->getCurrentPage($request));
    }

    public function testGetCurrentPageFromRequestStack(): void
    {
        $pageModel = $this->createStub(PageModel::class);

        $request = Request::create('https://localhost');
        $request->attributes->set('pageModel', $pageModel);

        $requestStack = new RequestStack([$request]);

        $pageFinder = new PageFinder(
            $this->createContaoFrameworkStub(),
            $this->createStub(RequestMatcherInterface::class),
            $requestStack,
        );

        $this->assertSame($pageModel, $pageFinder->getCurrentPage());
    }

    public function testFindRootPageForHostReturnsNullIfRoutingHasNoPageModel(): void
    {
        $framework = $this->createContaoFrameworkMock();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $pageFinder = new PageFinder($framework, $this->mockRequestMatcher(null), new RequestStack());
        $result = $pageFinder->findRootPageForHostAndLanguage('www.example.org');

        $this->assertNull($result);
    }

    public function testFindRootPageForHostReturnsNullIfRoutingThrowsException(): void
    {
        $framework = $this->createContaoFrameworkMock();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $requestMatcher = $this->createStub(RequestMatcherInterface::class);
        $requestMatcher
            ->method('matchRequest')
            ->willThrowException($this->createStub(ExceptionInterface::class))
        ;

        $pageFinder = new PageFinder($framework, $requestMatcher, new RequestStack());
        $result = $pageFinder->findRootPageForHostAndLanguage('www.example.org');

        $this->assertNull($result);
    }

    public function testFindRootPageForHostReturnsMatchedRootPage(): void
    {
        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, ['type' => 'root']);

        $framework = $this->createContaoFrameworkMock();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $pageFinder = new PageFinder($framework, $this->mockRequestMatcher($pageModel), new RequestStack());
        $result = $pageFinder->findRootPageForHostAndLanguage('www.example.org');

        $this->assertSame($pageModel, $result);
    }

    public function testFindRootPageForHostQueriesForRootPage(): void
    {
        $rootPage = $this->createClassWithPropertiesStub(PageModel::class, ['id' => 42, 'type' => 'root']);

        $regularPage = $this->createClassWithPropertiesMock(PageModel::class, ['type' => 'regular', 'rootId' => 42]);
        $regularPage
            ->expects($this->once())
            ->method('loadDetails')
            ->willReturnSelf()
        ;

        $pageAdapter = $this->createAdapterMock(['findPublishedById']);
        $pageAdapter
            ->expects($this->once())
            ->method('findPublishedById')
            ->with(42)
            ->willReturn($rootPage)
        ;

        $framework = $this->createContaoFrameworkMock([PageModel::class => $pageAdapter]);
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $pageFinder = new PageFinder($framework, $this->mockRequestMatcher($regularPage), new RequestStack());
        $result = $pageFinder->findRootPageForHostAndLanguage('www.example.org');

        $this->assertSame($rootPage, $result);
    }

    public function testFindRootPageForRequestCreatesNewRequest(): void
    {
        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, ['type' => 'root']);
        $request = new Request();

        $framework = $this->createContaoFrameworkMock();
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

        $pageFinder = new PageFinder($framework, $requestMatcher, new RequestStack());
        $result = $pageFinder->findRootPageForRequest($request);

        $this->assertSame($pageModel, $result);
    }

    public function testFindRootPageForRequestWillUseExistingPageModel(): void
    {
        $rootPage = $this->createClassWithPropertiesStub(PageModel::class, ['id' => 42, 'type' => 'root']);

        $regularPage = $this->createClassWithPropertiesMock(PageModel::class, ['type' => 'regular', 'rootId' => 42]);
        $regularPage
            ->expects($this->once())
            ->method('loadDetails')
            ->willReturnSelf()
        ;

        $pageAdapter = $this->createAdapterMock(['findPublishedById']);
        $pageAdapter
            ->expects($this->once())
            ->method('findPublishedById')
            ->with(42)
            ->willReturn($rootPage)
        ;

        $framework = $this->createContaoFrameworkMock([PageModel::class => $pageAdapter]);
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $request = new Request();
        $request->attributes->set('pageModel', $regularPage);

        $pageFinder = new PageFinder($framework, $this->mockRequestMatcher(false), new RequestStack());
        $result = $pageFinder->findRootPageForRequest($request);

        $this->assertSame($rootPage, $result);
    }

    public function testDoesNotPrependTheProtocolIfTheHostnameIsEmpty(): void
    {
        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, ['type' => 'root']);

        $framework = $this->createContaoFrameworkMock();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $pageFinder = new PageFinder($framework, $this->mockRequestMatcher($pageModel, 'http://localhost'), new RequestStack());
        $result = $pageFinder->findRootPageForHostAndLanguage('');

        $this->assertSame($pageModel, $result);
    }

    private function mockRequestMatcher(PageModel|false|null $pageModel, string|null $requestUri = null): RequestMatcherInterface&MockObject
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
                    function (Request $request) use ($requestUri) {
                        $this->assertSame($requestUri ?? 'http://www.example.org', $request->getSchemeAndHttpHost());

                        return true;
                    },
                ))
                ->willReturn($pageModel ? ['pageModel' => $pageModel] : [])
            ;
        }

        return $requestMatcher;
    }
}
