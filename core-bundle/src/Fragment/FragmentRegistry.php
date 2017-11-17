<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Fragment;

class FragmentRegistry implements FragmentRegistryInterface
{
    /**
     * @var array
     */
    private $fragments = [];

    /**
     * {@inheritdoc}
     */
    public function add(string $identifier, FragmentConfig $config): FragmentRegistryInterface
    {
        // Override existing fragments with the same identifier
        $this->fragments[$identifier] = $config;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $identifier): FragmentRegistryInterface
    {
        unset($this->fragments[$identifier]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $identifier): bool
    {
        return isset($this->fragments[$identifier]);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $identifier): ?FragmentConfig
    {
        return $this->fragments[$identifier] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->fragments;
    }

    /**
     * {@inheritdoc}
     */
    public function keys(): array
    {
        return array_keys($this->fragments);
    }
}
