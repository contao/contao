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

use Contao\CoreBundle\Repository\WebauthnCredentialRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\TrustPath\TrustPath;

#[Table(name: 'webauthn_credentials')]
#[Entity(repositoryClass: WebauthnCredentialRepository::class)]
class WebauthnCredential extends PublicKeyCredentialSource
{
    #[Column(type: Types::STRING)]
    public string $name;

    #[Column(type: Types::DATETIME_IMMUTABLE)]
    public readonly \DateTimeImmutable $createdAt;

    #[Id]
    #[Column(unique: true)]
    #[GeneratedValue(strategy: 'NONE')]
    private readonly string $id;

    public function __construct(string $publicKeyCredentialId, string $type, array $transports, string $attestationType, TrustPath $trustPath, Uuid $aaguid, string $credentialPublicKey, string $userHandle, int $counter)
    {
        $this->id = Ulid::generate();
        $this->name = '';
        $this->createdAt = new \DateTimeImmutable();

        parent::__construct($publicKeyCredentialId, $type, $transports, $attestationType, $trustPath, $aaguid, $credentialPublicKey, $userHandle, $counter);
    }

    public function getId(): string
    {
        return $this->id;
    }
}
