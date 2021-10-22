<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Doctrine\Dumper\Config;

class ImportConfig extends AbstractConfig
{
    private bool $truncate = false;

    public function mustTruncate(): bool
    {
        return $this->truncate;
    }

    public function withMustTruncate(bool $truncate): self
    {
        $new = clone $this;
        $new->truncate = $truncate;

        return $new;
    }
}
