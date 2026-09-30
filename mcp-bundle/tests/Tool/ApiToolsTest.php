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
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use Contao\ApiBundle\Http\ApiRequestFactory;
use Contao\McpBundle\Api\ApiOperationRegistry;
use Contao\McpBundle\Response\ApiResponseConverter;
use Contao\McpBundle\Tool\ApiTools;
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
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ApiToolsTest extends TestCase
{
    public function testDispatchesAnyDiscoveredOperationThroughTheApi(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $kernel
            ->expects($this->once())
            ->method('handle')
            ->willReturnCallback(
                function (Request $request, int $type): Response {
                    $this->assertSame(HttpKernelInterface::SUB_REQUEST, $type);
                    $this->assertSame('/contao/api/files_operations/move', $request->getPathInfo());
                    $this->assertSame('POST', $request->getMethod());
                    $this->assertSame('{"source":"a","target":"b"}', $request->getContent());

                    return new Response('{"path":"b"}', 200);
                },
            )
        ;

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_api_files_move', [])
            ->willReturn('/contao/api/files_operations/move')
        ;

        $stack = new RequestStack();
        $stack->push(Request::create('https://example.org/contao/mcp'));

        $tools = new ApiTools($this->createRegistry(), $kernel, new ApiRequestFactory($router), $stack, new ApiResponseConverter());
        $result = $tools->execute('contao_api_files_move', data: ['source' => 'a', 'target' => 'b']);

        $this->assertFalse($result->isError);
        $this->assertSame('b', $result->structuredContent['data']->path);
    }

    public function testRejectsUnknownOperationsBeforeDispatch(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Use contao_api_discover first.');

        $stack = new RequestStack();
        $stack->push(Request::create('/contao/mcp'));

        $tools = new ApiTools($this->createRegistry(), $this->createStub(HttpKernelInterface::class), new ApiRequestFactory($this->createStub(UrlGeneratorInterface::class)), $stack, new ApiResponseConverter());
        $tools->execute('unknown');
    }

    public function testRequiresHttpContextBeforeDispatch(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('require an HTTP request');

        $tools = new ApiTools($this->createRegistry(), $this->createStub(HttpKernelInterface::class), new ApiRequestFactory($this->createStub(UrlGeneratorInterface::class)), new RequestStack(), new ApiResponseConverter());
        $tools->execute('contao_api_files_move');
    }

    public function testToolSchemasSupportGenericApiInputs(): void
    {
        $generator = new SchemaGenerator(new DocBlockParser());
        $tools = [];
        $descriptions = [];

        foreach (new \ReflectionClass(ApiTools::class)->getMethods() as $method) {
            if (!$attributes = $method->getAttributes(McpTool::class)) {
                continue;
            }

            $tool = $attributes[0]->newInstance();
            $tools[$tool->name] = $generator->generate($method);
            $descriptions[] = $tool->description;
        }

        $this->assertSame(['contao_api_discover', 'contao_api_describe', 'contao_api_execute'], array_keys($tools));
        $this->assertSame('object', $tools['contao_api_execute']['properties']['parameters']['type']);
        $this->assertCount(6, $tools['contao_api_execute']['properties']['data']['anyOf']);

        foreach ($descriptions as $description) {
            $this->assertStringContainsString('Last resort', $description);
            $this->assertStringContainsString('Prefer any applicable specific MCP tool', $description);
        }
    }

    private function createRegistry(): ApiOperationRegistry
    {
        $names = $this->createStub(ResourceNameCollectionFactoryInterface::class);
        $names
            ->method('create')
            ->willReturn(new ResourceNameCollection(['File']))
        ;

        $metadata = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $metadata
            ->method('create')
            ->willReturn(new ResourceMetadataCollection('File', [new ApiResource(shortName: 'File', operations: [
                'contao_api_files_move' => new Post(name: 'contao_api_files_move'),
            ])]))
        ;

        return new ApiOperationRegistry($names, $metadata, $this->createStub(OpenApiFactoryInterface::class), $this->createStub(NormalizerInterface::class));
    }
}
