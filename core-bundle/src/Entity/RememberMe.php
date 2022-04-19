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
use Symfony\Component\Security\Core\User\UserInterface;

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

    #[Column(type: 'binary_string', length: 32, nullable: false, options: ['fixed' => true])]
    protected string $series;

    #[Column(type: 'binary_string', length: 64, nullable: false, options: ['fixed' => true])]
    protected string $value;

    #[Column(type: 'datetime', nullable: false)]
    protected \DateTimeInterface $lastUsed;

    #[Column(type: 'datetime', nullable: true)]
    protected \DateTimeInterface|null $expires = null;

    #[Column(type: 'string', length: 100, nullable: false)]
    protected string $class;

    #[Column(type: 'string', length: 200, nullable: false)]
    protected string $username;

    public function __construct(UserInterface $user, string $series)
    {
        $this->class = $user::class;
        $this->series = $series;
        $this->value = random_bytes(64);
        $this->username = $user->getUserIdentifier();
        $this->lastUsed = new \DateTime();
        $this->expires = null;
    }

    public function __clone()
    {
        $this->value = '';
        $this->lastUsed = new \DateTime();
        $this->expires = null;
    }

    public function getSeries(): string
    {
        return $this->series;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getLastUsed(): \DateTimeInterface
    {
        return $this->lastUsed;
    }

    public function getExpires(): \DateTimeInterface|null
    {
        return $this->expires;
    }

    public function setExpiresInSeconds(int $seconds): self
    {
        if (null === $this->expires) {
            $this->expires = (new \DateTime())->add(new \DateInterval('PT'.$seconds.'S'));
        }

        return $this;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function cloneWithNewValue(): self
    {
        $clone = clone $this;
        $clone->value = random_bytes(64);

        return $clone;
    }
}
