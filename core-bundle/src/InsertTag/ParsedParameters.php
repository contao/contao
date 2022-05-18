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

final class ParsedParameters extends InsertTagParameters
{
    /**
     * @param array<array-key,self|array|ParsedSequence> $parameters
     */
    public function __construct(array $parameters)
    {
        foreach ($parameters as $key => $value) {
            if (\is_array($value)) {
                $parameters[$key] = new self($value);
            }
        }

        parent::__construct($parameters);
    }

    public function hasInsertTags(): bool
    {
        foreach ($this->keys() as $key) {
            if ($this->get($key)->hasInsertTags()) {
                return true;
            }
        }

        return false;
    }

    public function get(int|string $key): ParsedSequence|self
    {
        return parent::get($key);
    }
}
