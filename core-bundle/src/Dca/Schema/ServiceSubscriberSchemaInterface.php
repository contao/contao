<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Dca\Schema;

use Psr\Container\ContainerInterface;

interface ServiceSubscriberSchemaInterface
{
    /**
     * Set the service locator with all subscribed services.
     */
    public function setLocator(ContainerInterface $locator): void;

    /**
     * Returns an array of service types required by such instances, optionally keyed by the service names used internally.
     *
     *  * ['logger' => 'Psr\Log\LoggerInterface'] means the objects use the "logger" name
     *    internally to fetch a service which must implement Psr\Log\LoggerInterface.
     *  * ['loggers' => 'Psr\Log\LoggerInterface[]'] means the objects use the "loggers" name
     *    internally to fetch an iterable of Psr\Log\LoggerInterface instances.
     *  * ['Psr\Log\LoggerInterface'] is a shortcut for
     *  * ['Psr\Log\LoggerInterface' => 'Psr\Log\LoggerInterface']
     *
     * @return array<string> The required service types, optionally keyed by service names
     */
    public static function getSubscribedServices(): array;
}
