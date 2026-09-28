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

use Contao\CoreBundle\Repository\PersonalAccessTokenRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\Table;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[Table(name: 'tl_personal_access_token')]
#[Entity(PersonalAccessTokenRepository::class)]
#[Index('id_expires', ['id', 'expiresAt'])]
class PersonalAccessToken
{
    #[Id]
    #[Column(type: UuidType::NAME, unique: true)]
    private readonly Uuid $id;

    #[Column(type: Types::INTEGER, options: ['unsigned' => true])]
    protected int $userId;

    #[Column(type: Types::DATETIME_IMMUTABLE)]
    protected \DateTimeImmutable $createdAt;

    #[Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected \DateTimeImmutable|null $expiresAt;

    #[Column(type: Types::STRING)]
    protected string $name;

    #[Column(type: Types::STRING)]
    protected string $secret;

    // The plain token that is shown to the user once
    protected string|null $plainToken = null;

    public function __construct(int $userId, string $name, string $secret, \DateTimeInterface|null $expiresAt = null)
    {
        $this->id = Uuid::v7();
        $this->userId = $userId;
        $this->createdAt = new \DateTimeImmutable();
        $this->name = $name;
        $this->secret = $secret;

        if ($expiresAt && !$expiresAt instanceof \DateTimeImmutable) {
            $expiresAt = \DateTimeImmutable::createFromInterface($expiresAt);
        }

        $this->expiresAt = $expiresAt;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCreated(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreated(\DateTimeInterface $createdAt): self
    {
        if (!$createdAt instanceof \DateTimeImmutable) {
            $createdAt = \DateTimeImmutable::createFromInterface($createdAt);
        }

        $this->createdAt = $createdAt;

        return $this;
    }

    public function getExpiresAt(): \DateTimeInterface|null
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeInterface|null $expiresAt): self
    {
        if ($expiresAt && !$expiresAt instanceof \DateTimeImmutable) {
            $expiresAt = \DateTimeImmutable::createFromInterface($expiresAt);
        }

        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function setSecret(string $secret): self
    {
        $this->secret = $secret;

        return $this;
    }

    public function getPlainToken(): string|null
    {
        return $this->plainToken;
    }

    public function setPlainToken(string $plainToken): self
    {
        $this->plainToken = $plainToken;

        return $this;
    }
}
