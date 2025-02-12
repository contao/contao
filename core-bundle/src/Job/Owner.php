<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Job;

/**
 * @experimental
 */
final class Owner
{
    public const SYSTEM = 'SYSTEM';

    public function __construct(private readonly string $identifier)
    {
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public static function asSystem(): self
    {
        return new self(self::SYSTEM);
    }
}
