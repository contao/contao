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
     * Creates a Twig template context from a @see Template object.
     */
    public function fromContaoTemplate(Template $template): array
    {
        $context = $template->getData();

        array_walk_recursive(
            $context,
            function (&$value, $key): void {
                if ($value instanceof \Closure) {
                    $value = $this->getCallableWrapper($value, (string) $key);
                }
            }
        );

        if (!isset($context['Template'])) {
            $context['Template'] = $template;
        }

        return $context;
    }

    /**
     * Creates a Twig template context from an arbitrary object. This will also
     * make protected methods/properties/constants accessible.
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

            if (0 === strpos($name, '__')) {
                continue;
            }

            $context[$name] = $this->getCallableWrapper($method->getClosure($object), $name);
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
        // Backwards compatibility with PHP < 7.4
        $mangledObjectVars = \function_exists('get_mangled_object_vars')
            ? get_mangled_object_vars($object)
            : (array) $object;

        foreach ($mangledObjectVars as $key => $value) {
            if (0 === strncmp($key, "\0*\0", 3)) {
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
    private function getCallableWrapper(callable $callable, string $name): object
    {
        return new class($callable, $name) {
            /**
             * @var callable
             */
            private $callable;
            private string $name;

            public function __construct(callable $callable, string $name)
            {
                $this->callable = $callable;
                $this->name = $name;
            }

            /**
             * Delegates call to callable, e.g. when in a Contao template context.
             *
             * @param mixed $args
             *
             * @return mixed
             */
            public function __invoke(...$args)
            {
                return ($this->callable)(...$args);
            }

            /**
             * Called when evaluating "{{ var }}" in a Twig template.
             */
            public function __toString(): string
            {
                try {
                    return (string) $this();
                } catch (\Throwable $e) {
                    throw new \RuntimeException(sprintf('Error evaluating "%s": %s', $this->name, $e->getMessage()), 0, $e);
                }
            }

            /**
             * Called when evaluating "{{ var.invoke(…) }}" in a Twig template.
             * We do not cast to string here, so that other types (like arrays)
             * are supported as well.
             *
             * @param mixed $args
             *
             * @return mixed
             */
            public function invoke(...$args)
            {
                return $this(...$args);
            }
        };
    }
}
