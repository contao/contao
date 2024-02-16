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

use Contao\ArticleModel;
use Contao\CoreBundle\Routing\Content\ContentUrlResolverInterface;
use Contao\CoreBundle\Routing\Content\ContentUrlResult;
use Contao\CoreBundle\Routing\Content\StringUrl;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ContentUrlGeneratorTest extends TestCase
{
    public function testGeneratesPageUrlUsingUrlGenerator(): void
    {
        $content = $this->mockPageModel();
        $route = new PageRoute($content);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with(PageRoute::PAGE_BASED_ROUTE_NAME, [RouteObjectInterface::ROUTE_OBJECT => $route], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('https://example.com')
        ;

        $pageRegistry = $this->mockPageRegistry($route);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $service = new ContentUrlGenerator($urlGenerator, $pageRegistry, $entityManager, []);
        $url = $service->generate($content);

        $this->assertSame('https://example.com', $url);
    }

    public function testRedirectsPageToAnotherPage(): void
    {
        $pageModel1 = $this->mockPageModel();
        $pageModel2 = $this->mockPageModel();
        $route = new PageRoute($pageModel2);

        $urlGenerator = $this->mockUrlGenerator($route);
        $pageRegistry = $this->mockPageRegistry($route);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $resolver = $this->mockResolver(
            [$pageModel1, ContentUrlResult::redirect($pageModel2)],
            [$pageModel2, null],
        );

        $service = new ContentUrlGenerator($urlGenerator, $pageRegistry, $entityManager, [$resolver]);
        $service->generate($pageModel1);
    }

    public function testGeneratesParametersForPageModel(): void
    {
        $pageModel1 = $this->mockPageModel();
        $pageModel2 = $this->mockPageModel();
        $route = new PageRoute($pageModel2);
        $parameters = ['foo' => 'bar'];

        $urlGenerator = $this->mockUrlGenerator($route, $parameters);
        $pageRegistry = $this->mockPageRegistry($route);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $resolver = $this->mockResolver(
            [$pageModel2, null],
        );

        $service = new ContentUrlGenerator($urlGenerator, $pageRegistry, $entityManager, [$resolver]);
        $service->generate($pageModel1, $parameters);
    }

    public function testIgnoresParametersOnRedirect(): void
    {
        $pageModel1 = $this->mockPageModel();
        $pageModel2 = $this->mockPageModel();
        $route = new PageRoute($pageModel2);
        $parameters = ['foo' => 'bar'];

        $urlGenerator = $this->mockUrlGenerator($route);
        $pageRegistry = $this->mockPageRegistry($route);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $resolver = $this->mockResolver(
            [$pageModel1, ContentUrlResult::redirect($pageModel2)],
            [$pageModel2, null],
        );

        $service = new ContentUrlGenerator($urlGenerator, $pageRegistry, $entityManager, [$resolver]);
        $service->generate($pageModel1, $parameters);
    }

    public function testReturnsAbsoluteUrlResult(): void
    {
        $content = $this->mockPageModel();

        $urlGenerator = $this->mockUrlGenerator(null);
        $pageRegistry = $this->mockPageRegistry(null);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $resolver = $this->mockResolver(
            [$content, new ContentUrlResult('https://example.net')],
        );

        $service = new ContentUrlGenerator($urlGenerator, $pageRegistry, $entityManager, [$resolver]);
        $url = $service->generate($content);

        $this->assertSame('https://example.net', $url);
    }

    public function testReturnsAbsoluteUrlResultAfterRedirect(): void
    {
        $pageModel1 = $this->mockPageModel();
        $pageModel2 = $this->mockPageModel();

        $urlGenerator = $this->mockUrlGenerator(null);
        $pageRegistry = $this->mockPageRegistry(null);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $resolver = $this->mockResolver(
            [$pageModel1, ContentUrlResult::redirect($pageModel2)],
            [$pageModel2, new ContentUrlResult('https://example.net')],
        );

        $service = new ContentUrlGenerator($urlGenerator, $pageRegistry, $entityManager, [$resolver]);
        $url = $service->generate($pageModel1);

        $this->assertSame('https://example.net', $url);
    }

    public function testResolvesStringResult(): void
    {
        $content = $this->mockPageModel();

        $urlGenerator = $this->mockUrlGenerator(null);
        $pageRegistry = $this->mockPageRegistry(null);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $resolver = $this->mockResolver(
            [$content, ContentUrlResult::url('https://example.net')],
            [$this->isInstanceOf(StringUrl::class), new ContentUrlResult('https://example.net')],
        );

        $service = new ContentUrlGenerator($urlGenerator, $pageRegistry, $entityManager, [$resolver]);
        $url = $service->generate($content);

        $this->assertSame('https://example.net', $url);
    }

    public function testResolvesFromMultipleResolvers(): void
    {
        $content = $this->mockPageModel();

        $urlGenerator = $this->mockUrlGenerator(null);
        $pageRegistry = $this->mockPageRegistry(null);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $pageResolver = $this->mockResolver(
            [$content, ContentUrlResult::url('https://example.net')],
            [$this->isInstanceOf(StringUrl::class), null],
        );

        $stringResolver = $this->mockResolver(
            [$this->isInstanceOf(StringUrl::class), new ContentUrlResult('https://example.net')],
        );

        $service = new ContentUrlGenerator($urlGenerator, $pageRegistry, $entityManager, [$pageResolver, $stringResolver]);
        $url = $service->generate($content);

        $this->assertSame('https://example.net', $url);
    }

    public function testGeneratesContentUrl(): void
    {
        $content = $this->createModel(ArticleModel::class, ['id' => 15]);
        $target = $this->mockPageModel();
        $route = new PageRoute($target);

        $urlGenerator = $this->mockUrlGenerator($route);
        $pageRegistry = $this->mockPageRegistry($route);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $resolver = $this->mockResolver(
            [$content, ContentUrlResult::resolve($target)],
            [$target, null],
        );

        $service = new ContentUrlGenerator($urlGenerator, $pageRegistry, $entityManager, [$resolver]);
        $service->generate($content);

        $this->assertSame($content, $route->getContent());
        $this->assertSame('tl_article.15', $route->getRouteKey());
    }

    public function testFetchesContentParametersFromResolvers(): void
    {
        $content = $this->createModel(ArticleModel::class, ['id' => 15]);
        $target = $this->mockPageModel();
        $route = new PageRoute($target, 'foo/{parameters}');
        $parameters = ['parameters' => '/articles/15.html'];

        $urlGenerator = $this->mockUrlGenerator($route, $parameters);
        $pageRegistry = $this->mockPageRegistry($route);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $resolver = $this->mockResolver(
            [$content, ContentUrlResult::resolve($target)],
            [$target, null],
        );

        $resolver
            ->expects($this->once())
            ->method('getParametersForContent')
            ->with($content, $target)
            ->willReturn($parameters)
        ;

        $service = new ContentUrlGenerator($urlGenerator, $pageRegistry, $entityManager, [$resolver]);
        $service->generate($content);

        $this->assertSame($content, $route->getContent());
        $this->assertSame('tl_article.15', $route->getRouteKey());
    }

    public function testIgnoresUnknownContentParametersFromResolvers(): void
    {
        $content = $this->createModel(ArticleModel::class, ['id' => 15]);
        $target = $this->mockPageModel();
        $route = new PageRoute($target, 'foo/{parameters}');

        $urlGenerator = $this->mockUrlGenerator($route, ['parameters' => '/articles/15.html']);
        $pageRegistry = $this->mockPageRegistry($route);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $resolver = $this->mockResolver(
            [$content, ContentUrlResult::resolve($target)],
            [$target, null],
        );

        $resolver
            ->expects($this->once())
            ->method('getParametersForContent')
            ->with($content, $target)
            ->willReturn(['parameters' => '/articles/15.html', 'foo' => 'bar'])
        ;

        $service = new ContentUrlGenerator($urlGenerator, $pageRegistry, $entityManager, [$resolver]);
        $service->generate($content);

        $this->assertSame($content, $route->getContent());
        $this->assertSame('tl_article.15', $route->getRouteKey());
    }

    private function mockUrlGenerator(PageRoute|null $route, array $parameters = []): UrlGeneratorInterface&MockObject
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        if (!$route) {
            $urlGenerator
                ->expects($this->never())
                ->method('generate')
            ;

            return $urlGenerator;
        }

        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with(
                PageRoute::PAGE_BASED_ROUTE_NAME,
                [...$parameters, RouteObjectInterface::ROUTE_OBJECT => $route],
                UrlGeneratorInterface::ABSOLUTE_PATH,
            )
        ;

        return $urlGenerator;
    }

    private function mockPageRegistry(PageRoute|null $route): PageRegistry&MockObject
    {
        $pageRegistry = $this->createMock(PageRegistry::class);

        if (!$route) {
            $pageRegistry
                ->expects($this->never())
                ->method('getRoute')
            ;

            return $pageRegistry;
        }

        $pageRegistry
            ->expects($this->once())
            ->method('getRoute')
            ->with($route->getPageModel())
            ->willReturn($route)
        ;

        return $pageRegistry;
    }

    private function mockPageModel(): PageModel
    {
        return $this->createModel(PageModel::class, [
            'urlPrefix' => '',
            'urlSuffix' => '',
        ]);
    }

    private function mockResolver(array ...$cases): ContentUrlResolverInterface&MockObject
    {
        $resolver = $this->createMock(ContentUrlResolverInterface::class);
        $resolver
            ->expects($this->exactly(\count($cases)))
            ->method('resolve')
            ->withConsecutive(...array_map(static fn (array $case) => [$case[0]], $cases))
            ->willReturnOnConsecutiveCalls(...array_column($cases, 1))
        ;

        return $resolver;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $modelClass
     *
     * @return T
     *
     * @phpstan-return T
     */
    private function createModel(string $modelClass, array $data = []): object
    {
        $ref = new \ReflectionClass($modelClass);
        $model = $ref->newInstanceWithoutConstructor();

        $prop = $ref->getProperty('arrData');
        $prop->setValue($model, $data);

        if (PageModel::class === $modelClass) {
            $prop = $ref->getProperty('blnDetailsLoaded');
            $prop->setValue($model, true);
        }

        return $model;
    }
}
