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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AuthenticationSuccessHandler extends DefaultAuthenticationSuccessHandler
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

    public function __construct(HttpUtils $httpUtils, ContaoFramework $framework, LoggerInterface $logger = null)
    {
        parent::__construct($httpUtils);

        $this->framework = $framework;
        $this->logger = $logger;
    }

    /**
     * Redirects the authenticated user.
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        if ($token instanceof TwoFactorTokenInterface) {
            $response = $this->httpUtils->createRedirectResponse(
                $request,
                $request->request->get('_failure_path') ?: 'contao_root'
            );

            $this->saveTargetPath($request->getSession(), $token->getProviderKey(), $response->getTargetUrl());

            return $response;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return $this->httpUtils->createRedirectResponse($request, $this->determineTargetUrl($request));
        }

        $this->user = $user;
        $this->user->lastLogin = $this->user->currentLogin;
        $this->user->currentLogin = time();
        $this->user->save();

        $response = $this->httpUtils->createRedirectResponse($request, $this->determineTargetUrl($request));

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

    private function getFixedTargetPath(Request $request): ?string
    {
        if (!$request->request->get('_always_use_target_path')) {
            return null;
        }

        return $request->request->get('_target_path');
    }
}
