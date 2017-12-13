<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Security;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Date;
use Contao\FrontendUser;
use Contao\Idna;
use Contao\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Translation\TranslatorInterface;

class UserChecker implements UserCheckerInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * @param TranslatorInterface      $translator
     * @param \Swift_Mailer            $mailer
     * @param Session                  $session
     * @param ScopeMatcher             $scopeMatcher
     * @param RequestStack             $requestStack
     * @param ContaoFrameworkInterface $framework
     * @param LoggerInterface|null     $logger
     */
    public function __construct(TranslatorInterface $translator, \Swift_Mailer $mailer, Session $session, ScopeMatcher $scopeMatcher, RequestStack $requestStack, ContaoFrameworkInterface $framework, LoggerInterface $logger = null)
    {
        $this->translator = $translator;
        $this->mailer = $mailer;
        $this->session = $session;
        $this->scopeMatcher = $scopeMatcher;
        $this->requestStack = $requestStack;
        $this->framework = $framework;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        $this->checkLoginAttempts($user);
        $this->checkIfAccountIsLocked($user);
        $this->checkIfAccountIsDisabled($user);
        $this->checkIfLoginIsAllowed($user);
        $this->checkIfAccountIsActive($user);
    }

    /**
     * {@inheritdoc}
     */
    public function checkPostAuth(UserInterface $user): void
    {
    }

    /**
     * Locks the account if there are too many login attempts.
     *
     * @param User $user
     *
     * @throws \RuntimeException
     */
    private function checkLoginAttempts(User $user): void
    {
        if ($user->loginCount > 0) {
            return;
        }

        $this->framework->initialize();

        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);

        $time = time();
        $user->locked = $time;
        $user->loginCount = (int) $config->get('loginCount');
        $user->save();

        $lockMinutes = ceil((int) $config->get('lockPeriod') / 60);

        $this->setAccountLockedFlashBag($user);

        if (null !== $this->logger) {
            $this->logger->info(
                sprintf('User "%s" has been locked for %s minutes', $user->getUsername(), $lockMinutes),
                ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS)]
            );
        }

        // Send admin notification
        if ($config->get('adminEmail')) {
            $request = $this->requestStack->getCurrentRequest();

            if (null === $request) {
                throw new \RuntimeException('The request stack did not contain a request');
            }

            $realName = $user->name;

            if ($this->scopeMatcher->isFrontendRequest($request)) {
                $realName = sprintf('%s %s', $user->firstname, $user->lastname);
            }

            $website = Idna::decode($request->getSchemeAndHttpHost());
            $subject = $this->translator->trans('MSC.lockedAccount.0', [], 'contao_default');

            $body = $this->translator->trans(
                'MSC.lockedAccount.1',
                [$user->getUsername(), $realName, $website, $lockMinutes],
                'contao_default'
            );

            $email = new \Swift_Message();

            $email
                ->setTo($config->get('adminEmail'))
                ->setSubject($subject)
                ->setBody($body, 'text/plain')
            ;

            $this->mailer->send($email);
        }

        throw new LockedException(sprintf('This account (%s) has been locked!', $user->getUsername()));
    }

    /**
     * Checks whether the account is locked.
     *
     * @param User $user
     */
    private function checkIfAccountIsLocked(User $user): void
    {
        if (false !== $user->isAccountNonLocked()) {
            return;
        }

        $this->setAccountLockedFlashBag($user);

        throw new LockedException(sprintf('This account (%s) has been locked!', $user->getUsername()));
    }

    /**
     * Check whether the account is disabled.
     *
     * @param User $user
     */
    private function checkIfAccountIsDisabled(User $user): void
    {
        if (false !== $user->isEnabled()) {
            return;
        }

        $this->setInvalidLoginFlashBag();

        if (null !== $this->logger) {
            $this->logger->info(
                'The account has been disabled',
                ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS)]
            );
        }

        throw new DisabledException(sprintf('This account (%s) has been disabled!', $user->getUsername()));
    }

    /**
     * Check wether login is allowed (front end only).
     *
     * @param User $user
     */
    private function checkIfLoginIsAllowed(User $user): void
    {
        if ($user->login || !$user instanceof FrontendUser) {
            return;
        }

        $this->setInvalidLoginFlashBag();

        if (null !== $this->logger) {
            $this->logger->info(
                sprintf('User "%s" is not allowed to log in', $user->getUsername()),
                ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS)]
            );
        }

        throw new DisabledException(sprintf('This user (%s) is not allowed to login.', $user->getUsername()));
    }

    /**
     * Check whether the account is not active yet or not anymore.
     *
     * @param User $user
     */
    private function checkIfAccountIsActive(User $user): void
    {
        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);

        $start = (int) $user->start;
        $stop = (int) $user->stop;
        $time = Date::floorToMinute(time());
        $notActiveYet = $start && $start > $time;
        $notActiveAnymore = $stop && $stop <= ($time + 60);
        $logMessage = '';

        if ($notActiveYet) {
            $logMessage = sprintf(
                'The account is not active yet (activation date: %s)',
                Date::parse($config->get('dateFormat'), $start)
            );
        }

        if ($notActiveAnymore) {
            $logMessage = sprintf(
                'The account is not active anymore (deactivation date: %s)',
                Date::parse($config->get('dateFormat'), $stop)
            );
        }

        if ('' === $logMessage) {
            return;
        }

        $this->setInvalidLoginFlashBag();

        if (null !== $this->logger) {
            $this->logger->info(
                $logMessage,
                ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS)]
            );
        }

        throw new DisabledException(sprintf('This account (%s) is not active', $user->getUsername()));
    }

    /**
     * Adds the "invalid login" flash message.
     */
    private function setInvalidLoginFlashBag(): void
    {
        $this->session->getFlashBag()->set(
            $this->getFlashType(),
            $this->translator->trans('ERR.invalidLogin', [], 'contao_default')
        );
    }

    /**
     * Adds the "account locked" flash message.
     *
     * @param User $user
     */
    private function setAccountLockedFlashBag(User $user): void
    {
        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);

        $this->session->getFlashBag()->set(
            $this->getFlashType(),
            $this->translator->trans(
                'ERR.accountLocked',
                [ceil((($user->locked + (int) $config->get('lockPeriod')) - time()) / 60)],
                'contao_default'
            )
        );
    }

    /**
     * Returns the flash type depending on the provider key.
     *
     * @return string
     */
    private function getFlashType(): string
    {
        $type = '';
        $request = $this->requestStack->getCurrentRequest();

        if ($request && $this->scopeMatcher->isFrontendRequest($request)) {
            $type = 'contao.FE.error';
        }

        if ($request && $this->scopeMatcher->isBackendRequest($request)) {
            $type = 'contao.BE.error';
        }

        return $type;
    }
}
