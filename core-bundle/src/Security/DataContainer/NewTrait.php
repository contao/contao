<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\DataContainer;

trait NewTrait
{
    public function getNew(): array|null
    {
        return $this->new;
    }

    public function getNewId(): string|null
    {
        return isset($this->new['id']) ? (string) $this->new['id'] : null;
    }

    public function getNewPid(): string|null
    {
        return isset($this->new['pid']) ? (string) $this->new['pid'] : null;
    }
}
