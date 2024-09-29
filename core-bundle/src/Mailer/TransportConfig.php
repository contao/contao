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
    private string $name;
    private ?string $from;

    public function __construct(string $name, ?string $from = null)
    {
        $this->name = $name;
        $this->from = $from;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFrom(): ?string
    {
        return $this->from;
    }
}
