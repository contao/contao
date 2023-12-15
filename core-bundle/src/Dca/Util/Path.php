<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Dca\Util;

class Path implements \Stringable
{
    public function __construct(
        private array $parts,
        private readonly string $separator = '.',
    ) {
    }

    public function __toString(): string
    {
        return implode($this->separator, $this->parts);
    }

    public function isEmpty(): bool
    {
        return [] === $this->parts;
    }

    public function shift(): string
    {
        return array_shift($this->parts);
    }
}
