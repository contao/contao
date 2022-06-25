<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Authentication;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\FrontendUser;
use Contao\StringUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class FrontendPreviewAuthenticator
{
    final public const SESSION_NAME = '_contao_frontend_preview';

    /**
     * @internal Do not inherit from this class; decorate the "contao.security.frontend_preview_authenticator" service instead
     */
    public function __construct(
        private Security $security,
        private SessionInterface $session,
        private UserProviderInterface $userProvider,
        private LoggerInterface|null $logger = null,
    ) {
    }

    public function authenticateFrontendUser(string $username, bool $showUnpublished): bool
    {
        $user = $this->loadFrontendUser($username);

        if (null === $user) {
            return false;
        }

        $token = new UsernamePasswordToken($user, 'contao_frontend');

        $this->session->set('_security_contao_frontend', serialize($token));
        $this->session->set(self::SESSION_NAME, ['showUnpublished' => $showUnpublished]);

        return true;
    }

    public function authenticateFrontendGuest(bool $showUnpublished): bool
    {
        $this->session->set(self::SESSION_NAME, ['showUnpublished' => $showUnpublished]);

        return true;
    }

    /**
     * Removes a front end authentication from the session.
     */
    public function removeFrontendAuthentication(): bool
    {
        if (!$this->session->isStarted() || (!$this->session->has('_security_contao_frontend') && !$this->session->has(self::SESSION_NAME))) {
            return false;
        }

        $this->session->remove('_security_contao_frontend');
        $this->session->remove(self::SESSION_NAME);

        return true;
    }

    /**
     * Loads the front end user and checks its group access permissions.
     */
    private function loadFrontendUser(string $username): FrontendUser|null
    {
        try {
            $frontendUser = $this->userProvider->loadUserByIdentifier($username);

            // Make sure the user provider returned a front end user
            if (!$frontendUser instanceof FrontendUser) {
                throw new UserNotFoundException('User is not a front end user');
            }
        } catch (UserNotFoundException) {
            $this->logger?->info(
                sprintf('Could not find a front end user with the username "%s"', $username),
                ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS, '')]
            );

            return null;
        }

        $frontendGroups = StringUtil::deserialize($frontendUser->groups, true);

        // The front end user does not belong to a group that the back end user is allowed to log in
        if (!$this->security->isGranted('contao_user.amg', $frontendGroups)) {
            return null;
        }

        return $frontendUser;
    }
}
