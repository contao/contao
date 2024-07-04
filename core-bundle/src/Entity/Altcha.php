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

use Contao\CoreBundle\Repository\AltchaRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'altcha_challenges')]
#[Entity(repositoryClass: AltchaRepository::class)]
#[Index(columns: ['challenge'], name: 'challenge')]
#[Index(columns: ['expires'], name: 'expires')]
class Altcha
{
    #[Id]
    #[Column(type: 'integer', options: ['unsigned' => true])]
    #[GeneratedValue]
    protected int $id;

    #[Column(type: 'string', length: 64, nullable: false)]
    protected string $challenge;

    #[Column(type: 'datetime')]
    protected \DateTimeInterface $created;

    #[Column(type: 'datetime')]
    protected \DateTimeInterface $expires;

    public function __construct(string $challenge, \DateTimeInterface $expires)
    {
        $this->challenge = $challenge;
        $this->created = new \DateTime();
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

    public function getChallenge(): string|null
    {
        return $this->challenge;
    }

    public function setChallenge(string $challenge): self
    {
        $this->challenge = $challenge;

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
