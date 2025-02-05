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
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Contao\FrontendUser;
use Contao\User;
use Symfony\Component\HttpFoundation\RequestStack;
use Webauthn\Bundle\Repository\PublicKeyCredentialUserEntityRepositoryInterface;
use Webauthn\PublicKeyCredentialUserEntity;

final class WebauthnUserEntityRepository implements PublicKeyCredentialUserEntityRepositoryInterface
{
    public function __construct(
        private readonly ContaoUserProvider $backendUserProvider,
        private readonly ContaoUserProvider $frontendUserProvider,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function findOneByUsername(string $username): PublicKeyCredentialUserEntity|null
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($this->scopeMatcher->isBackendRequest($request)) {
            return $this->getUserEntity($this->backendUserProvider->loadUserByIdentifier($username));
        }

        if ($this->scopeMatcher->isFrontendRequest($request)) {
            return $this->getUserEntity($this->frontendUserProvider->loadUserByIdentifier($username));
        }

        return null;
    }

    public function findOneByUserHandle(string $userHandle): PublicKeyCredentialUserEntity|null
    {
        if (str_starts_with($userHandle, 'tl_user.')) {
            return $this->getUserEntity($this->backendUserProvider->loadUserById((int) substr($userHandle, 8)));
        }

        if (str_starts_with($userHandle, 'tl_member.')) {
            return $this->getUserEntity($this->frontendUserProvider->loadUserById((int) substr($userHandle, 10)));
        }

        return null;
    }

    private function getUserEntity(User|null $user): PublicKeyCredentialUserEntity|null
    {
        if (!$user) {
            return null;
        }

        if ($user instanceof FrontendUser) {
            $displayName = implode(' ', array_filter([$user->firstname, $user->lastname]));
            $userHandle = 'tl_member.'.$user->id;
        } elseif ($user instanceof BackendUser) {
            $displayName = $user->name;
            $userHandle = 'tl_user.'.$user->id;
        } else {
            throw new \RuntimeException('User instance not supported.');
        }

        return new PublicKeyCredentialUserEntity($user->username, $userHandle, $displayName);
    }
}
