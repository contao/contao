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

use Contao\CoreBundle\Search\Backend\BackendSearch;
use Contao\CoreBundle\Search\Backend\Hit;
use Contao\CoreBundle\Search\Backend\Query;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

final class BackendSearchTools
{
    public function __construct(private readonly BackendSearch $backendSearch)
    {
    }

    #[McpTool(name: 'contao_backend_search', description: 'Search the Contao backend index for records and files the current backend user may access. Optionally filter by a result type and then by one of that type’s tags.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    public function search(#[Schema(minLength: 1)] string $keywords, #[Schema(minimum: 1, maximum: 100)] int $limit = 20, #[Schema(type: 'object', properties: ['type' => ['type' => 'string', 'minLength' => 1], 'tag' => ['type' => 'string', 'minLength' => 1]], additionalProperties: false)] array $filters = []): array
    {
        if (!$this->backendSearch->isAvailable()) {
            throw new ToolCallException('The Contao backend search is not available.');
        }

        $keywords = trim($keywords);

        if ('' === $keywords) {
            throw new ToolCallException('The search keywords must not be empty.');
        }

        if ($limit < 1 || $limit > 100) {
            throw new ToolCallException('The result limit must be between 1 and 100.');
        }

        $type = $this->getFilter($filters, 'type');
        $tag = $this->getFilter($filters, 'tag');

        if ($tag && !$type) {
            throw new ToolCallException('A tag filter requires a type filter.');
        }

        $result = $this->backendSearch->search(new Query($limit, $keywords, $type, $tag));

        return [
            'hits' => array_map(static fn (Hit $hit): array => $hit->toArray(), $result->getHits()),
            'typeFacets' => array_map(static fn ($facet) => $facet->toArray(), $result->getTypeFacets()),
            'tagFacets' => array_map(static fn ($facet) => $facet->toArray(), $result->getTagFacets()),
        ];
    }

    private function getFilter(array $filters, string $name): string|null
    {
        $value = $filters[$name] ?? null;

        if (null === $value) {
            return null;
        }

        if (!\is_string($value)) {
            throw new ToolCallException(\sprintf('The "%s" filter must be a non-empty string.', $name));
        }

        $value = trim($value);

        if ('' === $value) {
            throw new ToolCallException(\sprintf('The "%s" filter must be a non-empty string.', $name));
        }

        return $value;
    }
}
