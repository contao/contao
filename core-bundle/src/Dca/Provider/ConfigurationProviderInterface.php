<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Dca\Provider;

use Contao\CoreBundle\Dca\Driver\DriverInterface;

interface ConfigurationProviderInterface
{
    /**
     * Get the configuration array for the given resource, optionally using the provided driver.
     */
    public function getConfiguration(string $resource, DriverInterface|null $driver = null): array;
}
