<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Doctrine\Backup\Config;

class RestoreConfig extends AbstractConfig
{
    private bool $ignoreOriginCheck = false;

    public function ignoreOriginCheck(): bool
    {
        return $this->ignoreOriginCheck;
    }

    public function withIgnoreOriginCheck(bool $ignoreOriginCheck): self
    {
        $new = clone $this;
        $new->ignoreOriginCheck = $ignoreOriginCheck;

        return $new;
    }
}
