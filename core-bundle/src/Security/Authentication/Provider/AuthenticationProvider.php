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

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Security\Exception\LockedException;
use Contao\FrontendUser;
use Contao\Idna;
use Contao\System;
use Contao\User;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class AuthenticationProvider extends DaoAuthenticationProvider
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var array
     */
    private $options;

    /**
     * @param UserProviderInterface    $userProvider
     * @param UserCheckerInterface     $userChecker
     * @param string                   $providerKey
     * @param EncoderFactoryInterface  $encoderFactory
     * @param ContaoFrameworkInterface $framework
     * @param TranslatorInterface      $translator
     * @param RequestStack             $requestStack
     * @param \Swift_Mailer            $mailer
     * @param array                    $options
     */
    public function __construct(UserProviderInterface $userProvider, UserCheckerInterface $userChecker, $providerKey, EncoderFactoryInterface $encoderFactory, ContaoFrameworkInterface $framework, TranslatorInterface $translator, RequestStack $requestStack, \Swift_Mailer $mailer, array $options = [])
    {
        parent::__construct($userProvider, $userChecker, $providerKey, $encoderFactory, false);

        $this->framework = $framework;
        $this->translator = $translator;
        $this->requestStack = $requestStack;
        $this->mailer = $mailer;
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
     *
     * @param User                    $user
     * @param AuthenticationException $exception
     *
     * @return AuthenticationException
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

        $this->sendLockedEmail($user, $lockedMinutes);

        $exception = new LockedException(
            $lockedSeconds,
            sprintf('User "%s" has been locked for %s minutes', $user->username, $lockedMinutes),
            0,
            $exception
        );

        $exception->setUser($user);

        return $exception;
    }

    /**
     * Notifies the administrator of the locked account.
     *
     * @param User $user
     * @param int  $lockedMinutes
     *
     * @throws \RuntimeException
     */
    private function sendLockedEmail(User $user, int $lockedMinutes): void
    {
        $this->framework->initialize();

        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);

        if ($adminEmail = $config->get('adminEmail')) {
            $request = $this->requestStack->getMasterRequest();

            if (null === $request) {
                throw new \RuntimeException('The request stack did not contain a request');
            }

            $realName = $user->name;

            if ($user instanceof FrontendUser) {
                $realName = sprintf('%s %s', $user->firstname, $user->lastname);
            }

            $website = Idna::decode($request->getSchemeAndHttpHost());
            $subject = $this->translator->trans('MSC.lockedAccount.0', [], 'contao_default');

            $body = $this->translator->trans(
                'MSC.lockedAccount.1',
                [$user->username, $realName, $website, $lockedMinutes],
                'contao_default'
            );

            $email = new \Swift_Message();

            $email
                ->setFrom($adminEmail)
                ->setTo($adminEmail)
                ->setSubject($subject)
                ->setBody($body, 'text/plain')
            ;

            $this->mailer->send($email);
        }
    }

    /**
     * Triggers the checkCredentials hook.
     *
     * @param User                  $user
     * @param UsernamePasswordToken $token
     *
     * @return bool
     */
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
