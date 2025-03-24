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

use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Contao\User;
use Webauthn\Bundle\Repository\PublicKeyCredentialUserEntityRepositoryInterface;
use Webauthn\PublicKeyCredentialUserEntity;

final class WebauthnUserEntityRepository implements PublicKeyCredentialUserEntityRepositoryInterface
{
    public function __construct(private readonly ContaoUserProvider $backendUserProvider)
    {
    }

    public function findOneByUsername(string $username): PublicKeyCredentialUserEntity|null
    {
        return $this->getUserEntity($this->backendUserProvider->loadUserByIdentifier($username));
    }

    public function findOneByUserHandle(string $userHandle): PublicKeyCredentialUserEntity|null
    {
        if (str_starts_with($userHandle, 'backend.')) {
            return $this->getUserEntity($this->backendUserProvider->loadUserById((int) substr($userHandle, 8)));
        }

        return null;
    }

    private function getUserEntity(User|null $user): PublicKeyCredentialUserEntity|null
    {
        if (!$user) {
            return null;
        }

        return new PublicKeyCredentialUserEntity($user->username, $user->getPasskeyUserHandle(), $user->getDisplayName());
    }
}
