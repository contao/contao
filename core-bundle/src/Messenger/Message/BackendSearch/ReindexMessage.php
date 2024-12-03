<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Messenger\Message\BackendSearch;

use Contao\CoreBundle\Messenger\Message\LowPriorityMessageInterface;

/**
 * @experimental
 */
class ReindexMessage implements LowPriorityMessageInterface
{
    public function __construct(private readonly string|null $updateSince = null)
    {
    }

    public function getUpdateSince(): \DateTimeInterface|null
    {
        if (null === $this->updateSince) {
            return null;
        }

        return new \DateTimeImmutable($this->updateSince);
    }
}
