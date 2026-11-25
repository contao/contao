<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem;

/**
 * @experimental
 */
class MaximumStreamSizeExceededException extends \RuntimeException
{
    public function __construct(
        private readonly int $maximumSize,
        \Throwable|null $previous = null,
    ) {
        parent::__construct(\sprintf('The stream exceeds the maximum size of %d bytes.', $maximumSize), previous: $previous);
    }

    public function getMaximumSize(): int
    {
        return $this->maximumSize;
    }
}
