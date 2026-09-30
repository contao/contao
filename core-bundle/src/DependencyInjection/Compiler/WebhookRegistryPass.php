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

use Contao\CoreBundle\Webhook\Attribute\AsWebhookEvent;
use Contao\CoreBundle\Webhook\Attribute\WebhookProperty;
use Contao\CoreBundle\Webhook\WebhookEventInterface;
use Contao\CoreBundle\Webhook\WebhookEventRegistry;
use Contao\CoreBundle\Webhook\WebhookReceiverRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\Webhook\Client\RequestParserInterface;

final class WebhookRegistryPass implements CompilerPassInterface
{
    private const NAME_PATTERN = '/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/';

    public function process(ContainerBuilder $container): void
    {
        $receiverService = $container->hasDefinition('contao.webhook.receiver_registry') ? 'contao.webhook.receiver_registry' : WebhookReceiverRegistry::class;
        $eventService = $container->hasDefinition('contao.webhook.event_registry') ? 'contao.webhook.event_registry' : WebhookEventRegistry::class;

        if (!$container->hasDefinition($receiverService) || !$container->hasDefinition($eventService)) {
            return;
        }

        $receivers = [];
        $events = [];
        $services = [];
        $eventNames = [];
        $consumers = [];

        foreach ($container->findTaggedServiceIds('remote_event.consumer', true) as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                $consumers[$attributes['consumer'] ?? $serviceId][] = $serviceId;
            }
        }

        foreach ($container->findTaggedServiceIds('contao.webhook_receiver', true) as $tags) {
            foreach ($tags as $attributes) {
                $name = $attributes['name'] ?? '';
                $parser = $attributes['parser'] ?? '';

                if (!\is_string($name) || !preg_match(self::NAME_PATTERN, $name)) {
                    throw new InvalidArgumentException(\sprintf('Webhook receiver name "%s" is invalid.', $name));
                }

                if (isset($receivers[$name])) {
                    throw new InvalidArgumentException(\sprintf('Webhook receiver name "%s" is registered more than once.', $name));
                }

                $consumerIds = array_values(array_unique($consumers[$name] ?? []));

                if (1 !== \count($consumerIds)) {
                    throw new InvalidArgumentException(\sprintf('Webhook receiver "%s" must have exactly one remote event consumer.', $name));
                }

                $consumerId = $consumerIds[0];
                $consumerClass = $container->getDefinition($consumerId)->getClass();

                if (null === $consumerClass || !is_a($consumerClass, ConsumerInterface::class, true)) {
                    throw new InvalidArgumentException(\sprintf('Webhook consumer service "%s" must implement %s.', $consumerId, ConsumerInterface::class));
                }

                if (!\is_string($parser) || !class_exists($parser) || !is_a($parser, RequestParserInterface::class, true) || !$container->has($parser)) {
                    throw new InvalidArgumentException(\sprintf('Webhook parser "%s" must be a registered %s service.', $parser, RequestParserInterface::class));
                }

                $receivers[$name] = ['parser' => $parser];
                $services[$parser] = new Reference($parser);
            }
        }

        foreach ($container->findTaggedServiceIds('contao.webhook_event', true) as $serviceId => $_) {
            $class = $container->getDefinition($serviceId)->getClass();

            if (null === $class || !class_exists($class) || !is_subclass_of($class, WebhookEventInterface::class)) {
                throw new InvalidArgumentException(\sprintf('Webhook event "%s" must implement %s.', $class, WebhookEventInterface::class));
            }

            $eventReflection = new \ReflectionClass($class);
            $attributes = $eventReflection->getAttributes(AsWebhookEvent::class);

            if (!$attributes) {
                throw new InvalidArgumentException(\sprintf('Webhook event "%s" must have the AsWebhookEvent attribute.', $class));
            }

            $metadata = $attributes[0]->newInstance();

            if (!preg_match(self::NAME_PATTERN, $metadata->name)) {
                throw new InvalidArgumentException(\sprintf('Webhook event name "%s" is invalid.', $metadata->name));
            }

            if (isset($eventNames[$metadata->name])) {
                throw new InvalidArgumentException(\sprintf('Webhook event name "%s" is registered more than once.', $metadata->name));
            }

            $eventNames[$metadata->name] = true;
            $events[$class] = [
                'name' => $metadata->name,
                'provider' => $serviceId,
                'properties' => $this->getProperties($eventReflection),
            ];
        }

        $locator = ServiceLocatorTagPass::register($container, $services);
        $container->getDefinition($receiverService)->setArgument(0, $receivers);
        $container->getDefinition($receiverService)->setArgument(1, $locator);
        $container->getDefinition($eventService)->setArgument(0, $events);
    }

    /**
     * @template T of WebhookEventInterface
     *
     * @param \ReflectionClass<T> $class
     */
    private function getProperties(\ReflectionClass $class): array
    {
        $properties = [];
        $constructor = $class->getConstructor();
        $constructorProperties = [];

        if (null === $constructor) {
            return $properties;
        }

        foreach ($constructor->getParameters() as $parameter) {
            $constructorProperties[$parameter->getName()] = true;
            $type = $parameter->getType();
            $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : 'mixed';
            $property = [
                'name' => $parameter->getName(),
                'type' => 'array' === $typeName && $parameter->isDefaultValueAvailable() && [] === $parameter->getDefaultValue() ? 'list' : $typeName,
                'nullable' => $parameter->getType()?->allowsNull(),
                'array' => 'array' === $typeName,
            ];

            $attributes = $parameter->getAttributes(WebhookProperty::class);

            if ($attributes) {
                $property += get_object_vars($attributes[0]->newInstance());
            }

            $properties[] = $property;
        }

        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isReadOnly() || isset($constructorProperties[$property->getName()])) {
                continue;
            }

            $type = $property->getType();
            $properties[] = [
                'name' => $property->getName(),
                'type' => $type instanceof \ReflectionNamedType ? $type->getName() : 'mixed',
                'nullable' => $type?->allowsNull(),
                'array' => $type instanceof \ReflectionNamedType && 'array' === $type->getName(),
            ];
        }

        return $properties;
    }
}
