<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Functional;

use Contao\TestCase\FunctionalTestCase;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Tag\TaggedValue;
use Symfony\Component\Yaml\Yaml;

class ServiceArgumentsTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        static::bootKernel();
    }

    /**
     * @dataProvider serviceProvider
     */
    public function testServices(string $serviceId, array $config): void
    {
        $container = $this->getContainer();

        if (!$container->has($serviceId)) {
            $this->markTestSkipped(sprintf('Service "%s" was removed.', $serviceId));
        }

        $ref = new \ReflectionClass($config['class']);

        if (!$constructor = $ref->getConstructor()) {
            $this->markTestSkipped(sprintf('Service %s does not have a constructor method', $serviceId));
        }

        $arguments = $config['arguments'] ?? [];

        $this->assertGreaterThanOrEqual(
            $this->countRequiredParameters($constructor),
            $config['arguments'] ?? [],
            sprintf('Service %s does not have the necessary amount of parameters.', $serviceId)
        );

        foreach ($constructor->getParameters() as $i => $parameter) {
            if (!\array_key_exists($i, $arguments)) {
                $this->assertTrue($parameter->isOptional(), sprintf('Missing argument %s on service ID "%s".', $i, $serviceId));

                continue;
            }

            $argument = $arguments[$i];
            $type = $parameter->getType();

            if (null === $argument) {
                if (!$parameter->allowsNull()) {
                    $this->addWarning(sprintf('Argument %s ($%s) of %s does not allow NULL, assuming this parameter is set at runtime.', $i, $parameter->getName(), $serviceId));
                }

                continue;
            }

            if ($argument instanceof TaggedValue) {
                switch ($argument->getTag()) {
                    case 'service_closure':
                        $this->assertSame('Closure', $type->getName(), sprintf('Argument %s of %s should be \Closure but found %s.', $i, $serviceId, $type->getName()));
                        break;

                    case 'tagged_iterator':
                        $this->assertSame('iterable', $type->getName(), sprintf('Argument %s of %s is not an iterable', $i, $serviceId));
                        break;

                    case 'tagged_locator':
                    case 'service_locator':
                        $this->assertSame(PsrContainerInterface::class, $type->getName(), sprintf('Argument %s of %s should be %s but found %s.', $i, $serviceId, PsrContainerInterface::class, $type->getName()));
                        break;

                    default:
                        $this->addWarning(sprintf('Unknown tagged type "%s" for argument %s ($%s) of service %s.', $parameter->getType(), $i, $parameter->getName(), $serviceId));
                }
                continue;
            }

            if (!\is_string($argument)) {
                if (!$type instanceof \ReflectionNamedType) {
                    // cannot validate argument without known type
                    continue;
                }

                $this->assertTrue($type->isBuiltin() ?? false, sprintf('Argument %s of "%s" should be a built-in type, got "%s".', $i, $serviceId, get_debug_type($argument)));

                if ('iterable' === $type->getName()) {
                    $this->assertTrue(is_iterable($argument), sprintf('Argument %s of "%s" is not an iterable.', $i, $serviceId));

                    continue;
                }

                $this->assertSame(get_debug_type($argument), $type->getName());
                continue;
            }

            if ($type instanceof \ReflectionNamedType && ('@.inner' === $argument || str_ends_with($argument, '.inner'))) {
                $this->assertTrue(is_a($config['class'], $type->getName(), true), sprintf('Argument %s of "%s" should be "%s", got "%s".', $i, $serviceId, $type->getName(), $config['class']));

                continue;
            }

            if ('@' === ($argument[0] ?? '')) {
                $optional = '?' === $argument[1];
                $service = $container->get(substr($argument, $optional ? 2 : 1), ContainerInterface::NULL_ON_INVALID_REFERENCE);

                if (null === $service) {
                    $this->assertTrue($optional, sprintf('Unknown service "%s" for argument %s of "%s".', $argument, $i, $serviceId));
                    $this->assertTrue($parameter->allowsNull(), sprintf('Argument %s of "%s" does not allow NULL but the service "%s" was not found.', $i, $serviceId, $argument));

                    continue;
                }

                if (!$type instanceof \ReflectionNamedType) {
                    // cannot validate argument without known type
                    continue;
                }

                $this->assertInstanceOf($type->getName(), $service, sprintf('Argument %s of "%s" should be "%s", got "%s".', $i, $serviceId, $type->getName(), \get_class($service)));
            }

            // TODO: handle parameters
        }
    }

    public function serviceProvider(): \Generator
    {
        $files = Finder::create()
            ->files()
            ->name('*.yaml')
            ->name('*.yml')
            ->path('src/Resources/config')
            ->in(\dirname(__DIR__, 3))
        ;

        foreach ($files as $file) {
            $yaml = Yaml::parseFile($file->getPathname(), Yaml::PARSE_CUSTOM_TAGS);

            if (!isset($yaml['services'])) {
                continue;
            }

            foreach ($yaml['services'] as $serviceId => $config) {
                if ('_' === $serviceId[0]) {
                    continue;
                }

                if (!isset($config['class'])) {
                    continue;
                }

                yield [$serviceId, $config];
            }
        }
    }

    private function countRequiredParameters(\ReflectionMethod $method): int
    {
        $count = 0;

        foreach ($method->getParameters() as $parameter) {
            if ($parameter->isOptional()) {
                continue;
            }

            ++$count;
        }

        return $count;
    }
}
