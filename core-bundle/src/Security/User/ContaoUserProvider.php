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
use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\FrontendUser;
use Contao\System;
use Contao\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ContaoUserProvider implements UserProviderInterface
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var string
     */
    private $userClass;

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * @throws \RuntimeException
     */
    public function __construct(ContaoFramework $framework, SessionInterface $session, string $userClass, LoggerInterface $logger = null)
    {
        if (BackendUser::class !== $userClass && FrontendUser::class !== $userClass) {
            throw new \RuntimeException(sprintf('Unsupported class "%s".', $userClass));
        }

        $this->framework = $framework;
        $this->session = $session;
        $this->userClass = $userClass;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username): User
    {
        $this->framework->initialize();

        /** @var User $adapter */
        $adapter = $this->framework->getAdapter($this->userClass);
        $user = $adapter->loadUserByUsername($username);

        if (is_a($user, $this->userClass)) {
            return $user;
        }

        throw new UsernameNotFoundException(sprintf('Could not find user "%s"', $username));
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user)
    {
        if (!is_a($user, $this->userClass)) {
            throw new UnsupportedUserException(sprintf('Unsupported class "%s".', \get_class($user)));
        }

        $user = $this->loadUserByUsername($user->getUsername());

        $this->validateSessionLifetime($user);
        $this->triggerPostAuthenticateHook($user);

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class): bool
    {
        return $this->userClass === $class;
    }

    /**
     * Validates the session lifetime and logs the user out if the session has expired.
     *
     * @throws UsernameNotFoundException
     */
    private function validateSessionLifetime(User $user): void
    {
        if (!$this->session->isStarted()) {
            return;
        }

        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);
        $timeout = (int) $config->get('sessionTimeout');

        if ($timeout > 0 && (time() - $this->session->getMetadataBag()->getLastUsed()) < $timeout) {
            return;
        }

        if (null !== $this->logger) {
            $this->logger->info(
                sprintf('User "%s" has been logged out automatically due to inactivity', $user->username),
                ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS, $user->username)]
            );
        }

        throw new UsernameNotFoundException(
            sprintf('User "%s" has been logged out automatically due to inactivity.', $user->username)
        );
    }

    private function triggerPostAuthenticateHook(User $user): void
    {
        if (empty($GLOBALS['TL_HOOKS']['postAuthenticate']) || !\is_array($GLOBALS['TL_HOOKS']['postAuthenticate'])) {
            return;
        }

        @trigger_error('Using the "postAuthenticate" hook has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);

        /** @var System $system */
        $system = $this->framework->getAdapter(System::class);

        foreach ($GLOBALS['TL_HOOKS']['postAuthenticate'] as $callback) {
            $system->importStatic($callback[0])->{$callback[1]}($user);
        }
    }
}
