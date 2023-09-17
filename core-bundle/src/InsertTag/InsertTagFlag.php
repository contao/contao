<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\InsertTag;

final class InsertTagFlag
{
    public function __construct(private readonly string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
