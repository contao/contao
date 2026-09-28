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

use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\HttpOperation;
use Contao\ApiBundle\Http\ApiRequestFactory;
use Contao\ApiBundle\Resource\DataContainerResourceRegistry;
use Contao\McpBundle\Response\ApiResponseConverter;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\ToolAnnotations;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Serializer\Exception\UnsupportedFormatException;

final class DataContainerTools
{
    public function __construct(
        private readonly DataContainerResourceRegistry $resources,
        private readonly HttpKernelInterface $httpKernel,
        private readonly ApiRequestFactory $requestFactory,
        private readonly RequestStack $requestStack,
        private readonly ApiResponseConverter $responseConverter,
    ) {
    }

    #[McpTool(name: 'contao_dc_discover_resources', description: 'Start here to discover Contao DataContainer (DCA) resources. Optionally filter by name. Use contao_dc_describe_resource before reading or changing a resource. Never guess resource names or fields.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    public function discoverResources(string $query = ''): array
    {
        return $this->resources->discover($query);
    }

    #[McpTool(name: 'contao_dc_describe_resource', description: 'Get the response JSON Schema, supported operations and operation-specific request schemas for a discovered resource. Operations describe API capabilities, not a guarantee of permission on a particular record. Only use supported operations and the corresponding operationSchemas entry for request fields.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    public function describeResource(string $resource): array
    {
        try {
            return $this->resources->describe($resource);
        } catch (\OutOfBoundsException $exception) {
            throw new ToolCallException($exception->getMessage().' Use contao_dc_discover_resources first.', previous: $exception);
        }
    }

    #[McpTool(name: 'contao_dc_list_records', description: 'List records of a discovered resource. Follow the pagination links in the result. Requires the list operation. For child tables, supply parent with id and optionally table.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    public function listRecords(string $resource, #[Schema(minimum: 1)] int $page = 1, #[Schema(type: 'object', additionalProperties: true)] array $parent = []): CallToolResult
    {
        if ($page < 1) {
            throw new ToolCallException('The page must be at least 1.');
        }

        return $this->execute($this->getOperation($resource, 'list'), array_filter(['page' => $page, 'parent' => $parent['id'] ?? null, 'ptable' => $parent['table'] ?? null], static fn ($value) => null !== $value));
    }

    #[McpTool(name: 'contao_dc_read_record', description: 'Read one record using an identifier returned by the API. Requires the read operation.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    public function readRecord(string $resource, int|string $id): CallToolResult
    {
        return $this->execute($this->getOperation($resource, 'read'), ['id' => $id]);
    }

    #[McpTool(name: 'contao_dc_create_record', description: 'Create a record. First describe the resource and supply fields from operationSchemas.create, including required fields. Requires the create operation.', annotations: new ToolAnnotations(destructiveHint: false, openWorldHint: false))]
    public function createRecord(string $resource, #[Schema(type: 'object', additionalProperties: true)] array $data): CallToolResult
    {
        return $this->execute($this->getOperation($resource, 'create'), [], $data);
    }

    #[McpTool(name: 'contao_dc_update_record', description: 'Update a record using JSON Merge Patch. First describe the resource and supply only fields from operationSchemas.update. Use the move operation to change parent or position. Requires the update operation.', annotations: new ToolAnnotations(destructiveHint: true, openWorldHint: false))]
    public function updateRecord(string $resource, int|string $id, #[Schema(type: 'object', additionalProperties: true)] array $data): CallToolResult
    {
        return $this->execute($this->getOperation($resource, 'update'), ['id' => $id], $data);
    }

    #[McpTool(name: 'contao_dc_delete_record', description: 'Delete a record using an identifier returned by the API. Requires the delete operation. Check the resource description before calling.', annotations: new ToolAnnotations(destructiveHint: true, openWorldHint: false))]
    public function deleteRecord(string $resource, int|string $id): CallToolResult
    {
        return $this->execute($this->getOperation($resource, 'delete'), ['id' => $id]);
    }

    #[McpTool(name: 'contao_dc_move_record', description: 'Move or reorder a record using the move operation schema returned by contao_dc_describe_resource. Requires the move operation. Supply a target parent or sibling and position, never a raw sorting value.', annotations: new ToolAnnotations(destructiveHint: true, openWorldHint: false))]
    public function moveRecord(string $resource, int|string $id, #[Schema(type: 'object', additionalProperties: true)] array $data): CallToolResult
    {
        return $this->execute($this->getOperation($resource, 'move'), ['id' => $id], $data);
    }

    private function getOperation(string $resource, string $action): HttpOperation
    {
        try {
            return $this->resources->getOperation($resource, $action);
        } catch (\OutOfBoundsException $exception) {
            throw new ToolCallException($exception->getMessage().' Use contao_dc_discover_resources first.', previous: $exception);
        } catch (OperationNotFoundException $exception) {
            throw new ToolCallException($exception->getMessage().' Use contao_dc_describe_resource to discover its operations.', previous: $exception);
        }
    }

    private function execute(HttpOperation $operation, array $parameters = [], array|null $data = null): CallToolResult
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            throw new ToolCallException('Contao API tools require an HTTP request.');
        }

        try {
            $apiRequest = $this->requestFactory->create($request, $operation, $parameters, $data);
        } catch (UnsupportedFormatException $exception) {
            throw new ToolCallException($exception->getMessage(), previous: $exception);
        }

        // Use the API pipeline so serialization, validation and operation security also
        // apply to internal calls. Authentication belongs to the enclosing request.
        return $this->responseConverter->convert($this->httpKernel->handle($apiRequest, HttpKernelInterface::SUB_REQUEST));
    }
}
