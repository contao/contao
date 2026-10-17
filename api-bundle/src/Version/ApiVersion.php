<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Version;

final readonly class ApiVersion implements \Stringable
{
    public const HEADER = 'Contao-Api-Version';
    public const CONTEXT_KEY = 'contao_api_version';

    private \DateTimeImmutable $date;

    public function __construct(string $date)
    {
        if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $date) || str_starts_with($date, '0000')) {
            throw new \InvalidArgumentException('An API version must be a valid date in YYYY-MM-DD format.');
        }

        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date, new \DateTimeZone('UTC'));

        if (false === $parsed || $parsed->format('Y-m-d') !== $date) {
            throw new \InvalidArgumentException('An API version must be a valid date in YYYY-MM-DD format.');
        }

        $this->date = $parsed;
    }

    public function __toString(): string
    {
        return $this->date->format('Y-m-d');
    }

    public static function today(): self
    {
        return new self(new \DateTimeImmutable('today', new \DateTimeZone('UTC'))->format('Y-m-d'));
    }

    public function toDateTime(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function isBefore(string $date): bool
    {
        return $this->date < new self($date)->date;
    }
}
