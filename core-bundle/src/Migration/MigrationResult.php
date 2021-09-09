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

final class MigrationResult
{
    private bool $successful;
    private string $message;

    public function __construct(bool $successful, string $message)
    {
        $this->successful = $successful;
        $this->message = $message;
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
