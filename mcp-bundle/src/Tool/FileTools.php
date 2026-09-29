<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\Tool;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Contao\ApiBundle\Dto\VirtualFilesystemItem;
use Contao\ApiBundle\Http\ApiRequestFactory;
use Contao\McpBundle\Response\ApiResponseConverter;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\ToolAnnotations;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Serializer\Exception\UnsupportedFormatException;

final class FileTools
{
    private const int MAX_UPLOAD_SIZE = 10 * 1024 * 1024;

    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $metadataFactory,
        private readonly HttpKernelInterface $httpKernel,
        private readonly ApiRequestFactory $requestFactory,
        private readonly RequestStack $requestStack,
        private readonly ApiResponseConverter $responseConverter,
    ) {
    }

    #[McpTool(name: 'contao_files_list', description: 'List files and directories accessible to the current Contao backend user. Use a directory path to limit the result and enable deep only when recursive results are needed.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    public function list(string $path = '', bool $deep = false): CallToolResult
    {
        return $this->execute('contao_api_files_get_collection', array_filter(['path' => $path, 'deep' => $deep], static fn (mixed $value): bool => '' !== $value && false !== $value));
    }

    #[McpTool(name: 'contao_files_inspect', description: 'Inspect a file or directory by path, including its read-only DBAFS UUID in metadata.uuid when available.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    public function inspect(string $path): CallToolResult
    {
        return $this->execute('contao_api_files_get', ['path' => $path]);
    }

    #[McpTool(name: 'contao_files_upload', description: 'Upload up to 10 MiB of base64-encoded file contents to a path. This replaces an existing file at that path. For larger files, stream the raw contents through the contao_api_files_upload API operation (PUT /files/{path}). The response includes metadata.uuid when the destination is synchronized with DBAFS.', annotations: new ToolAnnotations(destructiveHint: true, openWorldHint: false))]
    public function upload(string $path, #[Schema(format: 'byte')] string $contentBase64): CallToolResult
    {
        if (\strlen($contentBase64) > 4 * (int) ceil(self::MAX_UPLOAD_SIZE / 3)) {
            throw $this->createUploadTooLargeException();
        }

        if (false === $content = base64_decode($contentBase64, true)) {
            throw new ToolCallException('The file contents must be valid base64.');
        }

        if (\strlen($content) > self::MAX_UPLOAD_SIZE) {
            throw $this->createUploadTooLargeException();
        }

        return $this->execute('contao_api_files_upload', ['path' => $path], rawContent: $content);
    }

    #[McpTool(name: 'contao_files_move', description: 'Move or rename a file or directory. Both source and destination are filesystem paths.', annotations: new ToolAnnotations(destructiveHint: true, openWorldHint: false))]
    public function move(string $source, string $destination): CallToolResult
    {
        return $this->execute('contao_api_files_move', data: ['source' => $source, 'destination' => $destination]);
    }

    #[McpTool(name: 'contao_files_update_metadata', description: 'Update metadata for a file by path without changing its contents. The UUID is read-only and cannot be changed.', annotations: new ToolAnnotations(destructiveHint: true, openWorldHint: false))]
    public function updateMetadata(string $path, #[Schema(type: 'object', additionalProperties: true)] array $data): CallToolResult
    {
        return $this->execute('contao_api_files_metadata', data: ['path' => $path, 'data' => $data]);
    }

    private function execute(string $operationName, array $parameters = [], array|null $data = null, string|null $rawContent = null): CallToolResult
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            throw new ToolCallException('Contao API tools require an HTTP request.');
        }

        $operation = $this->getOperation($operationName);

        try {
            $apiRequest = null === $rawContent
                ? $this->requestFactory->create($request, $operation, $parameters, $data)
                : $this->requestFactory->createRaw($request, $operation, $parameters, $rawContent, 'application/octet-stream');
        } catch (UnsupportedFormatException $exception) {
            throw new ToolCallException($exception->getMessage(), previous: $exception);
        }

        return $this->responseConverter->convert($this->httpKernel->handle($apiRequest, HttpKernelInterface::SUB_REQUEST));
    }

    private function getOperation(string $name): HttpOperation
    {
        $operations = iterator_to_array($this->metadataFactory->create(VirtualFilesystemItem::class)[0]->getOperations());
        $operation = $operations[$name] ?? null;

        if (!$operation instanceof HttpOperation) {
            throw new ToolCallException(\sprintf('The file operation "%s" is not available.', $name));
        }

        return $operation;
    }

    private function createUploadTooLargeException(): ToolCallException
    {
        return new ToolCallException('MCP uploads are limited to 10 MiB. For larger files, stream the raw contents through the contao_api_files_upload API operation (PUT /files/{path}).');
    }
}
