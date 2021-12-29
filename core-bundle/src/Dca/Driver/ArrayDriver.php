<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Dca\Driver;

abstract class ArrayDriver implements DriverInterface
{
    private array $cache = [];

    public function read(string $resource): array
    {
        return $this->cache[$resource] = $this->getData($resource);
    }

    public function hasChanged(string $resource): bool
    {
        $data = $this->getData($resource);
        $changed = $data !== ($this->cache[$resource] ?? $data);

        $this->cache[$resource] = $data;

        return $changed;
    }

    abstract protected function getData(string $name): array;
}
