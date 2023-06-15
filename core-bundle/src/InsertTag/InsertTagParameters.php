<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\InsertTag;

abstract class InsertTagParameters
{
    /**
     * @param list<ParsedSequence|string> $parameters
     */
    public function __construct(private readonly array $parameters)
    {
    }

    abstract public function hasInsertTags(): bool;

    public function get(int|string $key): ParsedSequence|float|int|string|null
    {
        if (\is_int($key)) {
            return $this->toValue($this->parameters[$key] ?? null);
        }

        return $this->all($key)[0] ?? null;
    }

    /**
     * @return list<ParsedSequence|float|int|string>
     */
    public function all(string|null $name = null): array
    {
        if (null === $name) {
            return array_map($this->toValue(...), $this->parameters);
        }

        return $this->getNamed($name);
    }

    public function serialize(): string
    {
        if (!\count($this->parameters)) {
            return '';
        }

        return '::'.implode(
            '::',
            array_map(
                static function ($value) {
                    if (\is_string($value)) {
                        return $value;
                    }

                    $return = '';

                    foreach ($value as $item) {
                        $return .= \is_string($item) ? $item : $item->serialize();
                    }

                    return $return;
                },
                $this->parameters,
            ),
        );
    }

    private function getNamed(string $key): array
    {
        $values = [];

        foreach ($this->parameters as $parameter) {
            if (
                \is_string($parameter)
                && str_starts_with($parameter, $key.'=')
            ) {
                $values[] = $this->toValue(substr($parameter, \strlen($key) + 1));
            } elseif (
                $parameter instanceof ParsedSequence
                && $parameter->count()
                && \is_string($parameter->get(0))
                && str_starts_with($parameter->get(0), $key.'=')
            ) {
                $value = iterator_to_array($parameter);
                $value[0] = substr($value[0], \strlen($key) + 1);
                $values[] = new ParsedSequence($value);
            }
        }

        return $values;
    }

    private function toValue(ParsedSequence|string|null $value): ParsedSequence|float|int|string|null
    {
        if (!\is_string($value)) {
            return $value;
        }

        if ((string) (int) $value === $value) {
            return (int) $value;
        }

        if ((string) (float) $value === $value) {
            return (float) $value;
        }

        return $value;
    }
}
