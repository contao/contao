<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DataContainer;

/**
 * Interface DefinitionInterface
 *
 * @package Contao\CoreBundle\DataContainer
 */
interface DefinitionInterface
{
    /**
     * Get the name of the definition. The name is usually the table name.
     */
    public function getName(): string;

    /**
     * Check if a configuration path exist.
     */
    public function has(array $path): bool;

    /**
     * Get an value from a path. Return default of value does not exist.
     */
    public function get(array $path, $default = null);

    /**
     * Set a value to a specific path. Each node in the path have to be an array or must not exist.
     */
    public function set(array $path, $value);

    /**
     * Get the value from a path and modify it with an callback. The callback has to return the modified value.
     */
    public function modify($path, callable $callback): void;
}
