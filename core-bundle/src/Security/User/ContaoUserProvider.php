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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FrontendUser;
use Contao\System;
use Contao\User;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ContaoUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    /**
     * @param class-string<User> $userClass
     */
    public function __construct(
        private ContaoFramework $framework,
        private string $userClass,
    ) {
        if (BackendUser::class !== $userClass && FrontendUser::class !== $userClass) {
            throw new \RuntimeException(sprintf('Unsupported class "%s".', $userClass));
        }
    }

    /**
     * @param mixed $username
     *
     * @deprecated Deprecated since Contao 4.13, to be removed in Contao 5.0;
     *             use ContaoUserProvider::loadUserByIdentifier() instead
     */
    public function loadUserByUsername($username): User
    {
        trigger_deprecation('contao/core-bundle', '4.13', 'Using "ContaoUserProvider::loadUserByUsername()" has been deprecated and will no longer work in Contao 5.0. Use "ContaoUserProvider::loadUserByIdentifier()" instead.');

        return $this->loadUserByIdentifier((string) $username);
    }

    public function loadUserByIdentifier(string $identifier): User
    {
        $this->framework->initialize();

        /** @var User $adapter */
        $adapter = $this->framework->getAdapter($this->userClass);
        $user = $adapter->loadUserByIdentifier($identifier);

        if (is_a($user, $this->userClass)) {
            return $user;
        }

        throw new UsernameNotFoundException(sprintf('Could not find user "%s"', $identifier));
    }

    public function refreshUser(UserInterface $user): User
    {
        if (!is_a($user, $this->userClass)) {
            throw new UnsupportedUserException(sprintf('Unsupported class "%s".', $user::class));
        }

        $user = $this->loadUserByIdentifier($user->getUserIdentifier());

        $this->triggerPostAuthenticateHook($user);

        return $user;
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
    public function upgradePassword(UserInterface $user, string $newHashedPassword): void
    {
        if (!is_a($user, $this->userClass)) {
            throw new UnsupportedUserException(sprintf('Unsupported class "%s".', $user::class));
        }

        $user->password = $newHashedPassword;
        $user->save();
    }

    private function triggerPostAuthenticateHook(User $user): void
    {
        if (empty($GLOBALS['TL_HOOKS']['postAuthenticate']) || !\is_array($GLOBALS['TL_HOOKS']['postAuthenticate'])) {
            return;
        }

        trigger_deprecation('contao/core-bundle', '4.5', 'Using the "postAuthenticate" hook has been deprecated and will no longer work in Contao 5.0.');

        $system = $this->framework->getAdapter(System::class);

        foreach ($GLOBALS['TL_HOOKS']['postAuthenticate'] as $callback) {
            $system->importStatic($callback[0])->{$callback[1]}($user);
        }
    }
}
