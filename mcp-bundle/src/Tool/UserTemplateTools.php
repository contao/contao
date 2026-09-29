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
use Contao\ApiBundle\Dto\UserTemplate;
use Contao\ApiBundle\Http\ApiRequestFactory;
use Contao\McpBundle\Response\ApiResponseConverter;
use Contao\McpBundle\UserTemplate\UserTemplateImpactAnalyzer;
use Contao\McpBundle\UserTemplate\UserTemplateValidator;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\ToolAnnotations;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Serializer\Exception\UnsupportedFormatException;

final class UserTemplateTools
{
    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $metadataFactory,
        private readonly HttpKernelInterface $httpKernel,
        private readonly ApiRequestFactory $requestFactory,
        private readonly RequestStack $requestStack,
        private readonly ApiResponseConverter $responseConverter,
        private readonly UserTemplateValidator $validator,
        private readonly UserTemplateImpactAnalyzer $impactAnalyzer,
    ) {
    }

    #[McpTool(name: 'contao_template_list_themes', description: 'List valid Contao theme slugs for template operations.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    public function listThemes(): CallToolResult
    {
        return $this->execute('contao_api_user_template_theme_discover');
    }

    #[McpTool(name: 'contao_template_discover', description: 'Discover template identifiers. Optionally filter identifiers by a case-insensitive query and select a theme context.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    public function discover(string $query = '', string|null $theme = null): CallToolResult
    {
        return $this->execute('contao_api_user_template_discover', array_filter(['query' => $query, 'theme' => $theme], static fn ($value): bool => null !== $value && '' !== $value));
    }

    #[McpTool(name: 'contao_template_read', description: 'Read a discovered template, including its source, inheritance chain, diagnostics and currently available operations.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    public function read(string $name, string|null $theme = null): CallToolResult
    {
        return $this->execute('contao_api_user_template_read', array_filter(['name' => $name, 'theme' => $theme], static fn ($value): bool => null !== $value));
    }

    #[McpTool(name: 'contao_template_validate', description: 'Compile proposed complete template code in its template and theme context without saving it.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    public function validate(string $name, string $code, string|null $theme = null): array
    {
        return $this->validator->validate($name, $code, $theme);
    }

    #[McpTool(name: 'contao_template_analyze_impact', description: 'Analyze direct and transitive consumers of a template through theme-aware resolution and Twig AST references. Optionally include the hierarchy of a specific block. Consult contao://template-guidance when choosing an edit target.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    public function analyzeImpact(string $name, string|null $theme = null, string|null $block = null): array
    {
        return $this->impactAnalyzer->analyze($name, $theme, $block);
    }

    #[McpTool(name: 'contao_template_create_override', description: 'Create an editable user override for a discovered template. Read the template first and only call this when the create operation is available.', annotations: new ToolAnnotations(destructiveHint: false, openWorldHint: false))]
    public function createOverride(string $name, string|null $theme = null): CallToolResult
    {
        return $this->execute('contao_api_user_template_operation_create', array_filter(['theme' => $theme], static fn ($value): bool => null !== $value), ['name' => $name, 'parameters' => []]);
    }

    #[McpTool(name: 'contao_template_save', description: 'Replace the complete code of an existing user template. Read contao://template-guidance, analyze impact and validate the proposed code first.', annotations: new ToolAnnotations(destructiveHint: true, openWorldHint: false))]
    public function save(string $name, string $code, string|null $theme = null): CallToolResult
    {
        return $this->execute('contao_api_user_template_operation_save', array_filter(['name' => $name, 'theme' => $theme], static fn ($value): bool => null !== $value), ['code' => $code]);
    }

    #[McpTool(name: 'contao_template_delete_override', description: 'Immediately delete an existing user template override. Read the template first and only call this when delete is available.', annotations: new ToolAnnotations(destructiveHint: true, openWorldHint: false))]
    public function deleteOverride(string $name, string|null $theme = null): CallToolResult
    {
        return $this->execute('contao_api_user_template_operation_delete', array_filter(['name' => $name, 'theme' => $theme], static fn ($value): bool => null !== $value));
    }

    #[McpTool(name: 'contao_template_execute_operation', description: 'Execute a named create or rename operation advertised by contao_template_read. Supply only operation-specific parameters documented by the API.', annotations: new ToolAnnotations(destructiveHint: true, openWorldHint: false))]
    public function executeOperation(string $operation, string $name, #[Schema(type: 'object', additionalProperties: true)] array $parameters = [], string|null $theme = null): CallToolResult
    {
        if (!preg_match('/^(?:create|rename)_[a-z_]+$/', $operation)) {
            throw new ToolCallException('Only advertised create_* and rename_* operations can be executed with this tool.');
        }

        return $this->execute('contao_api_user_template_operation_'.$operation, array_filter(['theme' => $theme], static fn ($value): bool => null !== $value), ['name' => $name, 'parameters' => $parameters]);
    }

    private function execute(string $operationName, array $parameters = [], array|null $data = null): CallToolResult
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            throw new ToolCallException('Contao API tools require an HTTP request.');
        }

        $operation = $this->getOperation($operationName);

        try {
            $apiRequest = $this->requestFactory->create($request, $operation, $parameters, $data);
        } catch (UnsupportedFormatException $exception) {
            throw new ToolCallException($exception->getMessage(), previous: $exception);
        }

        return $this->responseConverter->convert($this->httpKernel->handle($apiRequest, HttpKernelInterface::SUB_REQUEST));
    }

    private function getOperation(string $name): HttpOperation
    {
        $operations = iterator_to_array($this->metadataFactory->create(UserTemplate::class)[0]->getOperations());
        $operation = $operations[$name] ?? null;

        if (!$operation instanceof HttpOperation) {
            throw new ToolCallException(\sprintf('The template operation "%s" is not available.', $name));
        }

        return $operation;
    }
}
