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
final class ContextHelper
{
    /**
     * Adjust Contao template data to seamlessly work as a Twig context.
     */
    public static function fromContaoTemplate(Template $template): array
    {
        $context = $template->getData();

        array_walk_recursive(
            $context,
            static function (&$value, $key): void {
                if ($value instanceof \Closure) {
                    $value = self::getCallableWrapper($value, (string) $key);
                }
            }
        );

        if (!isset($context['Template'])) {
            $context['Template'] = $template;
        }

        return $context;
    }

    private static function getCallableWrapper(callable $callable, string $name): object
    {
        return new class($callable, $name) {
            /**
             * @var callable
             */
            private $callable;

            private ?string $name;

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
