<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\Tests\Tool;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Contao\ApiBundle\Dto\DataContainerRecord;
use Contao\ApiBundle\Http\ApiRequestFactory;
use Contao\ApiBundle\Resource\DataContainerResourceRegistry;
use Contao\ApiBundle\Schema\DataContainerSchemaFactory;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\McpBundle\Response\ApiResponseConverter;
use Contao\McpBundle\Tool\DataContainerTools;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Discovery\DocBlockParser;
use Mcp\Capability\Discovery\SchemaGenerator;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class DataContainerToolsTest extends TestCase
{
    public function testDiscoversResources(): void
    {
        $this->assertSame(['resources' => [['resource' => 'tl_news', 'title' => 'News']]], $this->createTools()->discoverResources('NEWS'));
    }

    public function testDescribesResources(): void
    {
        $description = $this->createTools()->describeResource('tl_news');

        $this->assertSame('tl_news', $description['resource']);
        $this->assertSame(['list', 'read', 'create', 'update', 'move'], $description['operations']);
    }

    public function testTranslatesUnknownResourceDescriptionsToToolErrors(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Use contao_dc_discover_resources first.');

        $this->createTools()->describeResource('unknown');
    }

    public function testRejectsUnknownResourcesBeforeDispatch(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unknown resource');

        $this->createTools()->readRecord('https://example.org', 1);
    }

    public function testRejectsUnsupportedOperationsBeforeDispatch(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('does not support "delete"');

        $this->createTools()->deleteRecord('tl_news', 1);
    }

    public function testDispatchesUpdatesThroughTheApi(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $kernel
            ->expects($this->once())
            ->method('handle')
            ->willReturnCallback(
                function (Request $request, int $type): Response {
                    $this->assertSame(HttpKernelInterface::SUB_REQUEST, $type);
                    $this->assertSame('/_api/news/42', $request->getPathInfo());
                    $this->assertSame('PATCH', $request->getMethod());
                    $this->assertSame('application/merge-patch+json', $request->headers->get('Content-Type'));
                    $this->assertSame(['title' => 'Updated'], json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR));

                    return new Response('{"id":42,"title":"Updated"}', 200);
                },
            )
        ;
        $router = $this->createMock(UrlGeneratorInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('news_patch', ['id' => 42])
            ->willReturn('/_api/news/42')
        ;
        $stack = new RequestStack();
        $stack->push(Request::create('https://example.org/_mcp/backend'));

        $tools = new DataContainerTools($this->createRegistry(), $kernel, new ApiRequestFactory($router), $stack, new ApiResponseConverter());
        $result = $tools->updateRecord('tl_news', 42, ['title' => 'Updated']);

        $this->assertFalse($result->isError);
        $this->assertSame(200, $result->structuredContent['status']);
        $this->assertSame(42, $result->structuredContent['data']->id);
        $this->assertSame('Updated', $result->structuredContent['data']->title);
    }

    public function testDispatchesMovesThroughTheApi(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $kernel
            ->expects($this->once())
            ->method('handle')
            ->willReturnCallback(
                function (Request $request, int $type): Response {
                    $this->assertSame(HttpKernelInterface::SUB_REQUEST, $type);
                    $this->assertSame('/_api/news/42/move', $request->getPathInfo());
                    $this->assertSame('POST', $request->getMethod());
                    $this->assertSame('application/ld+json', $request->headers->get('Content-Type'));
                    $this->assertSame(['target' => 8, 'position' => 'after'], json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR));

                    return new Response('{"id":42,"title":"Updated"}', 200);
                },
            )
        ;
        $router = $this->createMock(UrlGeneratorInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('news_move', ['id' => 42])
            ->willReturn('/_api/news/42/move')
        ;
        $stack = new RequestStack();
        $stack->push(Request::create('https://example.org/_mcp/backend'));

        $tools = new DataContainerTools($this->createRegistry(), $kernel, new ApiRequestFactory($router), $stack, new ApiResponseConverter());
        $result = $tools->moveRecord('tl_news', 42, ['target' => 8, 'position' => 'after']);

        $this->assertFalse($result->isError);
        $this->assertSame(200, $result->structuredContent['status']);
        $this->assertSame(42, $result->structuredContent['data']->id);
        $this->assertSame('Updated', $result->structuredContent['data']->title);
    }

    public function testRequiresHttpContextBeforeDispatch(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('require an HTTP request');

        $this->createTools()->readRecord('tl_news', 1);
    }

    public function testToolSchemasStayFixedAndDescribeObjects(): void
    {
        $generator = new SchemaGenerator(new DocBlockParser());
        $tools = [];

        foreach (new \ReflectionClass(DataContainerTools::class)->getMethods() as $method) {
            if (!$attributes = $method->getAttributes(McpTool::class)) {
                continue;
            }

            $tools[$attributes[0]->newInstance()->name] = $generator->generate($method);
        }

        $this->assertCount(8, $tools);
        $this->assertSame('object', $tools['contao_dc_create_record']['properties']['data']['type']);
        $this->assertSame('object', $tools['contao_dc_update_record']['properties']['data']['type']);
        $this->assertSame(['resource', 'id', 'data'], $tools['contao_dc_update_record']['required']);
        $this->assertSame(1, $tools['contao_dc_list_records']['properties']['page']['minimum']);
    }

    private function createTools(): DataContainerTools
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $kernel
            ->expects($this->never())
            ->method('handle')
        ;

        return new DataContainerTools($this->createRegistry(), $kernel, new ApiRequestFactory($this->createStub(UrlGeneratorInterface::class)), new RequestStack(), new ApiResponseConverter());
    }

    private function createRegistry(): DataContainerResourceRegistry
    {
        $resources = [new ApiResource(
            shortName: 'News',
            operations: [
                'news_list' => new GetCollection(name: 'news_list'),
                'news_read' => new Get(name: 'news_read'),
                'news_post' => new Post(name: 'news_post'),
                'news_patch' => new Patch(name: 'news_patch'),
                'news_move' => new Post(name: 'news_move', extraProperties: ['contao' => ['action' => 'move']]),
            ],
            extraProperties: ['contao' => ['table' => 'tl_news']],
        )];

        $metadata = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $metadata
            ->method('create')
            ->willReturn(new ResourceMetadataCollection(DataContainerRecord::class, $resources))
        ;

        return new DataContainerResourceRegistry($metadata, new DataContainerSchemaFactory($this->createStub(ContaoFramework::class)));
    }
}
