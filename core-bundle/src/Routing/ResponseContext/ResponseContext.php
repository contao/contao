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

use Contao\CoreBundle\Event\AbstractResponseContextEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ResponseContext
{
    public const REQUEST_ATTRIBUTE_NAME = '_contao_response_context';

    private array $services = [];

    private array $current = [];

    private PartialResponseHeaderBag|null $headerBag = null;

    public function dispatchEvent(AbstractResponseContextEvent $event): void
    {
        if (!$this->has(EventDispatcherInterface::class)) {
            return;
        }

        $event->setResponseContext($this);

        $eventDispatcher = $this->get(EventDispatcherInterface::class);
        $eventDispatcher->dispatch($event);
    }

    public function add(object $service): self
    {
        $this->registerService($service::class, $service);

        return $this;
    }

    public function addLazy(string $classname, \Closure|null $factory = null): self
    {
        $factory ??= fn () => new $classname($this);

        $this->registerService($classname, $factory);

        return $this;
    }

    public function has(string $serviceId): bool
    {
        return isset($this->current[$serviceId]);
    }

    public function isInitialized(string $serviceId): bool
    {
        if (!$this->has($serviceId)) {
            return false;
        }

        return !$this->services[$serviceId] instanceof \Closure;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $serviceId
     *
     * @return T
     */
    public function get(string $serviceId)
    {
        if (!$this->has($serviceId)) {
            throw new \InvalidArgumentException(sprintf('Service "%s" does not exist.', $serviceId));
        }

        $serviceId = $this->current[$serviceId];

        // Lazy load the ones with factories
        if (!$this->isInitialized($serviceId)) {
            $service = $this->services[$serviceId]();
            $this->services[$serviceId] = $service;
        }

        return $this->services[$serviceId];
    }

    public function getHeaderBag(): PartialResponseHeaderBag
    {
        return $this->headerBag ??= new PartialResponseHeaderBag();
    }

    /**
     * @param \Closure|object $objectOrFactory
     */
    private function registerService(string $serviceId, object $objectOrFactory): void
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
