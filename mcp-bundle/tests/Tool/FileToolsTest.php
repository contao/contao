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
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Contao\ApiBundle\Dto\VirtualFilesystemItem;
use Contao\ApiBundle\Http\ApiRequestFactory;
use Contao\McpBundle\Response\ApiResponseConverter;
use Contao\McpBundle\Tool\FileTools;
use Contao\TestCase\ContaoTestCase;
use Mcp\Exception\ToolCallException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class FileToolsTest extends ContaoTestCase
{
    public function testUploadsDecodedContentsThroughTheApi(): void
    {
        $router = $this->createMock(UrlGeneratorInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_api_files_upload', ['path' => 'images/example.png'])
            ->willReturn('/contao/api/files/images/example.png')
        ;

        $kernel = $this->createMock(HttpKernelInterface::class);
        $kernel
            ->expects($this->once())
            ->method('handle')
            ->with(
                $this->callback(static fn (Request $request): bool => 'binary contents' === $request->getContent()
                        && 'application/octet-stream' === $request->headers->get('Content-Type')),
                HttpKernelInterface::SUB_REQUEST,
            )
            ->willReturn(new Response('{"metadata":{"uuid":"171bb68d-0094-4f6c-88f5-9b83c0d01521"}}'))
        ;

        $result = $this->createTools($kernel, $router)->upload('images/example.png', base64_encode('binary contents'));

        $this->assertFalse($result->isError);
        $this->assertSame('171bb68d-0094-4f6c-88f5-9b83c0d01521', $result->structuredContent['data']->metadata->uuid);
    }

    public function testRejectsInvalidBase64(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIs('The file contents must be valid base64.');

        $this->createTools()->upload('example.txt', '*invalid*');
    }

    public function testRejectsUploadsLargerThanTenMebibytes(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIs('MCP uploads are limited to 10 MiB. For larger files, stream the raw contents through the contao_api_files_upload API operation (PUT /files/{path}).');

        $this->createTools()->upload('large.bin', str_repeat('A', 13_981_017));
    }

    public function testForwardsMetadataUpdates(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $kernel
            ->expects($this->once())
            ->method('handle')
            ->with(
                $this->callback(static fn (Request $request): bool => [
                    'path' => 'images/example.jpg',
                    'data' => ['localized' => ['en' => ['title' => 'Example']]],
                ] === json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR)),
                HttpKernelInterface::SUB_REQUEST,
            )
            ->willReturn(new Response('{}'))
        ;

        $this->createTools($kernel)->updateMetadata('images/example.jpg', ['localized' => ['en' => ['title' => 'Example']]]);
    }

    private function createTools(HttpKernelInterface|null $kernel = null, UrlGeneratorInterface|null $router = null): FileTools
    {
        $resource = new ApiResource(operations: [
            'contao_api_files_get_collection' => new GetCollection(name: 'contao_api_files_get_collection'),
            'contao_api_files_get' => new Get(name: 'contao_api_files_get'),
            'contao_api_files_upload' => new Put(name: 'contao_api_files_upload'),
            'contao_api_files_move' => new Post(name: 'contao_api_files_move'),
            'contao_api_files_metadata' => new Post(inputFormats: ['json' => ['application/json']], name: 'contao_api_files_metadata'),
        ]);

        $metadata = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $metadata
            ->method('create')
            ->willReturn(new ResourceMetadataCollection(VirtualFilesystemItem::class, [$resource]))
        ;

        $stack = new RequestStack();
        $stack->push(Request::create('https://example.org/contao/mcp'));

        return new FileTools(
            $metadata,
            $kernel ?? $this->createStub(HttpKernelInterface::class),
            new ApiRequestFactory($router ?? $this->createStub(UrlGeneratorInterface::class)),
            $stack,
            new ApiResponseConverter(),
        );
    }
}
