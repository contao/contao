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

use Contao\ArrayUtil;
use Contao\CoreBundle\Event\JsonLdEvent;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Spatie\SchemaOrg\Graph;
use Spatie\SchemaOrg\Type;

class JsonLdManager
{
    final public const SCHEMA_ORG = 'https://schema.org';
    final public const SCHEMA_CONTAO = 'https://schema.contao.org';

    /**
     * @var array<Graph>
     */
    private array $graphs = [];

    public function __construct(private readonly ResponseContext $responseContext)
    {
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

        if (!$data) {
            return '';
        }

        ArrayUtil::recursiveKeySort($data);

        return '<script type="application/ld+json">'."\n".json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n".'</script>';
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function createSchemaOrgTypeFromArray(array $jsonLd): Type
    {
        if (!isset($jsonLd['@type'])) {
            throw new \InvalidArgumentException('Must provide the @type property!');
        }

        $schemaClass = '\Spatie\SchemaOrg\\'.$jsonLd['@type'];

        if (!class_exists($schemaClass)) {
            throw new \InvalidArgumentException(sprintf('Unknown schema.org type "%s" provided!', $jsonLd['@type']));
        }

        $schema = new $schemaClass();
        unset($jsonLd['@type']);

        foreach ($jsonLd as $k => $v) {
            if (\is_array($v) && isset($v['@type'])) {
                $v = $this->createSchemaOrgTypeFromArray($v);
            }

            $schema->setProperty($k, $v);
        }

        return $schema;
    }
}
