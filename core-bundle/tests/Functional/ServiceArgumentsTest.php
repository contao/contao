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

    public function testServices(): void
    {
        $container = $this->getContainer();

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

                if (!$container->has($serviceId)) {
                    continue;
                }

                $ref = new \ReflectionClass($config['class']);

                if (!$constructor = $ref->getConstructor()) {
                    continue;
                }

                $this->doTestService($serviceId, $config['class'], $constructor, $config['arguments'] ?? [], $container);
            }
        }
    }

    private function doTestService(string $serviceId, string $class, \ReflectionMethod $constructor, array $arguments, ContainerInterface $container): void
    {
        $this->assertGreaterThanOrEqual(
            $this->countRequiredParameters($constructor),
            $arguments,
            sprintf('Service %s does not have the necessary amount of constructor arguments.', $serviceId)
        );

        foreach ($constructor->getParameters() as $i => $parameter) {
            if (!\array_key_exists($i, $arguments)) {
                $this->assertTrue($parameter->isOptional(), sprintf('Missing argument %s on service ID "%s".', $i, $serviceId));

                continue;
            }

            $argument = $arguments[$i];
            $type = $parameter->getType();
            $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;

            if (null === $argument) {
                if (!$parameter->allowsNull()) {
                    $this->fail(sprintf('Argument %s ($%s) of %s does not allow NULL, use a valid type even if this argument is set at runtime.', $i, $parameter->getName(), $serviceId));
                }

                continue;
            }

            if ($argument instanceof TaggedValue) {
                switch ($argument->getTag()) {
                    case 'service_closure':
                        $this->assertSame('Closure', $typeName, sprintf('Argument %s of %s should be \Closure but found %s.', $i, $serviceId, $typeName));
                        break;

                    case 'tagged_iterator':
                        $this->assertSame('iterable', $typeName, sprintf('Argument %s of %s is not an iterable', $i, $serviceId));
                        break;

                    case 'tagged_locator':
                    case 'service_locator':
                        $this->assertSame(PsrContainerInterface::class, $typeName, sprintf('Argument %s of %s should be %s but found %s.', $i, $serviceId, PsrContainerInterface::class, $typeName));
                        break;

                    default:
                        $this->fail(sprintf('Unknown tagged type "%s" for argument %s ($%s) of service %s.', $parameter->getType(), $i, $parameter->getName(), $serviceId));
                }

                continue;
            }

            if (!\is_string($argument)) {
                if (!$type instanceof \ReflectionNamedType) {
                    // cannot validate argument without known type
                    continue;
                }

                $this->assertTrue($type->isBuiltin() ?? false, sprintf('Argument %s of "%s" should be a built-in type, got "%s".', $i, $serviceId, get_debug_type($argument)));

                if ('iterable' === $typeName) {
                    $this->assertTrue(is_iterable($argument), sprintf('Argument %s of "%s" is not an iterable.', $i, $serviceId));

                    continue;
                }

                $this->assertSame(get_debug_type($argument), $typeName);
                continue;
            }

            if ($type instanceof \ReflectionNamedType && ('@.inner' === $argument || str_ends_with($argument, '.inner'))) {
                $this->assertTrue(is_a($class, $typeName, true), sprintf('Argument %s of "%s" should be "%s", got "%s".', $i, $serviceId, $typeName, $class));

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

                $this->assertInstanceOf($typeName, $service, sprintf('Argument %s of "%s" should be "%s", got "%s".', $i, $serviceId, $typeName, \get_class($service)));
            }

            // TODO: handle parameters
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
