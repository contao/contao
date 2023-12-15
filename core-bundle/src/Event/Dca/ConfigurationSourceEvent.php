<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Event\Dca;

use Contao\CoreBundle\Dca\Driver\DriverInterface;

class ConfigurationSourceEvent
{
    public function __construct(
        private readonly string $resource,
        private array $configurations,
        private readonly DriverInterface $driver,
    ) {
    }

    public function appendConfiguration(array $configuration): void
    {
        $this->configurations[] = $configuration;
    }

    public function prependConfiguration(array $configuration): void
    {
        array_unshift($this->configurations, $configuration);
    }

    public function getConfigurations(): array
    {
        return $this->configurations;
    }

    public function getResource(): string
    {
        return $this->resource;
    }

    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }
}
