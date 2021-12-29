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

use Contao\StringUtil;

/**
 * Object representation of a DCA palette.
 */
class Palette
{
    public function __construct(
        private readonly string $name,
        private readonly array $boxes,
    ) {
    }

    public static function createFromString(string $name, string $palette): self
    {
        $boxes = StringUtil::trimsplit(';', $palette);

        return new self($name, $boxes);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getBoxes(): array
    {
        return $this->boxes;
    }
}
