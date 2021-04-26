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
use Spatie\SchemaOrg\Type;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class JsonLdManager
{
    public const SCHEMA_ORG = 'https://schema.org';
    public const SCHEMA_CONTAO = 'https://schema.contao.org';

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var array<Graph>
     */
    private $graphs = [];

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getGraphForSchema(string $schema): Graph
    {
        $schema = rtrim($schema, '/');

        if (!\array_key_exists($schema, $this->graphs)) {
            $this->graphs[$schema] = new Graph($schema); // TODO: To be replaced by original implementation once https://github.com/spatie/schema-org/pull/160 is merged
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

        $this->eventDispatcher->dispatch(new JsonLdEvent($this));

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

    public function createSchemaFromArray(array $jsonLd): Type
    {
        if (!isset($jsonLd['@type'])) {
            throw new \InvalidArgumentException('Must provide the @type property!');
        }

        $schemaClass = '\Spatie\SchemaOrg\\'.$jsonLd['@type'];
        $schema = new $schemaClass();
        unset($jsonLd['@type']);

        foreach ($jsonLd as $k => $v) {
            if (\is_array($v) && isset($v['@type'])) {
                $v = $this->createSchemaFromArray($v);
            }

            $schema->setProperty($k, $v);
        }

        return $schema;
    }
}
