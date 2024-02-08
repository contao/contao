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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Contao\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Webauthn\Bundle\Repository\PublicKeyCredentialUserEntityRepositoryInterface;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * @template-extends ServiceEntityRepository<WebauthnCredential>
 *
 * @method WebauthnCredential|null findOneByName(string $name)
 *
 * @internal
 */
class WebauthnCredentialRepository implements PublicKeyCredentialUserEntityRepositoryInterface
{
    public function __construct(
        private readonly ContaoUserProvider $userProvider,
        private readonly ContaoFramework $framework
    )
    {
    }

    public function findOneByUsername(string $username): ?PublicKeyCredentialUserEntity
    {
        return $this->getUserEntity($this->userProvider->loadUserByIdentifier($username));
    }

    public function findOneByUserHandle(string $userHandle): ?PublicKeyCredentialUserEntity
    {
        throw new \RuntimeException("Not sure what '$userHandle' really is.");

        return $this->getUserEntity($this->userProvider->loadUserByIdentifier($userHandle));
    }

    private function getUserEntity(null|User $user): ?PublicKeyCredentialUserEntity
    {
        if ($user === null) {
            return null;
        }

        return new PublicKeyCredentialUserEntity(
            $user->username,
            (string) $user->id,
            $user->name,
        );
    }
}
