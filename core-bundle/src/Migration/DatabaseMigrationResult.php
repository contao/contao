<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration;

final class DatabaseMigrationResult
{
    public function __construct(
        private readonly bool $successful,
        private readonly string|null $message = null,
    ) {
    }

    public static function success(): self
    {
        return new self(true);
    }

    public static function failure(string|null $message = null): self
    {
        return new self(false, $message);
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function getMessage(): string|null
    {
        return $this->message;
    }
}
