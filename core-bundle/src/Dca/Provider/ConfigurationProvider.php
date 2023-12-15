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

use Contao\CoreBundle\Dca\DcaConfiguration;
use Contao\CoreBundle\Dca\Driver\DriverCollection;
use Contao\CoreBundle\Dca\Driver\DriverInterface;
use Contao\CoreBundle\Event\Dca\ConfigurationSourceEvent;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ConfigurationProvider implements ConfigurationProviderInterface
{
    public function __construct(
        private readonly DriverCollection $drivers,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function getConfiguration(string $resource, DriverInterface|null $driver = null): array
    {
        $driver ??= $this->drivers->getDriverForResource($resource);
        $configs = [$driver->read($resource)];
        $event = new ConfigurationSourceEvent($resource, $configs, $driver);

        return (new Processor())->processConfiguration(
            new DcaConfiguration($resource),
            $this->eventDispatcher->dispatch($event)->getConfigurations(),
        );
    }
}
