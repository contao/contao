<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Messenger\Message;

trait WebworkerAwareMessageTrait
{
    private bool $wasDispatchedByWebworker = false;

    public function wasDispatchedByWebworker(): bool
    {
        return $this->wasDispatchedByWebworker;
    }

    public function setWasDispatchedByWebworker(bool $wasDispatchedByWebworker): self
    {
        $this->wasDispatchedByWebworker = $wasDispatchedByWebworker;

        return $this;
    }
}
