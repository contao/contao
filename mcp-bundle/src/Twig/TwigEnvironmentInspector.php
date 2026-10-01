<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\Twig;

use Twig\AbstractTwigCallable;
use Twig\Environment;
use Twig\Node\Expression\FilterExpression;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Expression\TestExpression;
use Twig\TokenParser\TokenParserInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

final class TwigEnvironmentInspector
{
    private const KINDS = ['filter', 'function', 'test', 'tag', 'global'];

    public function __construct(private readonly Environment $twig)
    {
    }

    public function discover(string|null $kind = null, string $query = ''): array
    {
        $items = [];

        foreach (null === $kind ? self::KINDS : [$kind] as $entryKind) {
            foreach ($this->getEntries($entryKind) as $name => $entry) {
                if ('' !== $query && false === mb_stripos((string) $name, $query)) {
                    continue;
                }

                $item = [
                    'kind' => $entryKind,
                    'name' => (string) $name,
                ];

                if ('global' !== $entryKind && $entry instanceof AbstractTwigCallable) {
                    $item['deprecated'] = $entry->isDeprecated();
                }

                $items[] = $item;
            }
        }

        usort($items, static fn (array $a, array $b): int => strcmp($a['kind'], $b['kind']) ?: strcmp($a['name'], $b['name']));

        return ['items' => $items];
    }

    public function inspect(string $kind, string $name): array
    {
        $entries = $this->getEntries($kind);

        if (!\array_key_exists($name, $entries)) {
            throw new \OutOfBoundsException(\sprintf('The Twig %s "%s" is not registered. Use contao_twig_environment_discover first.', $kind, $name));
        }

        $entry = $entries[$name];

        if ('global' === $kind) {
            return $this->inspectGlobal($name, $entry);
        }

        if ($entry instanceof TokenParserInterface) {
            return ['kind' => $kind, 'name' => $name, 'parser_class' => $this->getClassName(new \ReflectionClass($entry))];
        }

        return $this->inspectCallable($entry);
    }

    private function getEntries(string $kind): array
    {
        $entries = match ($kind) {
            'filter' => $this->twig->getFilters(),
            'function' => $this->twig->getFunctions(),
            'test' => $this->twig->getTests(),
            'tag' => $this->twig->getTokenParsers(),
            'global' => $this->twig->getGlobals(),
            default => throw new \InvalidArgumentException('Invalid Twig entry kind. Expected one of: '.implode(', ', self::KINDS).'.'),
        };

        if ('global' === $kind) {
            return $entries;
        }

        $indexed = [];

        foreach ($entries as $entry) {
            $indexed[$entry instanceof TokenParserInterface ? $entry->getTag() : $entry->getName()] = $entry;
        }

        return $indexed;
    }

    private function inspectCallable(TwigFilter|TwigFunction|TwigTest $entry): array
    {
        $reflector = $this->reflectCallable($entry->getCallable());
        $data = [
            'kind' => $entry->getType(),
            'name' => $entry->getName(),
            'deprecated' => $entry->isDeprecated(),
            'implementation' => $reflector ? $this->getImplementation($reflector) : null,
            'signature' => $this->getSignature($entry, $reflector),
            'requirements' => [
                'needs_environment' => $entry->needsEnvironment(),
                'needs_context' => $entry->needsContext(),
                'needs_charset' => $entry->needsCharset(),
                'needs_is_sandboxed' => version_compare(Environment::VERSION, '3.25.0', '>=') && $entry->needsIsSandboxed(),
                'is_variadic' => $entry->isVariadic(),
            ],
        ];

        if ($entry instanceof TwigFilter) {
            $data['escaping'] = [
                'pre_escape' => $entry->getPreEscape(),
                'preserves_safety' => $entry->getPreservesSafety(),
            ];
        }

        return $data;
    }

    private function reflectCallable(mixed $callable): \ReflectionFunctionAbstract|null
    {
        try {
            if ($callable instanceof \Closure) {
                return new \ReflectionFunction($callable);
            }

            if (\is_array($callable)) {
                return new \ReflectionMethod($callable[0], $callable[1]);
            }

            if (\is_string($callable)) {
                if (str_contains($callable, '::')) {
                    [$class, $method] = explode('::', $callable, 2);

                    return new \ReflectionMethod($class, $method);
                }

                return new \ReflectionFunction($callable);
            }

            if (\is_object($callable)) {
                return new \ReflectionMethod($callable, '__invoke');
            }
        } catch (\ReflectionException) {
            return null;
        }

        return null;
    }

    private function getImplementation(\ReflectionFunctionAbstract $reflector): string
    {
        if ($reflector instanceof \ReflectionMethod) {
            return $this->getClassName($reflector->getDeclaringClass()).'::'.$reflector->getName();
        }

        return $reflector->isClosure() ? 'Closure' : $reflector->getName();
    }

    private function getSignature(TwigFilter|TwigFunction|TwigTest $entry, \ReflectionFunctionAbstract|null $reflector): array
    {
        $signature = ['status' => 'unavailable'];

        if (str_contains($entry->getName(), '*')) {
            $signature['reason'] = 'Wildcard registrations have dynamic arguments.';

            return $signature;
        }

        $nodeClass = match (true) {
            $entry instanceof TwigFilter => FilterExpression::class,
            $entry instanceof TwigFunction => FunctionExpression::class,
            $entry instanceof TwigTest => TestExpression::class,
        };

        if ($entry->getNodeClass() !== $nodeClass) {
            $signature['reason'] = 'Custom nodes can change the template-facing signature.';

            return $signature;
        }

        if (!$reflector) {
            $signature['reason'] = 'The implementation cannot be reflected.';

            return $signature;
        }

        if ($entry->isVariadic() && !$reflector->isVariadic()) {
            $signature['reason'] = 'Array-based variadic arguments have no directly reflectable template-facing signature.';

            return $signature;
        }

        $hasInput = $entry instanceof TwigFilter || $entry instanceof TwigTest;
        $offset = $entry->getMinimalNumberOfRequiredArguments() - (int) $hasInput;
        $parameters = array_map($this->getParameter(...), \array_slice($reflector->getParameters(), $offset));

        if ($hasInput && !$parameters) {
            $signature['reason'] = 'The implementation has no identifiable input parameter.';

            return $signature;
        }

        return [
            'status' => 'best_effort',
            'input' => $hasInput ? array_shift($parameters) : null,
            'parameters' => $parameters,
            'return_type' => $this->getType($reflector->getReturnType()),
        ];
    }

    private function getParameter(\ReflectionParameter $parameter): array
    {
        return [
            'name' => $parameter->getName(),
            'type' => $this->getType($parameter->getType()),
            'optional' => $parameter->isOptional(),
            'variadic' => $parameter->isVariadic(),
        ];
    }

    private function getType(\ReflectionType|null $type): string|null
    {
        return !$type ? null : (string) $type;
    }

    private function inspectGlobal(string $name, mixed $global): array
    {
        $data = ['kind' => 'global', 'name' => $name, 'type' => get_debug_type($global)];

        if (!\is_object($global)) {
            return $data;
        }

        $reflector = new \ReflectionClass($global);
        $data['type'] = $this->getClassName($reflector);
        $data['properties'] = [];
        $data['methods'] = [];

        foreach ($reflector->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic()) {
                $data['properties'][] = ['name' => $property->getName(), 'type' => $this->getType($property->getType())];
            }
        }

        foreach ($reflector->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (!$method->isStatic() && !str_starts_with($method->getName(), '__')) {
                $data['methods'][] = [
                    'name' => $method->getName(),
                    'parameters' => array_map($this->getParameter(...), $method->getParameters()),
                    'return_type' => $this->getType($method->getReturnType()),
                ];
            }
        }

        usort($data['properties'], static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));
        usort($data['methods'], static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $data;
    }

    /**
     * @template T of object
     *
     * @param \ReflectionClass<T> $class
     */
    private function getClassName(\ReflectionClass $class): string
    {
        // Anonymous class names contain their source path and a null byte.
        return $class->isAnonymous() ? 'class@anonymous' : $class->getName();
    }
}
