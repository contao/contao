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

interface DriverInterface
{
    /**
     * Read the data for the given source and return a parsed array.
     */
    public function read(string $resource): array;

    /**
     * Returns whether the specified resource is handled by this driver.
     */
    public function handles(string $resource): bool;
}
