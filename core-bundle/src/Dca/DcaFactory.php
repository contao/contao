<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Dca;

use Contao\CoreBundle\Dca\Driver\DriverCollection;
use Contao\CoreBundle\Dca\Driver\MutableDataDriverInterface;
use Contao\CoreBundle\Dca\Provider\ConfigurationProviderInterface;
use Contao\CoreBundle\Dca\Schema\Dca;

/**
 * @internal Do not use this class in your code; use the "contao.dca.factory" service instead
 */
class DcaFactory
{
    private array $dcas = [];

    public function __construct(
        private readonly SchemaFactory $schemaFactory,
        private readonly DriverCollection $driverCollection,
        private readonly ConfigurationProviderInterface $configurationProvider,
    ) {
    }

    public function get(string $resource): Dca
    {
        if (!isset($this->dcas[$resource])) {
            $this->dcas[$resource] = $this->create($resource);
        }

        return $this->dcas[$resource];
    }

    private function create(string $resource): Dca
    {
        $driver = $this->driverCollection->getDriverForResource($resource);
        $config = $this->configurationProvider->getConfiguration($resource, $driver);

        $data = new Data($config);

        $dca = $this->schemaFactory->createSchema($resource, Dca::class, $data);

        if ($driver instanceof MutableDataDriverInterface) {
            $observer = $driver->getMutableDataObserver($resource);

            $observer->setCallback(
                function (Data $subject) use ($resource, $driver): void {
                    $subject->replace($this->configurationProvider->getConfiguration($resource, $driver));
                },
            );

            $data->attachReadObserver($observer);
        }

        return $dca;
    }
}
