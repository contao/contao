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
 * The definition manager is responsible to manage all data container definitions.
 */
interface DefinitionManagerInterface
{
    /**
     * Get the data container definition by name.
     */
    public function getDefinition(string $name, bool $ignoreCache = false): DefinitionInterface;

    /**
     * Check if definition exist.
     */
    public function hasDefinition(string $name): bool;
}
