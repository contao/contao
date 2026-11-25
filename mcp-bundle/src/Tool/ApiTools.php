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

use Contao\ApiBundle\Http\ApiRequestFactory;
use Contao\McpBundle\Api\ApiOperationRegistry;
use Contao\McpBundle\Api\BinaryPayloadHandler;
use Contao\McpBundle\Response\ApiResponseConverter;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\ToolAnnotations;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Serializer\Exception\UnsupportedFormatException;

final class ApiTools
{
    public function __construct(
        private readonly ApiOperationRegistry $operations,
        private readonly HttpKernelInterface $httpKernel,
        private readonly ApiRequestFactory $requestFactory,
        private readonly RequestStack $requestStack,
        private readonly ApiResponseConverter $responseConverter,
        private readonly BinaryPayloadHandler $binaryPayloadHandler,
    ) {
    }

    #[McpTool(name: 'contao_api_discover', description: 'Last resort when no purpose-built MCP tool supports the task. Prefer any applicable specific MCP tool. Discover Contao API operations, optionally filtered by operation name, resource or description. Use contao_api_describe before execution and never guess operation names or input fields.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    public function discover(string $query = ''): array
    {
        return $this->operations->discover($query);
    }

    #[McpTool(name: 'contao_api_describe', description: 'Last resort when no purpose-built MCP tool supports the task. Prefer any applicable specific MCP tool. Get the route, parameters, request body and responses for a discovered Contao API operation.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    public function describe(string $operation): array
    {
        try {
            $description = $this->operations->describe($operation);

            if (null !== $transport = $this->binaryPayloadHandler->describe($this->operations->getOperation($operation))) {
                $description['mcpTransport'] = $transport;
            }

            return $description;
        } catch (\OutOfBoundsException $exception) {
            throw new ToolCallException($exception->getMessage().' Use contao_api_discover first.', previous: $exception);
        }
    }

    #[McpTool(name: 'contao_api_execute', description: 'Last resort when no purpose-built MCP tool supports the task. Prefer any applicable specific MCP tool. Execute a discovered Contao API operation through the API pipeline. Supply path and query parameters together in parameters. Supply data only when the operation description declares a request body.', annotations: new ToolAnnotations(destructiveHint: true, openWorldHint: false))]
    public function execute(string $operation, #[Schema(type: 'object', additionalProperties: true)] array $parameters = [], #[Schema(definition: ['anyOf' => [['type' => 'object'], ['type' => 'array'], ['type' => 'string'], ['type' => 'number'], ['type' => 'boolean'], ['type' => 'null']]])] mixed $data = null): CallToolResult
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            throw new ToolCallException('Contao API tools require an HTTP request.');
        }

        try {
            $metadata = $this->operations->getOperation($operation);
            $data = $this->binaryPayloadHandler->decode($metadata, $data);
            $apiRequest = $this->requestFactory->create($request, $metadata, $parameters, $data);
        } catch (\OutOfBoundsException $exception) {
            throw new ToolCallException($exception->getMessage().' Use contao_api_discover first.', previous: $exception);
        } catch (UnsupportedFormatException $exception) {
            throw new ToolCallException($exception->getMessage(), previous: $exception);
        }

        // Use the API pipeline so serialization, validation and operation security also
        // apply to internal calls. Authentication belongs to the enclosing request.
        return $this->responseConverter->convert($this->httpKernel->handle($apiRequest, HttpKernelInterface::SUB_REQUEST));
    }
}
