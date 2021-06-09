<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\ResponseContext;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

final class ResponseContext
{
    public const REQUEST_ATTRIBUTE_NAME = '_contao_response_context';

    /**
     * @var Container
     */
    private $container;

    /**
     * @var PartialResponseHeaderBag|null
     */
    private $headerBag;

    public function __construct()
    {
        $this->container = new Container();
    }

    public function remove(string $serviceId): self
    {
        $this->container->set($serviceId, null);

        return $this;
    }

    public function add(object $service): self
    {
        $this->container->set(\get_class($service), $service);

        // Automatically add aliases for all interfaces (last one added automatically wins by overriding here)
        foreach ((new \ReflectionClass($service))->getInterfaceNames() as $interfaceName) {
            $this->container->set($interfaceName, $service);
        }

        return $this;
    }

    public function has(string $serviceId): bool
    {
        return $this->container->has($serviceId);
    }

    /**
     * @throws ServiceNotFoundException
     *
     * @return mixed
     */
    public function get(string $serviceId)
    {
        return $this->container->get($serviceId, ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE);
    }

    public function getHeaderBag(): PartialResponseHeaderBag
    {
        if (null === $this->headerBag) {
            $this->headerBag = new PartialResponseHeaderBag();
        }

        return $this->headerBag;
    }
}
