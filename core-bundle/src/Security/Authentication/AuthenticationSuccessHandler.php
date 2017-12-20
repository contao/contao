<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Security\Authentication;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Translation\TranslatorInterface;

class AuthenticationSuccessHandler extends DefaultAuthenticationSuccessHandler
{
    /**
     * @var ContaoFrameworkInterface
     */
    protected $framework;

    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param HttpUtils                $httpUtils
     * @param ContaoFrameworkInterface $framework
     * @param TranslatorInterface      $translator
     * @param LoggerInterface|null     $logger
     */
    public function __construct(HttpUtils $httpUtils, ContaoFrameworkInterface $framework, TranslatorInterface $translator, LoggerInterface $logger = null)
    {
        parent::__construct($httpUtils);

        $this->framework = $framework;
        $this->translator = $translator;
        $this->logger = $logger;
    }

    /**
     * Redirects the authenticated user.
     *
     * @param Request        $request
     * @param TokenInterface $token
     *
     * @return RedirectResponse
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return $this->httpUtils->createRedirectResponse($request, $this->determineTargetUrl($request));
        }

        $this->framework->initialize();

        $user->lastLogin = $user->currentLogin;
        $user->currentLogin = time();
        $user->save();

        if ($user->language) {
            $this->setLocale($request, $user->language);
        }

        if (null !== $this->logger) {
            $this->logger->info(
                sprintf('User "%s" has logged in', $user->username),
                ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS, $user->username)]
            );
        }

        $this->triggerPostLoginHook($user);

        return $this->httpUtils->createRedirectResponse($request, $this->determineTargetUrl($request));
    }

    /**
     * Triggers the postLogin hook.
     *
     * @param User $user
     */
    private function triggerPostLoginHook(User $user): void
    {
        if (empty($GLOBALS['TL_HOOKS']['postLogin']) || !\is_array($GLOBALS['TL_HOOKS']['postLogin'])) {
            return;
        }

        @trigger_error('Using the "postLogin" hook has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);

        foreach ($GLOBALS['TL_HOOKS']['postLogin'] as $callback) {
            $this->framework->createInstance($callback[0])->{$callback[1]}($user);
        }
    }

    /**
     * Stores the locale after a user has logged in.
     *
     * @param Request $request
     * @param string  $locale
     */
    private function setLocale(Request $request, string $locale): void
    {
        if (null !== ($session = $request->getSession()) && $session->isStarted()) {
            $session->set('_locale', $locale);
        }

        $request->setLocale($locale);
        $this->translator->setLocale($locale);

        // Deprecated since Contao 4.0, to be removed in Contao 5.0
        $GLOBALS['TL_LANGUAGE'] = str_replace('_', '-', $locale);
    }
}
