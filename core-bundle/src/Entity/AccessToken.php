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

use Contao\CoreBundle\Repository\AccessTokenRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'tl_access_token')]
#[Entity(repositoryClass: AccessTokenRepository::class)]
#[Index(columns: ['token'], name: 'token')]
#[Index(columns: ['username'], name: 'username')]
class AccessToken
{
    #[Id]
    #[Column(type: 'integer', options: ['unsigned' => true])]
    #[GeneratedValue]
    protected int $id;

    #[Column(type: 'string', nullable: false)]
    protected string $token;

    #[Column(type: 'string')]
    protected string $username;

    #[Column(type: 'datetime')]
    protected \DateTimeInterface $expiresAt;

    public function __construct(string $token, string $username, \DateTimeInterface $expiresAt)
    {
        $this->token = $token;
        $this->username = $username;
        $this->expiresAt = $expiresAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getExpiresAt(): \DateTimeInterface
    {
        return $this->expiresAt;
    }
}
