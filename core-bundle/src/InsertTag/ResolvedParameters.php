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

/**
 * @method list<string> all(string|null $name = null)
 * @method string|null  get(int|string $key)
 */
final class ResolvedParameters extends InsertTagParameters
{
    /**
     * @param list<string> $parameters
     */
    public function __construct(array $parameters)
    {
        foreach ($parameters as $parameter) {
            if (!\is_string($parameter)) {
                throw new \TypeError(sprintf('%s(): Argument #1 ($parameters) must be of type list<%s>, list<%s> given', __METHOD__, 'string', get_debug_type($parameter)));
            }
        }

        parent::__construct(array_values($parameters));
    }

    public function getScalar(int|string $key): float|int|string|null
    {
        return $this->toScalar($this->get($key));
    }

    /**
     * @return list<float|int|string>
     */
    public function allScalar(string|null $name = null): array
    {
        return array_map($this->toScalar(...), $this->all($name));
    }

    #[\Override]
    public function hasInsertTags(): bool
    {
        return false;
    }

    private function toScalar(string|null $value): float|int|string|null
    {
        if ((string) (int) $value === $value) {
            return (int) $value;
        }

        if ((string) (float) $value === $value) {
            return (float) $value;
        }

        return $value;
    }
}
