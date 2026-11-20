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
use Contao\CoreBundle\Tests\Doctrine\DoctrineTestCase;
use Contao\CoreBundle\Tests\Fixtures\Entity\Foo;
use Contao\CoreBundle\Tests\Fixtures\Entity\FooTranslation;
use Contao\PageModel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ContentUrlGeneratorTest extends DoctrineTestCase
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

    /**
     * @dataProvider provideCircularRedirectModels
     *
     * @param class-string $modelClass
     */
    public function testRejectsCircularModelRedirects(string $modelClass, string $routeKey): void
    {
        $content = $this->createModel($modelClass, ['id' => 1]);
        $target = $this->createModel($modelClass, ['id' => 2]);

        $urlGenerator = $this->mockUrlGenerator(null);
        $pageRegistry = $this->mockPageRegistry(null);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $resolver = $this->mockResolver(
            [$content, ContentUrlResult::redirect($target)],
            [$target, ContentUrlResult::redirect($this->createModel($modelClass, ['id' => 1]))],
        );

        $service = new ContentUrlGenerator($urlGenerator, $pageRegistry, $entityManager, [$resolver]);

        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage(\sprintf('Circular reference while generating the URL for "%s".', $routeKey));

        $service->generate($content);
    }

    public static function provideCircularRedirectModels(): iterable
    {
        yield 'page' => [PageModel::class, 'tl_page.1'];
        yield 'article' => [ArticleModel::class, 'tl_article.1'];
    }

    /**
     * @dataProvider provideEntityIdentifiers
     */
    public function testRejectsCircularEntityRedirects(array $fields, array $identifiers, string|null $expectedKey): void
    {
        $content = new \stdClass();
        $target = null === $expectedKey ? $content : clone $content;

        $metadata = $this->createConfiguredMock(ClassMetadata::class, [
            'getIdentifierFieldNames' => $fields,
            'getIdentifierValues' => $identifiers,
            'getTableName' => 'records',
        ]);

        $entityManager = $this->createConfiguredMock(EntityManagerInterface::class, [
            'getClassMetadata' => $metadata,
            'getUnitOfWork' => $this->createMock(UnitOfWork::class),
        ]);

        $resolver = $this->mockResolver([$content, ContentUrlResult::redirect($target)]);
        $service = new ContentUrlGenerator($this->mockUrlGenerator(null), $this->mockPageRegistry(null), $entityManager, [$resolver]);

        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage(\sprintf('Circular reference while generating the URL for "%s".', $expectedKey ?? 'stdClass->'.spl_object_id($content)));

        $service->generate($content);
    }

    public static function provideEntityIdentifiers(): iterable
    {
        yield 'single' => [['id'], ['id' => 0], 'records.0'];
        yield 'composite' => [['language', 'slug'], ['slug' => 'news&events', 'language' => 'en'], 'records.language=en&slug=news%26events'];
        yield 'missing' => [['id'], [], null];
        yield 'incomplete' => [['language', 'slug'], ['language' => 'en'], null];
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRejectsCircularRedirectsWithAnAssociationAsIdentifier(): void
    {
        $record = new Foo();
        $record->id = 7;

        $content = new FooTranslation($record, 'en');
        $target = new FooTranslation(clone $record, 'en');
        $resolver = $this->mockResolver([$content, ContentUrlResult::redirect($target)]);
        $service = new ContentUrlGenerator($this->mockUrlGenerator(null), $this->mockPageRegistry(null), $this->getTestEntityManager(), [$resolver]);

        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage('Circular reference while generating the URL for "foo_translation.record=7&locale=en".');

        $service->generate($content);
    }

    /**
     * @dataProvider provideDistinctEntityIdentifiers
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testResolvesDistinctEntitiesWithAnAssociationAsIdentifier(int|null $id, string $targetLocale): void
    {
        $record = new Foo();

        if (null !== $id) {
            $record->id = $id;
        }

        $content = new FooTranslation($record, 'en');
        $target = new FooTranslation(clone $record, $targetLocale);

        $resolver = $this->mockResolver(
            [$content, ContentUrlResult::redirect($target)],
            [$target, new ContentUrlResult('https://example.org/')],
        );

        $service = new ContentUrlGenerator($this->mockUrlGenerator(null), $this->mockPageRegistry(null), $this->getTestEntityManager(), [$resolver]);

        $this->assertSame('https://example.org/', $service->generate($content));
    }

    public static function provideDistinctEntityIdentifiers(): iterable
    {
        yield 'same record with a different locale' => [7, 'de'];
        yield 'records without an identifier' => [null, 'en'];
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
