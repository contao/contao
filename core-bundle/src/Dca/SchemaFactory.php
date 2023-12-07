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

use Contao\CoreBundle\Dca\Schema\ParentAwareSchemaInterface;
use Contao\CoreBundle\Dca\Schema\SchemaInterface;
use Contao\CoreBundle\Dca\Schema\SchemaManagerInterface;
use Contao\CoreBundle\Dca\Schema\ServiceSubscriberSchemaInterface;
use Contao\CoreBundle\Dca\Schema\ValidatingSchemaInterface;
use Contao\CoreBundle\Event\Dca\SchemaCreatedEvent;
use Contao\CoreBundle\Event\Dca\SchemaCreatingEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * @internal Do not use this class in your code; use the "contao.dca.schema_factory" service instead
 */
class SchemaFactory implements ServiceSubscriberInterface
{
    private readonly Container $container;

    private readonly ContaoFramework $framework;

    private readonly EventDispatcherInterface|null $eventDispatcher;

    private readonly ContainerInterface $serviceLocator;

    private static array $dependencies = [];

    public function __construct(ContainerInterface $serviceLocator)
    {
        $this->container = $serviceLocator->get('service_container');
        $this->framework = $serviceLocator->get('contao.framework');
        $this->eventDispatcher = $serviceLocator->get('event_dispatcher');

        $this->serviceLocator = $serviceLocator;
    }

    /**
     * @template T of SchemaInterface
     *
     * @param class-string<T> $className
     *
     * @return T
     */
    public function createSchema(string $name, string $className, Data $data, SchemaInterface|null $parent = null, bool $triggerValidation = false): SchemaInterface
    {
        if (!is_a($className, SchemaInterface::class, true)) {
            throw new \InvalidArgumentException(sprintf('Class %s must implement SchemaInterface.', $className));
        }

        if ($this->eventDispatcher) {
            $this->eventDispatcher->dispatch(new SchemaCreatingEvent($data, $className, $name, $parent))->getSchema();
        }

        $schema = new $className($name, $data);

        $this->addSchemaDependencies($schema, $parent, $className);

        if ($this->eventDispatcher) {
            /** @var T $schema */
            $schema = $this->eventDispatcher->dispatch(new SchemaCreatedEvent($schema, $triggerValidation))->getSchema();
        }

        if ($schema instanceof ValidatingSchemaInterface) {
            $schema->validate();
        }

        return $schema;
    }

    public static function getSubscribedServices(): array
    {
        return [
            ...self::$dependencies,
            'service_container' => ContainerInterface::class,
            'contao.framework' => ContaoFramework::class,
            'event_dispatcher' => EventDispatcherInterface::class,
        ];
    }

    public static function addDependency(string $service): void
    {
        self::$dependencies[] = $service;
    }

    private function addSchemaDependencies(SchemaInterface $schema, SchemaInterface|null $parent, string $className): void
    {
        if ($schema instanceof ParentAwareSchemaInterface) {
            $schema->setParent($parent);
        }

        if ($schema instanceof SchemaManagerInterface) {
            $schema->setSchemaFactory($this);
        }

        if ($schema instanceof ContainerAwareInterface) {
            $schema->setContainer($this->container);
        }

        if ($schema instanceof FrameworkAwareInterface) {
            $schema->setFramework($this->framework);
        }

        if ($schema instanceof ServiceSubscriberSchemaInterface) {
            $schema->setLocator($this->serviceLocator->get($className));
        }
    }
}
