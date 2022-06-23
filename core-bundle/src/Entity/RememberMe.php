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

use Contao\CoreBundle\Repository\RememberMeRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[Table(name: 'tl_remember_me')]
#[Entity(repositoryClass: RememberMeRepository::class)]
#[Index(columns: ['series'], name: 'series')]
#[UniqueConstraint(name: 'value', columns: ['value'])]
class RememberMe
{
    #[Id]
    #[Column(type: 'integer', options: ['unsigned' => true])]
    #[GeneratedValue]
    protected int $id;

    #[Column(type: 'binary_string', length: 88, nullable: false, options: ['fixed' => true])]
    protected string $series;

    #[Column(type: 'binary_string', length: 64, nullable: false, options: ['fixed' => true])]
    protected string $value;

    #[Column(type: 'datetime', nullable: false)]
    protected \DateTimeInterface $lastUsed;

    #[Column(type: 'string', length: 100, nullable: false)]
    protected string $class;

    #[Column(type: 'string', length: 200, nullable: false)]
    protected string $userIdentifier;

    public function __construct(string $class, string $userIdentifier, string $series, string $value, \DateTime $lastUsed)
    {
        $this->class = $class;
        $this->series = $series;
        $this->value = $value;
        $this->userIdentifier = $userIdentifier;
        $this->lastUsed = $lastUsed;
    }

    public function getSeries(): string
    {
        return $this->series;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getLastUsed(): \DateTimeInterface
    {
        return $this->lastUsed;
    }

    public function setLastUsed(\DateTimeInterface $lastUsed): self
    {
        $this->lastUsed = $lastUsed;

        return $this;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getUserIdentifier(): string
    {
        return $this->userIdentifier;
    }
}
