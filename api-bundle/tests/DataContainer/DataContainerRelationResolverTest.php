<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\DataContainer;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Contao\ApiBundle\DataContainer\DataContainerRelationDefinition;
use Contao\ApiBundle\DataContainer\DataContainerRelationResolver;
use Contao\ApiBundle\Dto\DataContainerRecord;
use Contao\ApiBundle\Relation\RelationReference;
use Contao\CoreBundle\DataContainer\DcaHierarchy;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

final class DataContainerRelationResolverTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $this->connection->executeStatement('CREATE TABLE tl_page (id INTEGER PRIMARY KEY, pid INTEGER NOT NULL DEFAULT 0, alias VARCHAR(64))');
        $this->connection->executeStatement('CREATE TABLE tl_article (id INTEGER PRIMARY KEY, pid INTEGER NOT NULL)');
        $this->connection->executeStatement('INSERT INTO tl_page (id) VALUES (12)');
        $this->connection->executeStatement('INSERT INTO tl_article (id, pid) VALUES (7, 12)');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);
    }

    public function testLeavesUnsupportedDefinitionsUnchanged(): void
    {
        $resolver = $this->createResolver($this->createStub(RouterInterface::class));

        foreach ([new \stdClass(), new DataContainerRelationDefinition('tl_unexposed')] as $relation) {
            $this->assertFalse($resolver->supports($relation));
            $this->assertSame(12, $resolver->resolveToReference(12, $relation));
            $this->assertSame('/custom/12', $resolver->resolveToIdentifier('/custom/12', $relation));
        }
    }

    #[DataProvider('provideIrisWithoutPaths')]
    public function testRejectsIrisWithoutAPath(string $iri): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->never())
            ->method('getContext')
        ;

        $router
            ->expects($this->never())
            ->method('match')
        ;

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('valid IRIs');

        $this->createResolver($router)->resolveToIdentifier(['iri' => $iri], $this->getPageRelation());
    }

    public static function provideIrisWithoutPaths(): iterable
    {
        yield 'host only' => ['https://example.com'];
        yield 'host and query' => ['https://example.com?id=12'];
        yield 'query only' => ['?id=12'];
    }

    public function testRestoresTheRequestMethodWhenRouteMatchingFails(): void
    {
        $context = new RequestContext(method: 'POST');

        $router = $this->createStub(RouterInterface::class);
        $router
            ->method('getContext')
            ->willReturn($context)
        ;

        $router
            ->method('match')
            ->willThrowException(new ResourceNotFoundException())
        ;

        $this->expectException(UnprocessableEntityHttpException::class);

        try {
            $this->createResolver($router)->resolveToIdentifier('/missing/12', $this->getPageRelation());
        } finally {
            $this->assertSame('POST', $context->getMethod());
        }
    }

    public function testResolvesRelationsUsingAnotherIdentifierColumn(): void
    {
        $this->connection->update('tl_page', ['alias' => 'home'], ['id' => 12]);

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('page_get', ['id' => 12])
            ->willReturn('/pages/12')
        ;

        $router
            ->method('match')
            ->willReturn(['_route' => 'page_get', 'id' => '12'])
        ;

        $relation = new DataContainerRelationDefinition('tl_page', 'alias');
        $resolver = $this->createResolver($router);
        $reference = new RelationReference('home', '/pages/12');

        $this->assertEquals(new RelationReference('home', '/pages/12'), $resolver->resolveToReference('home', $relation));
        $this->assertSame('home', $resolver->resolveToIdentifier($reference, $relation));
        $this->assertNull($resolver->resolveToReference('missing', $relation));
    }

    public function testResolvesIdentifiersToResourceIris(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->exactly(3))
            ->method('generate')
            ->willReturnCallback(static fn (string $route, array $parameters): string => match ($route) {
                'page_get' => '/contao/api/dc/page/'.$parameters['id'],
                'article_get' => '/contao/api/dc/page/'.$parameters['page_id'].'/article/'.$parameters['id'],
                default => throw new \LogicException('Unexpected route.'),
            })
        ;

        $resolver = $this->createResolver($router);

        $this->assertEquals(new RelationReference(12, '/contao/api/dc/page/12'), $resolver->resolveToReference(12, $this->getPageRelation()));
        $this->assertEquals(new RelationReference(7, '/contao/api/dc/page/12/article/7'), $resolver->resolveToReference(7, $this->getArticleRelation()));
        $this->assertEquals([new RelationReference(12, '/contao/api/dc/page/12'), null], $resolver->resolveToReference([12, 0], $this->getPageRelation()));

        foreach ([null, '', 0, '0'] as $empty) {
            $this->assertNull($resolver->resolveToReference($empty, $this->getPageRelation()));
            $this->assertNull($resolver->resolveToIdentifier($empty, $this->getPageRelation()));
        }
    }

    public function testResolvesResourceIrisToIdentifiers(): void
    {
        $router = $this->createStub(RouterInterface::class);
        $router
            ->method('match')
            ->willReturnCallback(static fn (string $path): array => match ($path) {
                '/contao/api/dc/page/12' => ['_route' => 'page_get', 'id' => '12'],
                '/contao/api/dc/page/12/article/7' => ['_route' => 'article_get', 'page_id' => '12', 'id' => '7'],
                default => throw new \LogicException('Unexpected path.'),
            })
        ;

        $resolver = $this->createResolver($router);

        $this->assertSame('12', $resolver->resolveToIdentifier(new RelationReference(12, '/contao/api/dc/page/12'), $this->getPageRelation()));
        $this->assertSame('7', $resolver->resolveToIdentifier(['id' => 7, 'iri' => 'https://example.com/contao/api/dc/page/12/article/7'], $this->getArticleRelation()));
        $this->assertSame(['12', null], $resolver->resolveToIdentifier([['@id' => '/contao/api/dc/page/12', 'id' => 12], null], $this->getPageRelation()));
        $this->assertSame('12', $resolver->resolveToIdentifier('/contao/api/dc/page/12', $this->getPageRelation()));
    }

    public function testResolvesRecordsInsideSelfNestingTablesToIris(): void
    {
        $this->connection->executeStatement('CREATE TABLE tl_content (id INTEGER PRIMARY KEY, pid INTEGER NOT NULL, ptable VARCHAR(64) NOT NULL)');
        $this->connection->executeStatement("INSERT INTO tl_content (id, pid, ptable) VALUES (30, 7, 'tl_article'), (31, 30, 'tl_content'), (32, 31, 'tl_content')");

        $rows = [
            30 => ['id' => 30, 'pid' => 7, 'ptable' => 'tl_article'],
            31 => ['id' => 31, 'pid' => 30, 'ptable' => 'tl_content'],
            32 => ['id' => 32, 'pid' => 31, 'ptable' => 'tl_content'],
        ];

        $dcaHierarchy = $this->createStub(DcaHierarchy::class);
        $dcaHierarchy
            ->method('getParentRows')
            ->willReturnCallback(static fn (int|string $id): array => array_reverse(array_values(array_filter($rows, static fn (array $row): bool => $row['id'] <= (int) $id))))
        ;

        $parents = [['table' => 'tl_article', 'parameter' => 'article_id']];

        $content = new ApiResource(
            operations: [
                new Get(name: 'content_get', extraProperties: ['contao' => ['parents' => $parents]]),
                new Get(name: 'content_nested_get', extraProperties: ['contao' => ['parents' => $parents, 'recursive_parent' => ['table' => 'tl_content', 'parameter' => 'nested', 'segment' => 'content']]]),
            ],
            extraProperties: ['contao' => ['table' => 'tl_content']],
        );

        $router = $this->createStub(RouterInterface::class);
        $router
            ->method('generate')
            ->willReturnCallback(static fn (string $route, array $parameters): string => match ($route) {
                'content_get' => '/contao/api/dc/article/'.$parameters['article_id'].'/content/'.$parameters['id'],
                'content_nested_get' => '/contao/api/dc/article/'.$parameters['article_id'].'/content/'.$parameters['nested'].'/content/'.$parameters['id'],
                default => throw new \LogicException('Unexpected route.'),
            })
        ;

        $resolver = $this->createResolver($router, additionalResources: [$content], dcaHierarchy: $dcaHierarchy);

        $this->assertSame('/contao/api/dc/article/7/content/30', $resolver->resolveRecordToIri(new DataContainerRecord('tl_content', [], 30)));
        $this->assertSame('/contao/api/dc/article/7/content/30/content/31', $resolver->resolveRecordToIri(new DataContainerRecord('tl_content', [], 31)));
        $this->assertSame('/contao/api/dc/article/7/content/30/content/31/content/32', $resolver->resolveRecordToIri(new DataContainerRecord('tl_content', [], 32)));
    }

    public function testMatchesRelationIrisAsReadRequests(): void
    {
        $context = new RequestContext(method: 'POST');

        $router = $this->createStub(RouterInterface::class);
        $router
            ->method('getContext')
            ->willReturn($context)
        ;

        $router
            ->method('match')
            ->willReturnCallback(static fn (): array => 'GET' === $context->getMethod() ? ['_route' => 'page_get', 'id' => '12'] : ['_route' => 'page_post'])
        ;

        $resolver = $this->createResolver($router);

        $this->assertSame('12', $resolver->resolveToIdentifier('/contao/api/dc/page/12', $this->getPageRelation()));
        $this->assertSame('POST', $context->getMethod());
    }

    public function testRejectsIrisForAnotherResource(): void
    {
        $router = $this->createStub(RouterInterface::class);
        $router
            ->method('match')
            ->willReturn(['_route' => 'article_get', 'id' => '7'])
        ;

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('unexpected API resource');

        $this->createResolver($router)->resolveToIdentifier('/contao/api/dc/page/12/article/7', $this->getPageRelation());
    }

    public function testInfersTheRecordIdentifierRelation(): void
    {
        $router = $this->createStub(RouterInterface::class);
        $router
            ->method('generate')
            ->willReturn('/contao/api/dc/page/12')
        ;

        $resolver = $this->createResolver($router);

        $this->assertSame('/contao/api/dc/page/12', $resolver->resolveRecordToIri(new DataContainerRecord('tl_page', id: 12)));
    }

    /**
     * @param list<ApiResource> $additionalResources
     */
    private function createResolver(RouterInterface $router, array $additionalResources = [], DcaHierarchy|null $dcaHierarchy = null): DataContainerRelationResolver
    {
        $resources = [
            new ApiResource(
                operations: [new Get(name: 'page_get', extraProperties: ['contao' => ['parents' => []]])],
                extraProperties: ['contao' => ['table' => 'tl_page']],
            ),
            new ApiResource(
                operations: [new Get(name: 'article_get', extraProperties: ['contao' => ['parents' => [['table' => 'tl_page', 'parameter' => 'page_id']]]])],
                extraProperties: ['contao' => ['table' => 'tl_article']],
            ),
            ...$additionalResources,
        ];

        $metadataFactory = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $metadataFactory
            ->method('create')
            ->willReturn(new ResourceMetadataCollection(DataContainerRecord::class, $resources))
        ;

        return new DataContainerRelationResolver($this->connection, $metadataFactory, $router, $dcaHierarchy ?? $this->createStub(DcaHierarchy::class));
    }

    private function getPageRelation(): DataContainerRelationDefinition
    {
        return new DataContainerRelationDefinition('tl_page');
    }

    private function getArticleRelation(): DataContainerRelationDefinition
    {
        return new DataContainerRelationDefinition('tl_article');
    }
}
