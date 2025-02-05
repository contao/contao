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
 * @method list<ParsedSequence> all(string|null $name = null)
 * @method ParsedSequence|null  get(int|string $key)
 */
final class ParsedParameters extends InsertTagParameters
{
    /**
     * @param list<ParsedSequence> $parameters
     */
    public function __construct(array $parameters)
    {
        foreach ($parameters as $parameter) {
            if (!$parameter instanceof ParsedSequence) {
                throw new \TypeError(\sprintf('%s(): Argument #1 ($parameters) must be of type list<%s>, list<%s> given', __METHOD__, ParsedSequence::class, get_debug_type($parameter)));
            }
        }

        parent::__construct(array_values($parameters));
    }

    public function hasInsertTags(): bool
    {
        foreach ($this->all() as $sequence) {
            if ($sequence->hasInsertTags()) {
                return true;
            }
        }

        return false;
    }
}
