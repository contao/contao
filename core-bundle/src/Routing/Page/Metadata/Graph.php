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

/**
 * @internal To be removed once https://github.com/spatie/schema-org/pull/160 is merged
 */
class Graph extends \Spatie\SchemaOrg\Graph
{
    private $context = 'https://schema.org';

    public function __construct(string $context)
    {
        $this->context = $context;
    }

    public function getContext(): string
    {
        return $this->context;
    }
}
