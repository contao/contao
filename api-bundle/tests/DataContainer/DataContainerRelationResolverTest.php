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
use Contao\ApiBundle\DataContainer\DataContainerFieldContext;
use Contao\ApiBundle\DataContainer\DataContainerRelationDefinition;
use Contao\ApiBundle\DataContainer\DataContainerRelationReference;
use Contao\ApiBundle\DataContainer\DataContainerRelationResolver;
use Contao\ApiBundle\Dto\DataContainerRecord;
use Contao\ApiBundle\Widget\RelationAwareWidgetConverterInterface;
use Contao\ApiBundle\Widget\WidgetConverterRegistry;
use Contao\CoreBundle\DataContainer\ForeignKeyParser;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

final class DataContainerRelationResolverTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $this->connection->executeStatement('CREATE TABLE tl_page (id INTEGER PRIMARY KEY, pid INTEGER NOT NULL DEFAULT 0)');
        $this->connection->executeStatement('CREATE TABLE tl_article (id INTEGER PRIMARY KEY, pid INTEGER NOT NULL)');
        $this->connection->executeStatement('INSERT INTO tl_page (id) VALUES (12)');
        $this->connection->executeStatement('INSERT INTO tl_article (id, pid) VALUES (7, 12)');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);
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

        $this->assertEquals(new DataContainerRelationReference(12, '/contao/api/dc/page/12'), $resolver->resolveToReference(12, $this->getPageRelation()));
        $this->assertEquals(new DataContainerRelationReference(7, '/contao/api/dc/page/12/article/7'), $resolver->resolveToReference(7, $this->getArticleRelation()));
        $this->assertEquals([new DataContainerRelationReference(12, '/contao/api/dc/page/12'), null], $resolver->resolveToReference([12, 0], $this->getPageRelation()));
        $this->assertNull($resolver->resolveToReference(0, $this->getPageRelation()));
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

        $this->assertSame('12', $resolver->resolveToIdentifier(new DataContainerRelationReference(12, '/contao/api/dc/page/12'), $this->getPageRelation()));
        $this->assertSame('7', $resolver->resolveToIdentifier(['id' => 7, 'iri' => 'https://example.com/contao/api/dc/page/12/article/7'], $this->getArticleRelation()));
        $this->assertSame(['12', null], $resolver->resolveToIdentifier([['@id' => '/contao/api/dc/page/12', 'id' => 12], null], $this->getPageRelation()));
        $this->assertSame('12', $resolver->resolveToIdentifier('/contao/api/dc/page/12', $this->getPageRelation()));
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

    public function testUsesRelationMetadataProvidedByAWidgetConverter(): void
    {
        $config = ['inputType' => 'custom', 'eval' => ['targetTable' => 'tl_page']];

        $converter = $this->createStub(RelationAwareWidgetConverterInterface::class);
        $converter
            ->method('supports')
            ->willReturn(true)
        ;

        $converter
            ->method('getRelation')
            ->willReturnCallback(static fn (array $config): DataContainerRelationDefinition => new DataContainerRelationDefinition($config['eval']['targetTable']))
        ;

        $router = $this->createStub(RouterInterface::class);
        $router
            ->method('generate')
            ->willReturn('/contao/api/dc/page/12')
        ;

        $resolver = $this->createResolver($router, new WidgetConverterRegistry([$converter]));
        $field = new DataContainerFieldContext($config);

        $this->assertTrue($resolver->supports($field));
        $this->assertEquals(new DataContainerRelationReference(12, '/contao/api/dc/page/12'), $resolver->resolveToReference(12, $field));
    }

    public function testInfersPidRelationsFromTheDca(): void
    {
        $GLOBALS['TL_DCA']['tl_article']['config']['ptable'] = 'tl_page';
        $GLOBALS['TL_DCA']['tl_page']['list']['sorting']['mode'] = DataContainer::MODE_TREE;

        $router = $this->createStub(RouterInterface::class);
        $router
            ->method('generate')
            ->willReturn('/contao/api/dc/page/12')
        ;

        $resolver = $this->createResolver($router);

        foreach (['tl_article', 'tl_page'] as $table) {
            $field = new DataContainerFieldContext([], $table, 'pid');

            $this->assertTrue($resolver->supports($field));
            $this->assertEquals(new DataContainerRelationReference(12, '/contao/api/dc/page/12'), $resolver->resolveToReference(12, $field));
        }
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

    public function testInfersDynamicPidRelationsFromTheRow(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['config']['dynamicPtable'] = true;

        $router = $this->createStub(RouterInterface::class);
        $router
            ->method('generate')
            ->willReturn('/contao/api/dc/page/12/article/7')
        ;

        $field = new DataContainerFieldContext([], 'tl_content', 'pid');
        $resolver = $this->createResolver($router);

        $this->assertTrue($resolver->supports($field));
        $this->assertEquals(new DataContainerRelationReference(7, '/contao/api/dc/page/12/article/7'), $resolver->resolveToReference(7, $field, ['ptable' => 'tl_article']));
    }

    private function createResolver(RouterInterface $router, WidgetConverterRegistry|null $converters = null): DataContainerRelationResolver
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
        ];

        $metadataFactory = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $metadataFactory
            ->method('create')
            ->willReturn(new ResourceMetadataCollection(DataContainerRecord::class, $resources))
        ;

        return new DataContainerRelationResolver($this->connection, new ForeignKeyParser($this->connection), $converters ?? new WidgetConverterRegistry([]), $metadataFactory, $router);
    }

    private function getPageRelation(): DataContainerFieldContext
    {
        return new DataContainerFieldContext(['foreignKey' => 'tl_page.title', 'relation' => ['type' => 'hasOne']]);
    }

    private function getArticleRelation(): DataContainerFieldContext
    {
        return new DataContainerFieldContext(['foreignKey' => 'tl_article.title', 'relation' => ['type' => 'hasOne']]);
    }
}
