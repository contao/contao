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

use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\InsertTagSubscription;
use Contao\CoreBundle\InsertTag\ParsedInsertTag;
use Contao\CoreBundle\InsertTag\ParsedSequence;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Symfony\Component\Config\Definition\Exception\InvalidDefinitionException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AddInsertTagsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('contao.insert_tag.parser')) {
            return;
        }

        $definition = $container->findDefinition('contao.insert_tag.parser');

        foreach (['contao.insert_tag', 'contao.block_insert_tag'] as $serviceTag) {
            $serviceIds = $container->findTaggedServiceIds($serviceTag);
            $subscriptions = [];
            $priorities = [];

            foreach ($serviceIds as $serviceId => $tags) {
                foreach ($tags as $attributes) {
                    if (!preg_match('/^[a-z\x80-\xFF][a-z0-9_\x80-\xFF]*$/i', (string) ($attributes['name'] ?? ''))) {
                        throw new InvalidDefinitionException(sprintf('Invalid insert tag name "%s"', $attributes['name'] ?? ''));
                    }

                    if (!preg_match('/^[a-z\x80-\xFF][a-z0-9_\x80-\xFF]*$|^$/i', (string) ($attributes['endTag'] ?? ''))) {
                        throw new InvalidDefinitionException(sprintf('Invalid insert tag end tag name "%s"', $attributes['endTag'] ?? ''));
                    }

                    $class = $container->findDefinition($serviceId)->getClass();
                    $method = $this->getMethod($attributes['method'], $serviceTag, $class, $serviceId);
                    $attributes['resolveNestedTags'] ??= $this->getResolveNestedTagsFromMethod($class, $method);

                    $subscriptions[] = new Definition(
                        InsertTagSubscription::class,
                        [
                            new Reference($serviceId),
                            $method,
                            $attributes['name'],
                            $attributes['endTag'] ?? null,
                            $attributes['resolveNestedTags'],
                            $attributes['asFragment'] ?? false,
                        ],
                    );

                    $priorities[] = $attributes['priority'];
                }
            }

            // Order by priorities
            array_multisort($priorities, $subscriptions);

            foreach ($subscriptions as $subscription) {
                $definition->addMethodCall('contao.block_insert_tag' === $serviceTag ? 'addBlockSubscription' : 'addSubscription', [$subscription]);
            }
        }

        $serviceIds = $container->findTaggedServiceIds('contao.insert_tag_flag');
        $flags = [];
        $priorities = [];

        foreach ($serviceIds as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                if (!preg_match('/^[a-z\x80-\xFF][a-z0-9_\x80-\xFF]*$/i', (string) ($attributes['name'] ?? ''))) {
                    throw new InvalidDefinitionException(sprintf('Invalid insert tag flag name "%s"', $attributes['name'] ?? ''));
                }

                $method = $this->getMethod($attributes['method'], 'contao.insert_tag_flag', $container->findDefinition($serviceId)->getClass(), $serviceId);

                $flags[] = [
                    $attributes['name'],
                    new Reference($serviceId),
                    $method,
                ];

                $priorities[] = $attributes['priority'];
            }
        }

        // Order by priorities
        array_multisort($priorities, $flags);

        foreach ($flags as $flag) {
            $definition->addMethodCall('addFlagCallback', $flag);
        }
    }

    private function getMethod(string|null $method, string $serviceTag, string $class, string $serviceId): string
    {
        $ref = new \ReflectionClass($class);
        $invalid = sprintf('The %s definition for service "%s" is invalid. ', $serviceTag, $serviceId);

        $method = $method ?: '__invoke';

        if (!$ref->hasMethod($method)) {
            $invalid .= sprintf('The class "%s" does not have a method "%s".', $class, $method);

            throw new InvalidDefinitionException($invalid);
        }

        $ref = $ref->getMethod($method);

        if (!$ref->isPublic()) {
            $invalid .= sprintf('The "%s::%s" method exists but is not public.', $class, $method);

            throw new InvalidDefinitionException($invalid);
        }

        $expectedReturnType = 'contao.block_insert_tag' === $serviceTag ? ParsedSequence::class : InsertTagResult::class;

        if ($expectedReturnType !== (string) $ref->getReturnType()) {
            $invalid .= sprintf('The "%s::%s" method exists but has an invalid return type. Expected "%s", got "%s".', $class, $method, $expectedReturnType, (string) $ref->getReturnType());

            throw new InvalidDefinitionException($invalid);
        }

        return $method;
    }

    /**
     * @param class-string $class
     */
    private function getResolveNestedTagsFromMethod(string $class, string $method): bool
    {
        return match ($type = (string) (((new \ReflectionMethod($class, $method))->getParameters()[0] ?? null)?->getType() ?? 'NULL')) {
            ResolvedInsertTag::class => true,
            ParsedInsertTag::class => false,
            default => throw new InvalidDefinitionException(sprintf('The "%s::%s" method has an invalid parameter type. Expected "%s" or "%s", got "%s".', $class, $method, ResolvedInsertTag::class, ParsedInsertTag::class, $type)),
        };
    }
}
