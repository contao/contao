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

interface WebworkerAwareInterface
{
    public function wasDispatchedByWebworker(): bool;

    public function setWasDispatchedByWebworker(bool $wasDispatchedByWebworker): self;
}
