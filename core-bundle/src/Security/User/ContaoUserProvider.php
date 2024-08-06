<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\User;

use Contao\BackendUser;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FrontendUser;
use Contao\User;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<User>
 */
class ContaoUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    /**
     * @param class-string<User> $userClass
     */
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly string $userClass,
    ) {
        if (BackendUser::class !== $userClass && FrontendUser::class !== $userClass) {
            throw new \RuntimeException(\sprintf('Unsupported class "%s".', $userClass));
        }
    }

    public function loadUserByIdentifier(string $identifier): User
    {
        $this->framework->initialize();

        /** @var Adapter<User> $adapter */
        $adapter = $this->framework->getAdapter($this->userClass);
        $user = $adapter->loadUserByIdentifier($identifier);

        if (is_a($user, $this->userClass)) {
            return $user;
        }

        throw new UserNotFoundException(\sprintf('Could not find user "%s"', $identifier));
    }

    public function refreshUser(UserInterface $user): User
    {
        if (!is_a($user, $this->userClass)) {
            throw new UnsupportedUserException(\sprintf('Unsupported class "%s".', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    /**
     * @param class-string<User> $class
     */
    public function supportsClass(string $class): bool
    {
        return $this->userClass === $class;
    }

    /**
     * @param User $user
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!is_a($user, $this->userClass)) {
            throw new UnsupportedUserException(\sprintf('Unsupported class "%s".', $user::class));
        }

        $user->password = $newHashedPassword;
        $user->save();
    }
}
