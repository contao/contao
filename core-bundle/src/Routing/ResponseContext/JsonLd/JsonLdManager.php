<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\ResponseContext\JsonLd;

use Contao\CoreBundle\Event\JsonLdEvent;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Spatie\SchemaOrg\Graph;
use Spatie\SchemaOrg\Type;

class JsonLdManager
{
    public const SCHEMA_ORG = 'https://schema.org';
    public const SCHEMA_CONTAO = 'https://schema.contao.org';

    /**
     * @var ResponseContext
     */
    private $responseContext;

    /**
     * @var array<Graph>
     */
    private $graphs = [];

    public function __construct(ResponseContext $responseContext)
    {
        $this->responseContext = $responseContext;
    }

    public function getGraphForSchema(string $schema): Graph
    {
        $schema = rtrim($schema, '/');

        if (!\array_key_exists($schema, $this->graphs)) {
            $this->graphs[$schema] = new Graph($schema);
        }

        return $this->graphs[$schema];
    }

    public function getGraphs(): array
    {
        return $this->graphs;
    }

    public function collectFinalScriptFromGraphs(): string
    {
        $data = [];

        $this->responseContext->dispatchEvent(new JsonLdEvent());

        foreach ($this->getGraphs() as $graph) {
            $data[] = $graph->toArray();
        }

        // Reset graphs
        $this->graphs = [];

        if (0 === \count($data)) {
            return '';
        }

        return '<script type="application/ld+json">'."\n".json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n".'</script>';
    }

    public function createTypeFromArray(array $jsonLd): Type
    {
        if (!isset($jsonLd['@type'])) {
            throw new \InvalidArgumentException('Must provide the @type property!');
        }

        $schemaClass = '\Spatie\SchemaOrg\\'.$jsonLd['@type'];
        $schema = new $schemaClass();
        unset($jsonLd['@type']);

        foreach ($jsonLd as $k => $v) {
            if (\is_array($v) && isset($v['@type'])) {
                $v = $this->createTypeFromArray($v);
            }

            $schema->setProperty($k, $v);
        }

        return $schema;
    }
}
