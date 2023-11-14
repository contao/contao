<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\ResponseContext;

use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Symfony's core ResponseHeaderBag forces a Date header to be present which in
 * case of the ResponseContext handling is not required. We have to ensure a
 * completely empty header bag.
 */
class PartialResponseHeaderBag extends ResponseHeaderBag
{
    public function __construct(array $headers = [])
    {
        parent::__construct($headers);

        $this->remove('cache-control');
        $this->remove('date');
    }

    #[\Override]
    public function remove(string $key): void
    {
        parent::remove($key);

        $uniqueKey = strtr($key, self::UPPER, self::LOWER);

        if ('date' === $uniqueKey) {
            unset($this->headers[$key], $this->headerNames[$uniqueKey]);
        }
    }
}
