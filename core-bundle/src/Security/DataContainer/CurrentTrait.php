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
    public function getCurrent(): array|null
    {
        return $this->current;
    }

    public function getCurrentId(): string|null
    {
        return isset($this->current['id']) ? (string) $this->current['id'] : null;
    }

    public function getCurrentPid(): string|null
    {
        return isset($this->current['pid']) ? (string) $this->current['pid'] : null;
    }
}
