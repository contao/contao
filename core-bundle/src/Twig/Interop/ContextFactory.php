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
     * Create a template context from a @Template object that can be used in Twig.
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
     * Transform an object into a template context that can be used in Twig.
     * This also includes private/protected methods, (static) properties and
     * constants.
     */
    public function fromClass(object $object): array
    {
        $class = new \ReflectionClass($object);

        $context = array_merge(
            $class->getConstants(),
            $class->getStaticProperties(),
            iterator_to_array($this->getAllMembers($object)),
        );

        foreach ($class->getMethods() as $method) {
            $name = $method->getName();

            if (0 === strpos($name, '__')) {
                continue;
            }

            $context[$name] = $this->getCallableWrapper($method->getClosure($object), $name);
        }

        if (!isset($context['data'])) {
            $context['data'] = $object;
        }

        return $context;
    }

    /**
     * Find all members including those that were dynamically set ($this->foo = 'bar').
     */
    private function getAllMembers(object $object): \Generator
    {
        // See https://externals.io/message/105697#105697
        $mangledObjectVars = \function_exists('get_mangled_object_vars') ?
            get_mangled_object_vars($object) : (array) $object;

        foreach ($mangledObjectVars as $key => $value) {
            if (preg_match('/^\x0(\S+)\x0(\S+)$/', $key, $matches)) {
                // Protected or private member
                if ('*' === $matches[1] || \get_class($object) === $matches[1]) {
                    yield $matches[2] => $value;
                    continue;
                }

                throw new \InvalidArgumentException('Could not identify prepended key.');
            }

            // Public member
            yield $key => $value;
        }
    }

    /**
     * Wrap a callable into an object so that it can be evaluated in a Twig template.
     */
    private function getCallableWrapper(callable $callable, string $name): object
    {
        return new class($callable, $name) {
            /**
             * @var callable
             */
            private $callable;

            /**
             * @var string
             */
            private $name;

            public function __construct(callable $callable, string $name)
            {
                $this->callable = $callable;
                $this->name = $name;
            }

            /**
             * Delegate call to callable, e.g. when in a Contao template context.
             */
            public function __invoke(...$args)
            {
                return ($this->callable)(...$args);
            }

            /**
             * Called when evaluating `{{ var }}` in a Twig template.
             */
            public function __toString(): string
            {
                try {
                    return (string) $this();
                } catch (\Throwable $e) {
                    // A __toString function may not throw an exception in PHP<7.4
                    if (\PHP_VERSION_ID < 70400) {
                        return '';
                    }

                    throw new \RuntimeException("Error evaluating '$this->name': {$e->getMessage()}", 0, $e);
                }
            }

            /**
             * Called when evaluating '{{ var.invoke(…) }}' in a Twig template.
             * We do not cast to string here, so that other types (like arrays)
             * are supported as well.
             */
            public function invoke(...$args)
            {
                return $this(...$args);
            }
        };
    }
}
