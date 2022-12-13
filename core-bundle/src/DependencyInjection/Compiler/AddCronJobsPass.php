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

use Contao\CoreBundle\Cron\CronJob;
use Cron\CronExpression;
use GuzzleHttp\Promise\PromiseInterface;
use Symfony\Component\Config\Definition\Exception\InvalidDefinitionException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AddCronJobsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('contao.cron')) {
            return;
        }

        $serviceIds = $container->findTaggedServiceIds('contao.cronjob');
        $definition = $container->findDefinition('contao.cron');

        /** @var array<Definition> $sync */
        $sync = [];
        /** @var array<Definition> $async */
        $async = [];

        foreach ($serviceIds as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                if (!isset($attributes['interval'])) {
                    throw new InvalidDefinitionException(sprintf('Missing interval attribute in tagged cron service with service id "%s"', $serviceId));
                }

                $jobDefinition = $container->findDefinition($serviceId);
                $method = $this->getMethod($attributes, $jobDefinition->getClass(), $serviceId);
                $interval = $attributes['interval'];

                // Map interval to expression macros
                $interval = str_replace(
                    ['minutely', 'hourly', 'daily', 'weekly', 'monthly', 'yearly'],
                    ['* * * * *', '@hourly', '@daily', '@weekly', '@monthly', '@yearly'],
                    $interval
                );

                // Validate the cron expression
                if (!CronExpression::isValidExpression($interval)) {
                    throw new InvalidDefinitionException(sprintf('The contao.cronjob definition for service "%s" has an invalid interval expression "%s"', $serviceId, $interval));
                }

                $newDefinition = new Definition(CronJob::class, [new Reference($serviceId), $interval, $method]);

                $reflector = new \ReflectionMethod($jobDefinition->getClass(), $method ?? '__invoke');
                $returnType = $reflector->getReturnType();
                $returnsPromise = $returnType instanceof \ReflectionNamedType && PromiseInterface::class === $returnType->getName();

                if ($returnsPromise) {
                    $async[] = $newDefinition;
                } else {
                    $sync[] = $newDefinition;
                }
            }
        }

        // Add async jobs first, so they will be executed first.
        foreach ($async as $jobDefinition) {
            $definition->addMethodCall('addCronJob', [$jobDefinition]);
        }

        foreach ($sync as $jobDefinition) {
            $definition->addMethodCall('addCronJob', [$jobDefinition]);
        }
    }

    private function getMethod(array $attributes, string $class, string $serviceId): string|null
    {
        $ref = new \ReflectionClass($class);
        $invalid = sprintf('The contao.cronjob definition for service "%s" is invalid. ', $serviceId);

        if (isset($attributes['method'])) {
            if (!$ref->hasMethod($attributes['method'])) {
                $invalid .= sprintf('The class "%s" does not have a method "%s".', $class, $attributes['method']);

                throw new InvalidDefinitionException($invalid);
            }

            if (!$ref->getMethod($attributes['method'])->isPublic()) {
                $invalid .= sprintf('The "%s::%s" method exists but is not public.', $class, $attributes['method']);

                throw new InvalidDefinitionException($invalid);
            }

            return (string) $attributes['method'];
        }

        if ($ref->hasMethod('__invoke')) {
            return null;
        }

        $interval = str_replace('@', '', $attributes['interval']);

        if (!\in_array($interval, ['minutely', 'hourly', 'daily', 'weekly', 'monthly', 'yearly'], true)) {
            $invalid .= 'Either specify a method name or implement the __invoke method.';
        } else {
            $method = 'on'.ucfirst($interval);
            $private = false;

            if ($ref->hasMethod($method)) {
                if ($ref->getMethod($method)->isPublic()) {
                    return $method;
                }

                $private = true;
            }

            if ($private) {
                $invalid .= sprintf('The "%s::%s" method exists but is not public.', $class, $method);
            } else {
                $invalid .= sprintf('Either specify a method name or implement the "%s" or __invoke method.', $method);
            }
        }

        throw new InvalidDefinitionException($invalid);
    }
}
