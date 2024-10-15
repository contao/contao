<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Event\BackendSearch;

use Contao\CoreBundle\Search\Backend\Hit;

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
