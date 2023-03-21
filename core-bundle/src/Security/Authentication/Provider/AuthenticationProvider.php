<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Authentication\Provider;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\Exception\LockedException;
use Contao\System;
use Contao\User;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextFactoryInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Handler\AuthenticationHandlerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @deprecated Deprecated since Contao 4.13, to be removed in Contao 5.0.
 *             Use the new authenticator system instead.
 */
class AuthenticationProvider extends DaoAuthenticationProvider
{
    private UserCheckerInterface $userChecker;
    private string $providerKey;
    private ContaoFramework $framework;
    private AuthenticationProviderInterface $twoFactorAuthenticationProvider;
    private AuthenticationHandlerInterface $twoFactorAuthenticationHandler;
    private AuthenticationContextFactoryInterface $authenticationContextFactory;
    private RequestStack $requestStack;
    private TrustedDeviceManagerInterface $trustedDeviceManager;

    /**
     * @internal
     */
    public function __construct(UserProviderInterface $userProvider, UserCheckerInterface $userChecker, string $providerKey, PasswordHasherFactoryInterface $passwordHasherFactory, ContaoFramework $framework, AuthenticationProviderInterface $twoFactorAuthenticationProvider, AuthenticationHandlerInterface $twoFactorAuthenticationHandler, AuthenticationContextFactoryInterface $authenticationContextFactory, RequestStack $requestStack, TrustedDeviceManagerInterface $trustedDeviceManager)
    {
        parent::__construct($userProvider, $userChecker, $providerKey, $passwordHasherFactory, false);

        $this->userChecker = $userChecker;
        $this->providerKey = $providerKey;
        $this->framework = $framework;
        $this->twoFactorAuthenticationProvider = $twoFactorAuthenticationProvider;
        $this->twoFactorAuthenticationHandler = $twoFactorAuthenticationHandler;
        $this->authenticationContextFactory = $authenticationContextFactory;
        $this->requestStack = $requestStack;
        $this->trustedDeviceManager = $trustedDeviceManager;
    }

    public function authenticate(TokenInterface $token): TokenInterface
    {
        if ($token instanceof TwoFactorTokenInterface) {
            return $this->checkTwoFactor($token);
        }

        $wasAlreadyAuthenticated = $token->isAuthenticated();
        $token = parent::authenticate($token);

        // Only trigger two-factor authentication when the provider was called
        // with an unauthenticated token. When we get an authenticated token,
        // the system will refresh it and starting two-factor authentication
        // would trigger an endless loop.
        if ($wasAlreadyAuthenticated) {
            return $token;
        }

        // AnonymousToken and TwoFactorTokenInterface can be ignored.
        if ($token instanceof AnonymousToken || $token instanceof TwoFactorTokenInterface) {
            return $token;
        }

        // Skip two-factor authentication on trusted devices
        if ($this->trustedDeviceManager->isTrustedDevice($token->getUser(), $this->providerKey)) {
            return $token;
        }

        $request = $this->requestStack->getMainRequest();
        $context = $this->authenticationContextFactory->create($request, $token, $this->providerKey);

        return $this->twoFactorAuthenticationHandler->beginTwoFactorAuthentication($context);
    }

    public function supports(TokenInterface $token): bool
    {
        return parent::supports($token) || $this->twoFactorAuthenticationProvider->supports($token);
    }

    public function checkAuthentication(UserInterface $user, UsernamePasswordToken $token): void
    {
        if (!$user instanceof User) {
            parent::checkAuthentication($user, $token);

            return;
        }

        try {
            parent::checkAuthentication($user, $token);
        } catch (AuthenticationException $exception) {
            if (!$exception instanceof BadCredentialsException) {
                throw $exception;
            }

            if (!$this->triggerCheckCredentialsHook($user, $token)) {
                $exception = new BadCredentialsException(
                    sprintf('Invalid password submitted for username "%s"', $user->username),
                    $exception->getCode(),
                    $exception
                );

                throw $this->onBadCredentials($user, $exception);
            }
        }
    }

    private function checkTwoFactor(TokenInterface $token): TokenInterface
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return $this->twoFactorAuthenticationProvider->authenticate($token);
        }

        try {
            $this->userChecker->checkPreAuth($user);
            $token = $this->twoFactorAuthenticationProvider->authenticate($token);
            $this->userChecker->checkPostAuth($user);

            return $token;
        } catch (AuthenticationException $exception) {
            if (!$exception instanceof InvalidTwoFactorCodeException) {
                throw $exception;
            }

            $exception = new InvalidTwoFactorCodeException(
                sprintf('Invalid two-factor code submitted for username "%s"', $user->username),
                $exception->getCode(),
                $exception
            );

            throw $this->onBadCredentials($user, $exception);
        }
    }

    /**
     * Counts the login attempts and locks the user after three failed attempts
     * following a specific delay scheme.
     *
     * After the third failed attempt A, the authentication server waits for an
     * increased (A - 2) * 60 seconds. After 3 attempts, the server waits for 60 seconds,
     * at the fourth failed attempt, it waits for 2 * 60 = 120 seconds and so on.
     */
    private function onBadCredentials(User $user, AuthenticationException $exception): AuthenticationException
    {
        ++$user->loginAttempts;

        if ($user->loginAttempts < 3) {
            $user->save();

            return $exception;
        }

        $lockedSeconds = ($user->loginAttempts - 2) * 60;

        $user->locked = time() + $lockedSeconds;
        $user->save();

        $exception = new LockedException(
            $lockedSeconds,
            sprintf('User "%s" has been locked for %s seconds', $user->username, $lockedSeconds),
            0,
            $exception
        );

        $exception->setUser($user);

        return $exception;
    }

    private function triggerCheckCredentialsHook(User $user, UsernamePasswordToken $token): bool
    {
        $this->framework->initialize();

        if (empty($GLOBALS['TL_HOOKS']['checkCredentials']) || !\is_array($GLOBALS['TL_HOOKS']['checkCredentials'])) {
            return false;
        }

        trigger_deprecation('contao/core-bundle', '4.5', 'Using the "checkCredentials" hook has been deprecated and will no longer work in Contao 5.0.');

        $system = $this->framework->getAdapter(System::class);
        $username = $token->getUserIdentifier();
        $credentials = $token->getCredentials();

        foreach ($GLOBALS['TL_HOOKS']['checkCredentials'] as $callback) {
            if ($system->importStatic($callback[0])->{$callback[1]}($username, $credentials, $user)) {
                return true;
            }
        }

        return false;
    }
}
