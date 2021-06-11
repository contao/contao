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
     * @var array
     */
    private $services;

    /**
     * @var PartialResponseHeaderBag|null
     */
    private $headerBag;

    public function add(object $service): self
    {
        $this->services[\get_class($service)] = $service;

        $ref = new \ReflectionClass($service);

        // Automatically add aliases for all interfaces and parents (last one added automatically wins by overriding here)
        foreach ($ref->getInterfaceNames() as $interfaceName) {
            $this->services[$interfaceName] = $service;
        }

        while ($ref = $ref->getParentClass()) {
            $this->services[$ref->getName()] = $service;
        }

        return $this;
    }

    public function has(string $serviceId): bool
    {
        return isset($this->services[$serviceId]);
    }

    /**
     * @template T
     * @psalm-param class-string<T> $serviceId
     * @psalm-return T
     *
     * @throws ServiceNotFoundException
     *
     * @return object
     */
    public function get(string $serviceId)
    {
        if (!$this->has($serviceId)) {
            throw new \InvalidArgumentException(sprintf('Service "%s" does not exist.', $serviceId));
        }

        return $this->services[$serviceId];
    }

    public function getHeaderBag(): PartialResponseHeaderBag
    {
        if (null === $this->headerBag) {
            $this->headerBag = new PartialResponseHeaderBag();
        }

        return $this->headerBag;
    }
}
