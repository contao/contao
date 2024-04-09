<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'tl_altcha')]
#[Entity]
class Altcha
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    protected int|null $id = null;

    #[Column(type: 'datetime')]
    protected \DateTimeInterface $created;

    #[Column(type: 'string', nullable: true)]
    protected string|null $challenge = null;

    #[Column(type: 'boolean')]
    protected bool $solved;

    #[Column(type: 'datetime')]
    protected \DateTimeInterface $expires;

    public function __construct(string $challenge, \DateTimeInterface $expires)
    {
        $this->created = new \DateTime();
        $this->solved = false;
        $this->challenge = $challenge;
        $this->expires = $expires;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getCreated(): \DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getChallenge(): string|null
    {
        return $this->challenge;
    }

    public function setChallenge(string $challenge): self
    {
        $this->challenge = $challenge;

        return $this;
    }

    public function getSolved(): bool
    {
        return $this->solved;
    }

    public function setSolved(bool $solved): self
    {
        $this->solved = $solved;

        return $this;
    }

    public function getExpires(): \DateTimeInterface
    {
        return $this->expires;
    }

    public function setExpires(\DateTimeInterface $expires): self
    {
        $this->created = $expires;

        return $this;
    }
}
