<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\DataContainer;

final readonly class DataContainerFieldContext
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        public array $config,
        public string|null $table = null,
        public string|null $name = null,
    ) {
    }
}
