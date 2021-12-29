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

class DriverCollection
{
    public function __construct(
        /**
         * @var array<DriverInterface>
         */
        private readonly iterable $drivers,
    ) {
    }

    public function getDriverForResource(string $resource): DriverInterface
    {
        foreach ($this->drivers as $driver) {
            if ($driver->handles($resource)) {
                return $driver;
            }
        }

        throw new \LogicException(sprintf('No driver found to handle resource "%s" in %s.', $resource, implode(', ', array_map(static fn ($driver) => $driver::class, $this->getDriversArray()))));
    }

    public function hasDriverForResource(string $resource): bool
    {
        foreach ($this->drivers as $driver) {
            if ($driver->handles($resource)) {
                return true;
            }
        }

        return false;
    }

    private function getDriversArray(): array
    {
        return $this->drivers instanceof \Traversable ? iterator_to_array($this->drivers) : (array) $this->drivers;
    }
}
