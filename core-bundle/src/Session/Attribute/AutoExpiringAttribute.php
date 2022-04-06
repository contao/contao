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
    private int $ttl;
    private mixed $value;

    /**
     * @param int $ttl Time to live in seconds
     */
    public function __construct(int $ttl, mixed $value, \DateTimeInterface|null $createdAt = null)
    {
        $this->tstamp = ($createdAt ?? new \DateTime())->getTimestamp();
        $this->ttl = $ttl;
        $this->value = $value;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function isExpired(\DateTimeInterface $now = null): bool
    {
        $now = $now ?? new \DateTime();

        return $this->tstamp + $this->getTtl() < $now->getTimestamp();
    }
}
