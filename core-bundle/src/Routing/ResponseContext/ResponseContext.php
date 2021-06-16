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

use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

final class ResponseContext
{
    public const REQUEST_ATTRIBUTE_NAME = '_contao_response_context';

    /**
     * @var array
     */
    private $services = [];

    /**
     * @var array
     */
    private $current = [];

    /**
     * @var PartialResponseHeaderBag|null
     */
    private $headerBag;

    public function add(object $service): self
    {
        $this->registerService(\get_class($service), $service);

        return $this;
    }

    public function addLazy(string $classname, \Closure $factory): self
    {
        $this->registerService($classname, $factory);

        return $this;
    }

    public function has(string $serviceId): bool
    {
        return isset($this->current[$serviceId]);
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

        $serviceId = $this->current[$serviceId];

        // Lazy load the ones with factories
        if ($this->services[$serviceId] instanceof \Closure) {
            $service = $this->services[$serviceId]();
            $this->services[$serviceId] = $service;
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

    /**
     * @param \Closure|object $objectOrFactory
     */
    private function registerService(string $serviceId, $objectOrFactory): void
    {
        $this->services[$serviceId] = $objectOrFactory;
        $this->current[$serviceId] = $serviceId;

        foreach ($this->getAliases($serviceId) as $alias) {
            $this->current[$alias] = $serviceId;
        }
    }

    private function getAliases(string $classname): array
    {
        $aliases = [];
        $ref = new \ReflectionClass($classname);

        // Automatically add aliases for all interfaces and parents (last one added automatically wins by overriding here)
        foreach ($ref->getInterfaceNames() as $interfaceName) {
            $aliases[] = $interfaceName;
        }

        while ($ref = $ref->getParentClass()) {
            $aliases[] = $ref->getName();
        }

        return $aliases;
    }
}
