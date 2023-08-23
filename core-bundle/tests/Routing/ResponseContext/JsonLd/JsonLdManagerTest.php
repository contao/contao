<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing\ResponseContext\JsonLd;

use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use PHPUnit\Framework\TestCase;
use Spatie\SchemaOrg\ImageObject;

class JsonLdManagerTest extends TestCase
{
    public function testGettingGraphForSchema(): void
    {
        $schemaManager = new JsonLdManager(new ResponseContext());
        $graph1 = $schemaManager->getGraphForSchema(JsonLdManager::SCHEMA_ORG);
        $graph2 = $schemaManager->getGraphForSchema(JsonLdManager::SCHEMA_ORG);

        $this->assertSame($graph1, $graph2);
    }

    public function testCanGetGraphForArbitrarySchema(): void
    {
        $schemaManager = new JsonLdManager(new ResponseContext());
        $graph = $schemaManager->getGraphForSchema('https://schema.example.org');

        $this->assertSame('https://schema.example.org', $graph->getContext());
    }

    public function testCanGetAllGraphs(): void
    {
        $schemaManager = new JsonLdManager(new ResponseContext());
        $schemaManager->getGraphForSchema(JsonLdManager::SCHEMA_ORG);
        $schemaManager->getGraphForSchema(JsonLdManager::SCHEMA_CONTAO);

        $this->assertCount(2, $schemaManager->getGraphs());
    }

    public function testCollectFinalScriptFromGraphs(): void
    {
        $schemaManager = new JsonLdManager(new ResponseContext());
        $this->assertSame('', $schemaManager->collectFinalScriptFromGraphs());

        $graph = $schemaManager->getGraphForSchema(JsonLdManager::SCHEMA_ORG);
        $graph->add((new ImageObject())->name('Name')->caption('Caption'));

        $this->assertSame(
            <<<'JSONLD'
                <script type="application/ld+json">
                [
                    {
                        "@context": "https:\/\/schema.org",
                        "@graph": [
                            {
                                "@type": "ImageObject",
                                "caption": "Caption",
                                "name": "Name"
                            }
                        ]
                    }
                ]
                </script>
                JSONLD,
            $schemaManager->collectFinalScriptFromGraphs(),
        );
    }

    public function testCreateSchemaOrgTypeFromArrayWithoutType(): void
    {
        $schemaManager = new JsonLdManager(new ResponseContext());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Must provide the @type property!');

        $schemaManager->createSchemaOrgTypeFromArray(['name' => 'Name']);
    }

    public function testCreateSchemaOrgTypeFromArrayWithInvalidType(): void
    {
        $schemaManager = new JsonLdManager(new ResponseContext());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown schema.org type "Foobar" provided!');

        $schemaManager->createSchemaOrgTypeFromArray([
            '@type' => 'Foobar',
            'name' => 'Name',
        ]);
    }

    public function testCreateSchemaOrgTypeFromArrayWithValidType(): void
    {
        $schemaManager = new JsonLdManager(new ResponseContext());

        $type = $schemaManager->createSchemaOrgTypeFromArray([
            '@type' => 'ImageObject',
            'name' => 'Name',
        ]);

        $this->assertInstanceOf(ImageObject::class, $type);
    }
}
