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
use Spatie\SchemaOrg\MultiTypedEntity;
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

        // Reset the graphs
        $this->graphs = [];

        if (!$data) {
            return '';
        }

        ArrayUtil::recursiveKeySort($data);

        $return = [];

        // Create one <script> block per JSON-LD context (see #6401)
        foreach ($data as $context) {
            $return[] = \sprintf(
                "<script type=\"application/ld+json\">\n%s\n</script>",
                json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            );
        }

        return implode("\n", $return);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function createSchemaOrgTypeFromArray(array $jsonLd): Type
    {
        if (!isset($jsonLd['@type'])) {
            throw new \InvalidArgumentException('Must provide the @type property!');
        }

        if (\is_array($jsonLd['@type'])) {
            if ([] !== array_filter($jsonLd['@type'], static fn ($type) => !\is_string($type))) {
                throw new \InvalidArgumentException('The @type property must be a string or an array of strings!');
            }

            $schema = new MultiTypedEntity();

            foreach ($jsonLd['@type'] as $type) {
                $schema->add($this->buildInstance($type, $jsonLd));
            }
        } else {
            $schema = $this->buildInstance($jsonLd['@type'], $jsonLd);
        }

        return $schema;
    }

    private function buildInstance(string $type, array $jsonLd = []): mixed
    {
        $schemaClass = '\Spatie\SchemaOrg\\'.$type;

        if (!class_exists($schemaClass)) {
            throw new \InvalidArgumentException(\sprintf('Unknown schema.org type "%s" provided!', $type));
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
