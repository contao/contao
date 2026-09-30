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

    #[Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected \DateTimeImmutable|null $lastUsed = null;

    #[Column(type: Types::STRING)]
    protected string $name;

    #[Column(type: Types::STRING)]
    #[\SensitiveParameter]
    protected string $secret;

    public function __construct(int $userId, string $name, #[\SensitiveParameter] string $secret, \DateTimeInterface|null $expiresAt = null)
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

    public function getExpiresAt(): \DateTimeInterface|null
    {
        return $this->expiresAt;
    }

    public function getLastUsed(): \DateTimeInterface|null
    {
        return $this->lastUsed;
    }

    public function setLastUsed(\DateTimeInterface|null $lastUsed): self
    {
        if ($lastUsed && !$lastUsed instanceof \DateTimeImmutable) {
            $lastUsed = \DateTimeImmutable::createFromInterface($lastUsed);
        }

        $this->lastUsed = $lastUsed;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }
}
