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
