<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\Tests\Api;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use Contao\McpBundle\Api\ApiOperationRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ApiOperationRegistryTest extends TestCase
{
    public function testDiscoversAllApiOperationsFromMetadata(): void
    {
        $registry = $this->createRegistry();

        $this->assertSame(
            [
                'operations' => [[
                    'operation' => 'contao_api_user_template_read',
                    'method' => 'GET',
                    'resource' => 'UserTemplate',
                    'description' => 'Read a user template.',
                ]],
            ],
            $registry->discover('template'),
        );

        $this->assertCount(2, $registry->discover()['operations']);
    }

    public function testDescribesAnOperationFromOpenApiAndResolvesReferences(): void
    {
        $description = $this->createRegistry()->describe('contao_api_dc_news_get');

        $this->assertSame('contao_api_dc_news_get', $description['operation']);
        $this->assertSame('News', $description['resource']);
        $this->assertSame('GET', $description['method']);
        $this->assertSame('/contao/api/dc/news/{id}', $description['path']);
        $this->assertSame('integer', $description['parameters'][0]['schema']['type']);
        $this->assertSame('string', $description['responses']['200']['content']['application/json']['schema']['properties']['title']['type']);
    }

    public function testRejectsUnknownOperations(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Unknown API operation "unknown".');

        $this->createRegistry()->getOperation('unknown');
    }

    private function createRegistry(): ApiOperationRegistry
    {
        $names = $this->createStub(ResourceNameCollectionFactoryInterface::class);
        $names
            ->method('create')
            ->willReturn(new ResourceNameCollection(['ApiResource']))
        ;

        $metadata = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $metadata
            ->method('create')
            ->willReturn(new ResourceMetadataCollection('ApiResource', [
                new ApiResource(shortName: 'News', operations: [
                    'contao_api_dc_news_get' => new Get(name: 'contao_api_dc_news_get', description: 'Read news.'),
                ]),
                new ApiResource(shortName: 'UserTemplate', operations: [
                    'contao_api_user_template_read' => new Get(name: 'contao_api_user_template_read', description: 'Read a user template.'),
                ]),
            ]))
        ;

        $openApiFactory = $this->createStub(OpenApiFactoryInterface::class);
        $openApiFactory
            ->method('__invoke')
            ->willReturn(new OpenApi(new Info('Contao', '1.0'), [], new Paths()))
        ;

        $normalizer = $this->createStub(NormalizerInterface::class);
        $normalizer
            ->method('normalize')
            ->willReturn([
                'paths' => [
                    '/contao/api/dc/news/{id}' => [
                        'get' => [
                            'operationId' => 'contao_api_dc_news_get',
                            'parameters' => [['name' => 'id', 'schema' => ['$ref' => '#/components/schemas/Identifier']]],
                            'responses' => ['200' => ['content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/News']]]]],
                        ],
                    ],
                ],
                'components' => ['schemas' => [
                    'Identifier' => ['type' => 'integer'],
                    'News' => ['type' => 'object', 'properties' => ['title' => ['type' => 'string']]],
                ]],
            ])
        ;

        return new ApiOperationRegistry($names, $metadata, $openApiFactory, $normalizer);
    }
}
