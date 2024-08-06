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

use Symfony\Component\Config\Definition\Exception\InvalidDefinitionException;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
class DataContainerCallbackPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('contao.listener.data_container_callback')) {
            return;
        }

        if (!$callbacks = $this->getCallbacks($container)) {
            return;
        }

        $definition = $container->getDefinition('contao.listener.data_container_callback');
        $definition->addMethodCall('setCallbacks', [$callbacks]);
    }

    /**
     * @return array<string, array<int, array<string>>>
     */
    private function getCallbacks(ContainerBuilder $container): array
    {
        $callbacks = [];
        $serviceIds = $container->findTaggedServiceIds('contao.callback');

        foreach ($serviceIds as $serviceId => $tags) {
            if ($container->hasAlias($serviceId)) {
                $serviceId = (string) $container->getAlias($serviceId);
            }

            $definition = $container->findDefinition($serviceId);
            $definition->setPublic(true);

            while (!$definition->getClass() && $definition instanceof ChildDefinition) {
                $definition = $container->findDefinition($definition->getParent());
            }

            foreach ($tags as $attributes) {
                $this->addCallback($callbacks, $serviceId, $definition->getClass(), $attributes);
            }
        }

        return $callbacks;
    }

    private function addCallback(array &$callbacks, string $serviceId, string $class, array $attributes): void
    {
        if (!isset($attributes['table'])) {
            throw new InvalidDefinitionException(\sprintf('Missing table attribute in tagged callback service ID "%s"', $serviceId));
        }

        if (!isset($attributes['target'])) {
            throw new InvalidDefinitionException(\sprintf('Missing target attribute in tagged callback service ID "%s"', $serviceId));
        }

        if (
            !str_ends_with($attributes['target'], '_callback')
            && !str_contains((string) $attributes['target'], '.panel_callback.')
            && !\in_array(substr($attributes['target'], -7), ['.wizard', '.xlabel'], true)
        ) {
            $attributes['target'] .= '_callback';
        }

        $priority = (int) ($attributes['priority'] ?? 0);

        $callbacks[$attributes['table']][$attributes['target']][$priority][] = [
            $serviceId,
            $this->getMethod($attributes, $class, $serviceId),
        ];
    }

    private function getMethod(array $attributes, string $class, string $serviceId): string
    {
        $ref = new \ReflectionClass($class);
        $invalid = \sprintf('The contao.callback definition for service "%s" is invalid. ', $serviceId);

        if (isset($attributes['method'])) {
            if (!$ref->hasMethod($attributes['method'])) {
                $invalid .= \sprintf('The class "%s" does not have a method "%s".', $class, $attributes['method']);

                throw new InvalidDefinitionException($invalid);
            }

            if (!$ref->getMethod($attributes['method'])->isPublic()) {
                $invalid .= \sprintf('The "%s::%s" method exists but is not public.', $class, $attributes['method']);

                throw new InvalidDefinitionException($invalid);
            }

            return (string) $attributes['method'];
        }

        $keys = explode('.', (string) $attributes['target']);
        $callback = end($keys);

        if (str_starts_with($callback, 'on')) {
            $callback = substr($callback, 2);
        }

        $method = 'on'.Container::camelize($callback);
        $private = false;

        if ($ref->hasMethod($method)) {
            if ($ref->getMethod($method)->isPublic()) {
                return $method;
            }

            $private = true;
        }

        if ($ref->hasMethod('__invoke')) {
            return '__invoke';
        }

        if ($private) {
            $invalid .= \sprintf('The "%s::%s" method exists but is not public.', $class, $method);
        } else {
            $invalid .= \sprintf('Either specify a method name or implement the "%s" or __invoke method.', $method);
        }

        throw new InvalidDefinitionException($invalid);
    }
}
