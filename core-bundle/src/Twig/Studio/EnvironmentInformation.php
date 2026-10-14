<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Studio;

use Contao\ArrayUtil;
use Twig\AbstractTwigCallable;
use Twig\Environment;
use Twig\Node\Expression\FilterExpression;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Expression\TestExpression;
use Twig\TokenParser\TokenParserInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class EnvironmentInformation
{
    private const KINDS = ['filter', 'function', 'test', 'tag', 'global'];

    public function __construct(private readonly Environment $twig)
    {
    }

    /**
     * Return a collection of available tags, functions, filters and tests in the
     * current Twig environment.
     */
    public function getData(): array
    {
        // We output the keywords sorted and organized by length and occurrence, so that
        // they can be easily matched by a regular expression
        $normalize = static function (array $array): array {
            $array = array_map(strval(...), $array);
            rsort($array);

            return $array;
        };

        $tokenParsers = $this->getEntries('tag');

        // Guess which tags have a corresponding end tag by method naming convention
        $tokenParsersWithEndTag = array_filter(
            $tokenParsers,
            static fn (TokenParserInterface $tokenParser): bool => method_exists($tokenParser, \sprintf('decide%sEnd', ucfirst($tokenParser->getTag()))),
        );

        $tags = $normalize(array_keys($tokenParsers));

        // Handle some special cases
        ArrayUtil::arrayInsert($tags, array_search('if', $tags, true), ['elseif', 'else']);

        $tags = [
            ...$tags,
            ...$normalize(
                array_map(
                    static fn (TokenParserInterface $tokenParser): string => "end{$tokenParser->getTag()}",
                    $tokenParsersWithEndTag,
                ),
            ),
        ];

        return [
            'tags' => $tags,
            'functions' => $normalize(array_keys($this->getEntries('function'))),
            'filters' => $normalize(array_keys($this->getEntries('filter'))),
            'tests' => $normalize(array_keys($this->getEntries('test'))),
        ];
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
            throw new \OutOfBoundsException(\sprintf('The Twig %s "%s" is not registered. Use discover() first.', $kind, $name));
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
            return match (true) {
                $callable instanceof \Closure => new \ReflectionFunction($callable),
                \is_array($callable) => new \ReflectionMethod($callable[0], $callable[1]),
                \is_string($callable) && str_contains($callable, '::') => new \ReflectionMethod(...explode('::', $callable, 2)),
                \is_string($callable) => new \ReflectionFunction($callable),
                \is_object($callable) => new \ReflectionMethod($callable, '__invoke'),
                default => null,
            };
        } catch (\ReflectionException) {
            return null;
        }
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
        $nodeClass = match (true) {
            $entry instanceof TwigFilter => FilterExpression::class,
            $entry instanceof TwigFunction => FunctionExpression::class,
            $entry instanceof TwigTest => TestExpression::class,
        };

        $reason = match (true) {
            str_contains($entry->getName(), '*') => 'Wildcard registrations have dynamic arguments.',
            $entry->getNodeClass() !== $nodeClass => 'Custom nodes can change the template-facing signature.',
            !$reflector => 'The implementation cannot be reflected.',
            $entry->isVariadic() && !$reflector->isVariadic() => 'Array-based variadic arguments have no directly reflectable template-facing signature.',
            default => null,
        };

        if (null !== $reason) {
            return ['status' => 'unavailable', 'reason' => $reason];
        }

        $hasInput = $entry instanceof TwigFilter || $entry instanceof TwigTest;
        $offset = $entry->getMinimalNumberOfRequiredArguments() - (int) $hasInput;
        $parameters = array_map($this->getParameter(...), \array_slice($reflector->getParameters(), $offset));

        if ($hasInput && !$parameters) {
            return ['status' => 'unavailable', 'reason' => 'The implementation has no identifiable input parameter.'];
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
