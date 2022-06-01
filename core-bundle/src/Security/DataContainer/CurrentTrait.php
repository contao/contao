<?php

namespace Contao\CoreBundle\Security\DataContainer;

trait CurrentTrait
{
    public function getCurrent(): ?array
    {
        return $this->current;
    }

    public function getCurrentId(): ?string
    {
        return $this->current['id'] ? (string) $this->current['id'] : null;
    }
}
