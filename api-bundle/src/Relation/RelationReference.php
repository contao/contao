<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Relation;

readonly class RelationReference
{
    public function __construct(
        public int|string $id,
        public string $iri,
    ) {
    }
}
