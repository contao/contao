<?php

namespace Contao\CoreBundle\Job;

final class Owner
{
    public const SYSTEM = 'SYSTEM';

    public function __construct(private string $identifier)
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
