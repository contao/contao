<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthServerBundle\Entity;

use Contao\OAuthServerBundle\Repository\OAuthServerTokenRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @internal
 */
#[Table(name: 'oauth_server_token')]
#[UniqueConstraint(name: 'identifier', columns: ['identifier'])]
#[Index(name: 'expires', columns: ['expires'])]
#[Entity(repositoryClass: OAuthServerTokenRepository::class)]
class OAuthServerToken
{
    final public const string TYPE_ACCESS = 'access';
    final public const string TYPE_REFRESH = 'refresh';
    final public const string TYPE_CODE = 'code';

    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    protected int|null $id = null;

    #[Column(type: 'string', length: 16)]
    protected string $type;

    #[Column(type: 'string', length: 128)]
    protected string $identifier;

    #[Column(name: 'client_id', type: 'string', length: 255, nullable: true)]
    protected string|null $clientId = null;

    #[Column(name: 'user_identifier', type: 'string', nullable: true)]
    protected string|null $userIdentifier = null;

    #[Column(type: 'datetime_immutable')]
    protected \DateTimeImmutable $expires;

    #[Column(type: 'boolean')]
    protected bool $revoked = false;

    public function __construct(string $type, string $identifier, \DateTimeImmutable $expires, string|null $clientId = null, string|null $userIdentifier = null)
    {
        $this->type = $type;
        $this->identifier = $identifier;
        $this->expires = $expires;
        $this->clientId = $clientId;
        $this->userIdentifier = $userIdentifier;
    }

    public function isRevoked(): bool
    {
        return $this->revoked || $this->expires < new \DateTimeImmutable();
    }

    public function revoke(): self
    {
        $this->revoked = true;

        return $this;
    }
}
