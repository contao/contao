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

final class ResolvedParameters extends InsertTagParameters
{
    /**
     * @param array<array-key,self|float|int|string> $parameters
     */
    public function __construct(array $parameters)
    {
        parent::__construct($parameters);
    }

    public function hasInsertTags(): bool
    {
        return false;
    }

    public function get(int|string $key): self|float|int|string
    {
        return parent::get($key);
    }

    public function toArray(): array
    {
        $parameters = [];

        foreach ($this->keys() as $key) {
            if (($value = $this->get($key)) instanceof self) {
                $value = $value->toArray();
            }

            $parameters[$key] = $value;
        }

        return $parameters;
    }
}
