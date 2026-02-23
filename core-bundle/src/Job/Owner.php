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
    public const SYSTEM = 0;

    public function __construct(private readonly int $id)
    {
    }

    public function isSystem(): bool
    {
        return self::SYSTEM === $this->id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public static function asSystem(): self
    {
        return new self(self::SYSTEM);
    }
}
