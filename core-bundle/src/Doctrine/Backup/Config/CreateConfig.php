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

class CreateConfig extends AbstractConfig
{
    private int $bufferSize = 104857600; // 100 MB

    public function getBufferSize(): int
    {
        return $this->bufferSize;
    }

    public function withBufferSize(int $bufferSizeInBytes): self
    {
        $new = clone $this;
        $new->bufferSize = $bufferSizeInBytes;

        return $new;
    }
}
