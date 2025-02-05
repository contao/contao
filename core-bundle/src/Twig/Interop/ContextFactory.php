<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Interop;

use Contao\Template;

/**
 * @experimental
 */
final class ContextFactory
{
    /**
     * Creates a Twig template context from a Template object.
     *
     * @see Template
     */
    public function fromContaoTemplate(Template $template): array
    {
        $context = $this->fromData($template->getData());

        if (!isset($context['Template'])) {
            $context['Template'] = $template;
        }

        return $context;
    }

    /**
     * Replaces all occurrences of closures by callable wrappers.
     */
    public function fromData(array $data): array
    {
        array_walk_recursive(
            $data,
            function (&$value): void {
                if ($value instanceof \Closure) {
                    $value = $this->getCallableWrapper($value);
                }
            },
        );

        return $data;
    }

    /**
     * Creates a Twig template context from an arbitrary object. This will also make
     * protected methods/properties/constants accessible.
     */
    public function fromClass(object $object): array
    {
        $class = new \ReflectionClass($object);
        $context = iterator_to_array($this->getAllMembers($object));

        foreach ($class->getReflectionConstants() as $constant) {
            if ($constant->isPrivate()) {
                continue;
            }

            $context[$constant->getName()] = $constant->getValue();
        }

        foreach ($class->getStaticProperties() as $property => $value) {
            if ($class->getProperty($property)->isPrivate()) {
                continue;
            }

            $context[$property] = $value;
        }

        foreach ($class->getMethods() as $method) {
            if ($method->isPrivate()) {
                continue;
            }

            $name = $method->getName();

            if (str_starts_with($name, '__')) {
                continue;
            }

            $context[$name] = $this->getCallableWrapper($method->getClosure($object));
        }

        if (!isset($context['this'])) {
            $context['this'] = $object;
        }

        return $context;
    }

    /**
     * Returns all members including those that were dynamically set ($this->foo = 'bar').
     */
    private function getAllMembers(object $object): \Generator
    {
        // See https://externals.io/message/105697#105697
        $mangledObjectVars = get_mangled_object_vars($object);

        foreach ($mangledObjectVars as $key => $value) {
            if (str_starts_with($key, "\0*\0")) {
                // Protected member
                $key = substr($key, 3);
            }

            if ("\0" !== $key[0]) {
                yield $key => $value;
            }
        }
    }

    /**
     * Wraps a callable into an object so that it can be evaluated in a Twig template.
     */
    private function getCallableWrapper(callable $callable): object
    {
        return new class($callable) implements \Stringable {
            /**
             * @var callable
             */
            private $callable;

            public function __construct(callable $callable)
            {
                $this->callable = $callable;
            }

            /**
             * Delegates call to callable, e.g. when in a Contao template context.
             */
            public function __invoke(mixed ...$args): mixed
            {
                return ($this->callable)(...$args);
            }

            /**
             * Called when evaluating "{{ var }}" in a Twig template.
             */
            public function __toString(): string
            {
                return (string) $this();
            }

            /**
             * Called when evaluating "{{ var.invoke() }}" in a Twig template. We do not cast
             * to string here, so that other types (like arrays) are supported as well.
             */
            public function invoke(mixed ...$args): mixed
            {
                return $this(...$args);
            }
        };
    }
}
