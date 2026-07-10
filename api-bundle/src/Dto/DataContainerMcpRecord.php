<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Dto;

final class DataContainerMcpRecord
{
    public function __construct(
        public array $data = [],
        public int|string|null $id = null,
    ) {
    }
}
