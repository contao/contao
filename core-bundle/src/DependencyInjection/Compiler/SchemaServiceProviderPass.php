<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Contao\CoreBundle\Dca\Provider\SchemaProviderInterface;
use Contao\CoreBundle\Dca\Schema\ServiceSubscriberSchemaInterface;
use Contao\CoreBundle\Dca\SchemaFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\TypedReference;

class SchemaServiceProviderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $schemaFactory = $container->getDefinition('contao.dca.schema_factory');
        $providers = $container->findTaggedServiceIds('contao.dca.schema_dependency_provider');
        $schemas = [];

        foreach (array_keys($providers) as $id) {
            $provider = $container->getDefinition($id)->getClass();

            if (!$container->getReflectionClass($provider)->isSubclassOf(SchemaProviderInterface::class)) {
                throw new \LogicException(sprintf('Service "%s" must implement interface "%s".', $provider, SchemaProviderInterface::class));
            }

            /** @var SchemaProviderInterface $provider */
            $schemas[] = $provider::getServiceSubscribingSchemas();
        }

        $schemas = array_merge([], ...$schemas);

        foreach ($schemas as $class) {
            $subscriberMap = [];

            if (!$container->getReflectionClass($class)->isSubclassOf(ServiceSubscriberSchemaInterface::class)) {
                throw new \LogicException(sprintf('Schema "%s" must implement interface "%s".', $class, ServiceSubscriberSchemaInterface::class));
            }

            $services = $class::getSubscribedServices();

            foreach ($services as $key => $type) {
                if (!\is_string($type) || !preg_match('/(?(DEFINE)(?<cn>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+))(?(DEFINE)(?<fqcn>(?&cn)(?:\\\\(?&cn))*+))^\??(?&fqcn)(?:(?:\|(?&fqcn))*+|(?:&(?&fqcn))*+)$/', $type)) {
                    throw new InvalidArgumentException(sprintf('"%s::getSubscribedServices()" must return valid PHP types for service "%s", "%s" returned.', $class, $key, get_debug_type($type)));
                }

                if (\is_int($name = $key)) {
                    $key = $type;
                    $name = null;
                }

                $subscriberMap[$key] = new TypedReference((string) new Reference($type), $type, ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $name);
            }

            $locatorRef = ServiceLocatorTagPass::register($container, $subscriberMap);

            SchemaFactory::addDependency($class);

            $schemaFactory->addTag('container.service_subscriber', [
                'key' => $class,
                'id' => (string) $locatorRef,
            ]);
        }
    }
}
