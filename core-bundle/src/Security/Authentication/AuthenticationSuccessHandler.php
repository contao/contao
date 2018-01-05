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

use Contao\BackendUser;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\FrontendUser;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\HttpUtils;

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
     * @var User|UserInterface
     */
    private $user;

    /**
     * @param HttpUtils                $httpUtils
     * @param ContaoFrameworkInterface $framework
     * @param LoggerInterface|null     $logger
     */
    public function __construct(HttpUtils $httpUtils, ContaoFrameworkInterface $framework, LoggerInterface $logger = null)
    {
        parent::__construct($httpUtils);

        $this->framework = $framework;
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
        $this->user = $token->getUser();

        if (!$this->user instanceof User) {
            return $this->getRedirectResponse($request);
        }

        $this->updateUserAndSession($request);

        if (null !== $this->logger) {
            $this->logger->info(
                sprintf('User "%s" has logged in', $this->user->username),
                ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS, $this->user->username)]
            );
        }

        $this->triggerPostLoginHook();

        return $this->getRedirectResponse($request);
    }

    /**
     * {@inheritdoc}
     */
    protected function determineTargetUrl(Request $request): string
    {
        if (!$this->user instanceof FrontendUser) {
            return parent::determineTargetUrl($request);
        }

        if ($targetUrl = $this->getFixedTargetPath($request)) {
            return $targetUrl;
        }

        /** @var PageModel $pageModelAdapter */
        $pageModelAdapter = $this->framework->getAdapter(PageModel::class);
        $groups = StringUtil::deserialize($this->user->groups, true);
        $groupPage = $pageModelAdapter->findFirstActiveByMemberGroups($groups);

        if ($groupPage instanceof PageModel) {
            return $groupPage->getAbsoluteUrl();
        }

        return parent::determineTargetUrl($request);
    }

    /**
     * Generates a redirect response.
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    private function getRedirectResponse(Request $request): RedirectResponse
    {
        return $this->httpUtils->createRedirectResponse($request, $this->determineTargetUrl($request));
    }

    /**
     * Updates the user and the session.
     *
     * @param Request $request
     */
    private function updateUserAndSession(Request $request): void
    {
        $this->user->lastLogin = $this->user->currentLogin;
        $this->user->currentLogin = time();
        $this->user->save();

        if (\is_array($this->user->session)) {
            $this->getSessionBag($request, $this->user)->replace($this->user->session);
        }
    }

    /**
     * Returns the session bag.
     *
     * @param Request $request
     * @param User    $user
     *
     * @throws \RuntimeException
     *
     * @return AttributeBagInterface|SessionBagInterface
     */
    private function getSessionBag(Request $request, User $user): AttributeBagInterface
    {
        $session = $request->getSession();

        if (null === $session) {
            throw new \RuntimeException('The request did not contain a session.');
        }

        if ($user instanceof BackendUser) {
            return $session->getBag('contao_backend');
        }

        if ($user instanceof FrontendUser) {
            return $session->getBag('contao_frontend');
        }

        throw new \RuntimeException(sprintf('Unsupported user class "%s".', \get_class($user)));
    }

    /**
     * Triggers the postLogin hook.
     */
    private function triggerPostLoginHook(): void
    {
        $this->framework->initialize();

        if (empty($GLOBALS['TL_HOOKS']['postLogin']) || !\is_array($GLOBALS['TL_HOOKS']['postLogin'])) {
            return;
        }

        @trigger_error('Using the "postLogin" hook has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);

        foreach ($GLOBALS['TL_HOOKS']['postLogin'] as $callback) {
            $this->framework->createInstance($callback[0])->{$callback[1]}($this->user);
        }
    }

    /**
     * Returns the fixed target path.
     *
     * @param Request $request
     *
     * @return string|null
     */
    private function getFixedTargetPath(Request $request): ?string
    {
        if (!$request->request->get('_always_use_target_path')) {
            return null;
        }

        return $request->request->get('_target_path');
    }
}
