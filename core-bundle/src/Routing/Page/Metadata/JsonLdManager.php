<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\Page\Metadata;

use Contao\CoreBundle\Event\JsonLdEvent;

class JsonLdManager
{
    public const SCHEMA_ORG = 'https://schema.org';
    public const SCHEMA_CONTAO = 'https://schema.contao.org';

    /**
     * @var PageMetadataContainer
     */
    private $pageMetadataContainer;

    /**
     * @var array<Graph>
     */
    private $graphs = [];

    public function __construct(PageMetadataContainer $pageMetadataContainer)
    {
        $this->pageMetadataContainer = $pageMetadataContainer;
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

        $this->pageMetadataContainer->getEventDispatcher()->dispatch(new JsonLdEvent($this));

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
}
