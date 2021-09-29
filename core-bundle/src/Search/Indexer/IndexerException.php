<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Search\Indexer;

class IndexerException extends \RuntimeException
{
    private bool $isOnlyWarning = false;

    public function isOnlyWarning(): bool
    {
        return $this->isOnlyWarning;
    }

    public static function createAsWarning(string $message): self
    {
        $exception = new self($message);
        $exception->isOnlyWarning = true;

        return $exception;
    }
}
