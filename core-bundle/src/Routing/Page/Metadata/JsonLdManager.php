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

class JsonLdManager
{
    public const SCHEMA_ORG = 'https://schema.org';
    public const SCHEMA_CONTAO = 'https://schema.contao.org';

    /**
     * @var array<Graph>
     */
    private array $graphs = [];

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
}
