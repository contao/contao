<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Session\Attribute;

class AutoExpiringAttribute
{
    private int $tstamp;

    /**
     * @param int $ttl Time to live in seconds
     */
    public function __construct(private int $ttl, private mixed $value, \DateTimeInterface|null $createdAt = null)
    {
        $this->tstamp = ($createdAt ?? new \DateTime())->getTimestamp();
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function isExpired(\DateTimeInterface|null $now = null): bool
    {
        $now = $now ?? new \DateTime();

        return $this->tstamp + $this->getTtl() < $now->getTimestamp();
    }
}
