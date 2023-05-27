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
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class FrontendPreviewAuthenticator
{
    final public const SESSION_NAME = '_contao_frontend_preview';

    /**
     * @internal
     */
    public function __construct(
        private Security $security,
        private RequestStack $requestStack,
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

        $token = new UsernamePasswordToken($user, 'contao_frontend', $user->getRoles());

        try {
            $session = $this->requestStack->getSession();
        } catch (SessionNotFoundException) {
            return false;
        }

        $session->set('_security_contao_frontend', serialize($token));
        $session->set(self::SESSION_NAME, ['showUnpublished' => $showUnpublished]);

        return true;
    }

    public function authenticateFrontendGuest(bool $showUnpublished, int $previewLinkId = null): bool
    {
        try {
            $session = $this->requestStack->getSession();
        } catch (SessionNotFoundException) {
            return false;
        }

        $session->set(self::SESSION_NAME, ['previewLinkId' => $previewLinkId, 'showUnpublished' => $showUnpublished]);

        return true;
    }

    /**
     * Removes a front end authentication from the session.
     */
    public function removeFrontendAuthentication(): bool
    {
        try {
            $session = $this->requestStack->getSession();
        } catch (SessionNotFoundException) {
            return false;
        }

        if (!$session->isStarted() || (!$session->has('_security_contao_frontend') && !$session->has(self::SESSION_NAME))) {
            return false;
        }

        $session->remove('_security_contao_frontend');
        $session->remove(self::SESSION_NAME);

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
