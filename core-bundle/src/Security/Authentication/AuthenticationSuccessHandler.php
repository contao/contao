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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\FrontendUser;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\User;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    use TargetPathTrait;

    /**
     * @var ContaoFramework
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
     * @internal Do not inherit from this class; decorate the "contao.security.authentication_success_handler" service instead
     */
    public function __construct(ContaoFramework $framework, LoggerInterface $logger = null)
    {
        $this->framework = $framework;
        $this->logger = $logger;
    }

    /**
     * Redirects the authenticated user.
     *
     * @return RedirectResponse
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return new RedirectResponse($this->determineTargetUrl($request));
        }

        $this->user = $user;

        // Reset login attempts and locked values
        $this->user->loginAttempts = 0;
        $this->user->locked = 0;

        if ($token instanceof TwoFactorTokenInterface) {
            $this->user->save();

            $response = new RedirectResponse($request->getUri());

            $this->saveTargetPath($request->getSession(), $token->getProviderKey(), $response->getTargetUrl());

            return $response;
        }

        $this->user->lastLogin = $this->user->currentLogin;
        $this->user->currentLogin = time();
        $this->user->save();

        $response = new RedirectResponse($this->determineTargetUrl($request));

        if (null !== $this->logger) {
            $this->logger->info(
                sprintf('User "%s" has logged in', $this->user->username),
                ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS, $this->user->username)]
            );
        }

        $this->triggerPostLoginHook();

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function determineTargetUrl(Request $request): string
    {
        if (!$this->user instanceof FrontendUser || $request->request->get('_always_use_target_path')) {
            return base64_decode($request->request->get('_target_path'), true);
        }

        /** @var PageModel $pageModelAdapter */
        $pageModelAdapter = $this->framework->getAdapter(PageModel::class);
        $groups = StringUtil::deserialize($this->user->groups, true);
        $groupPage = $pageModelAdapter->findFirstActiveByMemberGroups($groups);

        if ($groupPage instanceof PageModel) {
            return $groupPage->getAbsoluteUrl();
        }

        return base64_decode($request->request->get('_target_path'), true);
    }

    private function triggerPostLoginHook(): void
    {
        $this->framework->initialize();

        if (empty($GLOBALS['TL_HOOKS']['postLogin']) || !\is_array($GLOBALS['TL_HOOKS']['postLogin'])) {
            return;
        }

        @trigger_error('Using the "postLogin" hook has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);

        /** @var System $system */
        $system = $this->framework->getAdapter(System::class);

        foreach ($GLOBALS['TL_HOOKS']['postLogin'] as $callback) {
            $system->importStatic($callback[0])->{$callback[1]}($this->user);
        }
    }
}
