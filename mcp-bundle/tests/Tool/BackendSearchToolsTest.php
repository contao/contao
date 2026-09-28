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

use Contao\CoreBundle\Search\Backend\BackendSearch;
use Contao\CoreBundle\Search\Backend\Document;
use Contao\CoreBundle\Search\Backend\Facet;
use Contao\CoreBundle\Search\Backend\Hit;
use Contao\CoreBundle\Search\Backend\Query;
use Contao\CoreBundle\Search\Backend\Result;
use Contao\McpBundle\Tool\BackendSearchTools;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Discovery\DocBlockParser;
use Mcp\Capability\Discovery\SchemaGenerator;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\TestCase;

final class BackendSearchToolsTest extends TestCase
{
    public function testSearchesTheBackendIndex(): void
    {
        $document = new Document('42', 'contao.db.tl_page', 'Home');

        $hit = new Hit($document, 'Home', '/contao?do=page&id=42')
            ->withVisibleType('Pages')
            ->withEditUrl('/contao?do=page&act=edit&id=42')
            ->withBreadcrumbs([['label' => 'Website']])
            ->withContext('Home page')
            ->withMetadata(['published' => true])
        ;

        $backendSearch = $this->createMock(BackendSearch::class);
        $backendSearch
            ->method('isAvailable')
            ->willReturn(true)
        ;

        $backendSearch
            ->expects($this->once())
            ->method('search')
            ->with($this->callback($this->matchesQuery(...)))
            ->willReturn(new Result([$hit], [new Facet('tl_page', 'Pages', 1)], [new Facet('published', 'Published', 1)]))
        ;

        $result = new BackendSearchTools($backendSearch)->search(' home ', 10, ['type' => ' contao.db.tl_page ', 'tag' => ' published ']);

        $this->assertSame('42', $result['hits'][0]['id']);
        $this->assertSame('Pages', $result['hits'][0]['visibleType']);
        $this->assertSame('/contao?do=page&act=edit&id=42', $result['hits'][0]['editUrl']);
        $this->assertSame([['label' => 'Website']], $result['hits'][0]['breadcrumbs']);
        $this->assertSame(['key' => 'tl_page', 'label' => 'Pages', 'count' => 1], $result['typeFacets'][0]);
        $this->assertSame(['key' => 'published', 'label' => 'Published', 'count' => 1], $result['tagFacets'][0]);
    }

    public function testRejectsUnavailableBackendSearch(): void
    {
        $backendSearch = $this->createStub(BackendSearch::class);
        $backendSearch
            ->method('isAvailable')
            ->willReturn(false)
        ;

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('backend search is not available');

        new BackendSearchTools($backendSearch)->search('home');
    }

    public function testRejectsTagWithoutType(): void
    {
        $backendSearch = $this->createStub(BackendSearch::class);
        $backendSearch
            ->method('isAvailable')
            ->willReturn(true)
        ;

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('tag filter requires a type filter');

        new BackendSearchTools($backendSearch)->search('home', filters: ['tag' => 'published']);
    }

    public function testToolSchemaConstrainsTheArguments(): void
    {
        $method = new \ReflectionMethod(BackendSearchTools::class, 'search');
        $attribute = $method->getAttributes(McpTool::class)[0]->newInstance();
        $schema = new SchemaGenerator(new DocBlockParser())->generate($method);

        $this->assertSame('contao_backend_search', $attribute->name);
        $this->assertSame(1, $schema['properties']['keywords']['minLength']);
        $this->assertSame(100, $schema['properties']['limit']['maximum']);
        $this->assertFalse($schema['properties']['filters']['additionalProperties']);
    }

    private function matchesQuery(Query $query): bool
    {
        return 10 === $query->getPerPage()
            && 'home' === $query->getKeywords()
            && 'contao.db.tl_page' === $query->getType()
            && 'published' === $query->getTag();
    }
}
