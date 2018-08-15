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

interface FragmentRegistryInterface
{
    /**
     * Adds a fragment or overwrites an existing fragment with the same identifier.
     *
     * @return FragmentRegistryInterface
     */
    public function add(string $identifier, FragmentConfig $config): self;

    /**
     * Removes a fragment.
     *
     * @return FragmentRegistryInterface
     */
    public function remove(string $identifier): self;

    /**
     * Checks whether the registry has a fragment.
     */
    public function has(string $identifier): bool;

    /**
     * Returns a fragment by its identifier.
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
