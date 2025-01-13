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

use Contao\BackendUser;
use Contao\CoreBundle\Entity\WebauthnCredential;
use Contao\FrontendUser;
use Contao\User;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Webauthn\Bundle\Repository\DoctrineCredentialSourceRepository;
use Webauthn\PublicKeyCredentialSource;

/**
 * @template-extends DoctrineCredentialSourceRepository<WebauthnCredential>
 *
 * @internal
 */
final class WebauthnCredentialRepository extends DoctrineCredentialSourceRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
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
            ->andWhere('c.userType = :user_type')
            ->setParameter(':user_handle', $user->id)
            ->setParameter(':user_type', $this->getUserType($user))
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
                $this->getUserType($this->tokenStorage->getToken()->getUser()),
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
            ->andWhere('c.userType = :user_type')
            ->setParameter(':user_handle', $user->id)
            ->setParameter(':user_type', $this->getUserType($user))
            ->orderBy('c.createdAt', 'desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function getUserType(UserInterface $user): string
    {
        return match ($user::class) {
            FrontendUser::class => 'frontend',
            BackendUser::class => 'backend',
            default => throw new \RuntimeException('User instance not supported.'),
        };
    }
}
