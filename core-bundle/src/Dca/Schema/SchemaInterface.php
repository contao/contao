<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Dca\Schema;

use Contao\CoreBundle\Dca\Data;

interface SchemaInterface
{
    /**
     * @return mixed|null
     */
    public function get(string $key);

    public function getData(string|null $key = null): Data;

    public function all(): array;

    public function is(string $key, bool $default): bool;

    public function getName(): string;
}
