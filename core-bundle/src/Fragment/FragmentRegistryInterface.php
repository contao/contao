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

interface FragmentRegistryInterface
{
    /**
     * Adds a fragment or overwrites an existing fragment with the same identifier.
     *
     * @param string         $identifier
     * @param FragmentConfig $config
     *
     * @return FragmentRegistryInterface
     */
    public function add(string $identifier, FragmentConfig $config): self;

    /**
     * Removes a fragment.
     *
     * @param string $identifier
     *
     * @return FragmentRegistryInterface
     */
    public function remove(string $identifier): self;

    /**
     * Checks whether the registry has a fragment.
     *
     * @param string $identifier
     *
     * @return bool
     */
    public function has(string $identifier): bool;

    /**
     * Returns a fragment by its identifier.
     *
     * @param string $identifier
     *
     * @return FragmentConfig|null
     */
    public function get(string $identifier): ?FragmentConfig;

    /**
     * Returns all fragment identifiers.
     *
     * @return string[]
     */
    public function keys(): array;

    /**
     * Returns all fragments.
     *
     * @return FragmentConfig[]
     */
    public function all(): array;
}
