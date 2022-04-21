<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Fragment;

class FragmentRegistry implements FragmentRegistryInterface
{
    private array $fragments = [];

    public function add(string $identifier, FragmentConfig $config): FragmentRegistryInterface
    {
        // Override existing fragments with the same identifier
        $this->fragments[$identifier] = $config;

        return $this;
    }

    public function remove(string $identifier): FragmentRegistryInterface
    {
        unset($this->fragments[$identifier]);

        return $this;
    }

    public function has(string $identifier): bool
    {
        return isset($this->fragments[$identifier]);
    }

    public function get(string $identifier): FragmentConfig|null
    {
        return $this->fragments[$identifier] ?? null;
    }

    public function all(): array
    {
        return $this->fragments;
    }

    public function keys(): array
    {
        return array_keys($this->fragments);
    }
}
