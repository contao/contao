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
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class AuthenticationProvider extends DaoAuthenticationProvider
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var array
     */
    private $options;

    public function __construct(UserProviderInterface $userProvider, UserCheckerInterface $userChecker, string $providerKey, EncoderFactoryInterface $encoderFactory, ContaoFramework $framework, array $options = [])
    {
        parent::__construct($userProvider, $userChecker, $providerKey, $encoderFactory, false);

        $this->framework = $framework;
        $this->options = array_merge(['login_attempts' => 3, 'lock_period' => 300], $options);
    }

    /**
     * {@inheritdoc}
     */
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
                throw $this->onBadCredentials($user, $exception);
            }
        }

        $user->loginCount = $this->options['login_attempts'];
        $user->save();
    }

    /**
     * Counts the login attempts and locks the user if it reaches zero.
     */
    public function onBadCredentials(User $user, AuthenticationException $exception): AuthenticationException
    {
        --$user->loginCount;

        if ($user->loginCount > 0) {
            $user->save();

            return new BadCredentialsException(
                sprintf('Invalid password submitted for username "%s"', $user->username),
                $exception->getCode(),
                $exception
            );
        }

        $user->locked = time() + $this->options['lock_period'];
        $user->loginCount = $this->options['login_attempts'];
        $user->save();

        $lockedSeconds = $user->locked - time();
        $lockedMinutes = (int) ceil($lockedSeconds / 60);

        $exception = new LockedException(
            $lockedSeconds,
            sprintf('User "%s" has been locked for %s minutes', $user->username, $lockedMinutes),
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

        @trigger_error('Using the checkCredentials hook has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);

        /** @var System $system */
        $system = $this->framework->getAdapter(System::class);
        $username = $token->getUsername();
        $credentials = $token->getCredentials();

        foreach ($GLOBALS['TL_HOOKS']['checkCredentials'] as $callback) {
            if ($system->importStatic($callback[0])->{$callback[1]}($username, $credentials, $user)) {
                return true;
            }
        }

        return false;
    }
}
