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
 * @internal
 */
abstract class InsertTag
{
    /**
     * @param list<InsertTagFlag> $flags
     */
    public function __construct(
        private readonly string $name,
        private readonly InsertTagParameters $parameters,
        private readonly array $flags,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParameters(): InsertTagParameters
    {
        return $this->parameters;
    }

    /**
     * @return list<InsertTagFlag>
     */
    public function getFlags(): array
    {
        return $this->flags;
    }

    public function serialize(): string
    {
        $flags = '';

        if ($this->flags) {
            $flags = '|'.implode('|', array_map(static fn ($flag) => $flag->getName(), $this->flags));
        }

        return '{{'.$this->name.$this->parameters->serialize().$flags.'}}';
    }
}
