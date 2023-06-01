<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Mailer;

final class TransportConfig
{
    public function __construct(
        private string $name,
        private string|null $from = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFrom(): string|null
    {
        return $this->from;
    }
}
