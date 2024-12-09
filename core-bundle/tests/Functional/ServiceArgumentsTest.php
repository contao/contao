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
use Symfony\Component\DependencyInjection\Container;
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

        if (!$container instanceof Container) {
            $this->fail(\sprintf('Expected container to be of class %s, got %s', Container::class, $container::class));
        }

        $files = Finder::create()
            ->files()
            ->name('*.yaml')
            ->path('config')
            ->exclude('vendor')
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

    private function doTestService(string $serviceId, string $class, \ReflectionMethod $constructor, array $arguments, Container $container): void
    {
        $this->assertGreaterThanOrEqual(
            $this->countRequiredParameters($constructor),
            $arguments,
            \sprintf('Service %s does not have the necessary amount of constructor arguments.', $serviceId),
        );

        foreach ($constructor->getParameters() as $i => $parameter) {
            if (!\array_key_exists($i, $arguments)) {
                $this->assertTrue($parameter->isOptional(), \sprintf('Missing argument %s on service ID "%s".', $i, $serviceId));

                continue;
            }

            $argument = $arguments[$i];
            $type = $parameter->getType();
            $typeNames = $this->getArgumentTypes($type);

            if (null === $argument) {
                if (!$parameter->allowsNull()) {
                    $this->fail(\sprintf('Argument %s ($%s) of %s does not allow NULL, use a valid type even if this argument is set at runtime.', $i, $parameter->getName(), $serviceId));
                }

                continue;
            }

            if ($argument instanceof TaggedValue) {
                switch ($argument->getTag()) {
                    case 'service_closure':
                        $this->assertContains(\Closure::class, $typeNames, \sprintf('Argument %s of %s should be \Closure but found %s.', $i, $serviceId, implode('|', $typeNames)));
                        break;

                    case 'tagged_iterator':
                        if (\in_array('iterable', $typeNames, true)) {
                            $this->assertContains('iterable', $typeNames, \sprintf('Argument %s of %s should be an iterable but found %s.', $i, $serviceId, implode('|', $typeNames)));
                        } else {
                            // When used in a union type, iterable is an alias for Traversable|array.
                            // https://www.php.net/manual/en/reflectionuniontype.gettypes.php#128871
                            $this->assertContains(\Traversable::class, $typeNames, \sprintf('Argument %s of %s should be an iterable but found %s.', $i, $serviceId, implode('|', $typeNames)));
                        }
                        break;

                    case 'tagged_locator':
                    case 'service_locator':
                        $this->assertContainsInstanceOf(PsrContainerInterface::class, $typeNames, \sprintf('Argument %s of %s should be %s but found %s.', $i, $serviceId, PsrContainerInterface::class, implode('|', $typeNames)));
                        break;

                    default:
                        $this->fail(\sprintf('Unknown tagged type "%s" for argument %s ($%s) of service %s.', $parameter->getType(), $i, $parameter->getName(), $serviceId));
                }

                continue;
            }

            if (!\is_string($argument)) {
                if ([] === $typeNames) {
                    $this->missingArgumentType($serviceId, $class, $i);

                    continue;
                }

                $this->assertTrue($type instanceof \ReflectionNamedType && $type->isBuiltin(), \sprintf('Argument %s of "%s" should be a built-in type, got "%s".', $i, $serviceId, get_debug_type($argument)));

                if (\in_array('iterable', $typeNames, true)) {
                    $this->assertIsIterable($argument, \sprintf('Argument %s of "%s" is not an iterable.', $i, $serviceId));

                    continue;
                }

                $this->assertContains(get_debug_type($argument), $typeNames, \sprintf('Argument %s of "%s" should be "%s", got "%s".', $i, $serviceId, implode('|', $typeNames), get_debug_type($argument)));

                continue;
            }

            if ('@.inner' === $argument || str_ends_with($argument, '.inner')) {
                $this->assertContainsInstanceOf($class, $typeNames, \sprintf('Argument %s of "%s" should be "%s", got "%s".', $i, $serviceId, implode('|', $typeNames), $class));

                continue;
            }

            if ('@' === ($argument[0] ?? '')) {
                $optional = '?' === $argument[1];
                $service = $container->get(substr($argument, $optional ? 2 : 1), ContainerInterface::NULL_ON_INVALID_REFERENCE);

                if (null === $service) {
                    $this->assertTrue($optional, \sprintf('Unknown service "%s" for argument %s of "%s".', $argument, $i, $serviceId));
                    $this->assertTrue($parameter->allowsNull(), \sprintf('Argument %s of "%s" does not allow NULL but the service "%s" was not found.', $i, $serviceId, $argument));

                    continue;
                }

                if ([] === $typeNames) {
                    $this->missingArgumentType($serviceId, $class, $i, $argument);

                    continue;
                }

                $this->assertContainsInstanceOf($service::class, $typeNames, \sprintf('Argument %s of "%s" should be "%s", got "%s".', $i, $serviceId, implode('|', $typeNames), $service::class));

                continue;
            }

            if ([] === $typeNames) {
                $this->missingArgumentType($serviceId, $class, $i, $argument);

                continue;
            }

            // At this point, the argument must be a string, which can contain
            // parameter placeholders
            $value = $container->getParameterBag()->resolveValue($argument);
            $this->assertContains(get_debug_type($value), $typeNames, \sprintf('Argument %s of "%s" should be "%s", got "%s".', $i, $serviceId, implode('|', $typeNames), get_debug_type($value)));
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

    private function getArgumentTypes(\ReflectionType|null $type): array
    {
        if ($type instanceof \ReflectionNamedType) {
            return [$type->getName()];
        }

        if ($type instanceof \ReflectionIntersectionType || $type instanceof \ReflectionUnionType) {
            $names = [];

            foreach ($type->getTypes() as $t) {
                if (!$t instanceof \ReflectionNamedType) {
                    throw new \RuntimeException(\sprintf('Unsupported reflection type "%s"', get_debug_type($t)));
                }

                $names[] = $t->getName();
            }

            return $names;
        }

        return [];
    }

    private function missingArgumentType(string $serviceId, string $class, int $i, string|null $argument = null): void
    {
        // Only warn about missing types if the constructor is in a Contao class
        if (
            !str_starts_with($class, 'Contao\\')
            || !str_starts_with((new \ReflectionMethod($class, '__construct'))->class, 'Contao\\')
        ) {
            return;
        }

        if ($argument) {
            $this->addWarning(\sprintf('Argument %s of "%s" (value: %s) does not have a type.', $i, $serviceId, $argument));
        } else {
            $this->addWarning(\sprintf('Argument %s of "%s" does not have a type.', $i, $serviceId));
        }
    }

    private function assertContainsInstanceOf(string $class, array $typeNames, string $message = ''): void
    {
        $found = false;

        foreach ($typeNames as $typeName) {
            if (is_a($class, $typeName, true)) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, $message);
    }
}
