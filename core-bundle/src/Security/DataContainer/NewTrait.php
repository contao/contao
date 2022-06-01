<?php

namespace Contao\CoreBundle\Security\DataContainer;

trait NewTrait
{
    public function getNew(): ?array
    {
        return $this->new;
    }

    public function getNewId(): ?string
    {
        return $this->new['id'] ? (string) $this->new['id'] : null;
    }
}
