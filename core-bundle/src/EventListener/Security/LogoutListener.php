<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\Security;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\Authentication\FrontendPreviewAuthenticator;
use Contao\User;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LogoutListener
{
    use TargetPathTrait;

    /**
     * @internal
     */
    public function __construct(
        private readonly HttpUtils $httpUtils,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly Security $security,
        private readonly LoggerInterface|null $logger,
    ) {
    }

    public function __invoke(LogoutEvent $event): void
    {
        $request = $event->getRequest();

        $this->logout($request);

        if ($this->scopeMatcher->isBackendRequest($request)) {
            $event->setResponse($this->httpUtils->createRedirectResponse($request, 'contao_backend_login'));

            return;
        }

        if ($targetUrl = (string) $request->request->get('_target_path')) {
            $event->setResponse($this->httpUtils->createRedirectResponse($request, $targetUrl));

            return;
        }

        if ($targetUrl = (string) $request->query->get('redirect')) {
            $event->setResponse($this->httpUtils->createRedirectResponse($request, $targetUrl));

            return;
        }

        if ($targetUrl = (string) $request->headers->get('Referer')) {
            $event->setResponse($this->httpUtils->createRedirectResponse($request, $targetUrl));
        }
    }

    private function logout(Request $request): void
    {
        $token = $this->security->getToken();

        if ($request->hasSession() && null !== $request->getSession()->get(FrontendPreviewAuthenticator::SESSION_NAME)) {
            $request->getSession()->remove(FrontendPreviewAuthenticator::SESSION_NAME);
        }

        if ($token instanceof TokenInterface) {
            if ($request->hasSession() && method_exists($token, 'getFirewallName')) {
                $this->removeTargetPath($request->getSession(), $token->getFirewallName());
            }

            $user = $token->getUser();

            if (!$user instanceof User || $token instanceof TwoFactorTokenInterface) {
                return;
            }

            $this->logger?->info(
                sprintf('User "%s" has logged out', $user->username),
                ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS, $user->username)],
            );
        }
    }
}
