<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Event\BackendSearch;

use Contao\CoreBundle\Search\Backend\Hit;

/**
 * @experimental
 */
final class EnhanceHitEvent
{
    public function __construct(private Hit|null $hit)
    {
    }

    public function setHit(Hit|null $hit): self
    {
        $this->hit = $hit;

        return $this;
    }

    public function getHit(): Hit|null
    {
        return $this->hit;
    }
}
