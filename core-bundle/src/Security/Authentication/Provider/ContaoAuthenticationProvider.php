<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Security\Authentication\Provider;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ContaoAuthenticationProvider extends DaoAuthenticationProvider
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $providerKey;

    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * @param UserProviderInterface    $userProvider
     * @param UserCheckerInterface     $userChecker
     * @param string                   $providerKey
     * @param EncoderFactoryInterface  $encoderFactory
     * @param bool                     $hideUserNotFoundExceptions
     * @param Session                  $session
     * @param TranslatorInterface      $translator
     * @param ContaoFrameworkInterface $framework
     * @param LoggerInterface|null     $logger
     */
    public function __construct(UserProviderInterface $userProvider, UserCheckerInterface $userChecker, $providerKey, EncoderFactoryInterface $encoderFactory, $hideUserNotFoundExceptions, Session $session, TranslatorInterface $translator, ContaoFrameworkInterface $framework, LoggerInterface $logger = null)
    {
        parent::__construct($userProvider, $userChecker, $providerKey, $encoderFactory, $hideUserNotFoundExceptions);

        $this->logger = $logger;
        $this->session = $session;
        $this->translator = $translator;
        $this->providerKey = $providerKey;
        $this->framework = $framework;
    }

    /**
     * {@inheritdoc}
     */
    public function checkAuthentication(UserInterface $user, UsernamePasswordToken $token): void
    {
        try {
            parent::checkAuthentication($user, $token);
        } catch (BadCredentialsException $badCredentialsException) {
            if (!$user instanceof User) {
                throw $badCredentialsException;
            }

            if (false === $this->triggerCheckCredentialsHook($user, $token)) {
                --$user->loginCount;
                $user->save();

                $this->session->getFlashBag()->set(
                    $this->getFlashType(),
                    $this->translator->trans('ERR.invalidLogin', [], 'contao_default')
                );

                if (null !== $this->logger) {
                    $this->logger->info(
                        sprintf('Invalid password submitted for username "%s"', $user->getUsername()),
                        ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS)]
                    );
                }

                throw $badCredentialsException;
            }
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

        @trigger_error('Using the checkCredentials hook has been deprecated and will no longer work in Contao 5.0. Use the contao.check_credentials event instead.', E_USER_DEPRECATED);

        foreach ($GLOBALS['TL_HOOKS']['checkCredentials'] as $callback) {
            $objectInstance = $this->framework->createInstance($callback[0]);

            if ($objectInstance->{$callback[1]}($token->getUsername(), $token->getCredentials(), $user)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the flash type depending on the provider key.
     *
     * @return string
     */
    private function getFlashType(): string
    {
        if ('contao_frontend' === $this->providerKey) {
            return 'contao.FE.error';
        }

        return 'contao.BE.error';
    }
}
