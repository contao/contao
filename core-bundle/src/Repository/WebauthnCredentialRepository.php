<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Repository;

use Contao\CoreBundle\Entity\WebauthnCredential;
use Contao\User;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Webauthn\Bundle\Repository\DoctrineCredentialSourceRepository;
use Webauthn\PublicKeyCredentialSource;

/**
 * @template-extends DoctrineCredentialSourceRepository<WebauthnCredential>
 *
 * @internal
 */
final class WebauthnCredentialRepository extends DoctrineCredentialSourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WebauthnCredential::class);
    }

    /**
     * @return list<WebauthnCredential>
     */
    public function getAllForUser(User $user): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('c')
            ->from($this->class, 'c')
            ->where('c.userHandle = :user_handle')
            ->setParameter(':user_handle', $user->getPasskeyUserHandle())
            ->getQuery()
            ->execute()
        ;
    }

    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        if (!$publicKeyCredentialSource instanceof WebauthnCredential) {
            $publicKeyCredentialSource = new WebauthnCredential(
                $publicKeyCredentialSource->publicKeyCredentialId,
                $publicKeyCredentialSource->type,
                $publicKeyCredentialSource->transports,
                $publicKeyCredentialSource->attestationType,
                $publicKeyCredentialSource->trustPath,
                $publicKeyCredentialSource->aaguid,
                $publicKeyCredentialSource->credentialPublicKey,
                $publicKeyCredentialSource->userHandle,
                $publicKeyCredentialSource->counter,
            );
        }

        parent::saveCredentialSource($publicKeyCredentialSource);
    }

    public function findOneByCredentialId(string $publicKeyCredentialId): WebauthnCredential|null
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->from($this->class, 'c')
            ->select('c')
            ->where('c.publicKeyCredentialId = :publicKeyCredentialId')
            ->setParameter(':publicKeyCredentialId', base64_encode($publicKeyCredentialId))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneById(string $id): WebauthnCredential|null
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->from($this->class, 'c')
            ->select('c')
            ->where('c.id = :id')
            ->setParameter(':id', $id)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function remove(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        $this->getEntityManager()->remove($publicKeyCredentialSource);
        $this->getEntityManager()->flush();
    }

    public function getLastForUser(User $user): WebauthnCredential|null
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('c')
            ->from($this->class, 'c')
            ->where('c.userHandle = :user_handle')
            ->setParameter(':user_handle', $user->getPasskeyUserHandle())
            ->orderBy('c.createdAt', 'desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
