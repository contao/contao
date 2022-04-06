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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\System;
use Contao\User;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LogoutSuccessListener
{
    use TargetPathTrait;

    private HttpUtils $httpUtils;
    private ScopeMatcher $scopeMatcher;
    private ContaoFramework $framework;
    private Security $security;
    private ?LoggerInterface $logger;

    /**
     * @internal
     */
    public function __construct(HttpUtils $httpUtils, ScopeMatcher $scopeMatcher, ContaoFramework $framework, Security $security, ?LoggerInterface $logger)
    {
        $this->httpUtils = $httpUtils;
        $this->scopeMatcher = $scopeMatcher;
        $this->framework = $framework;
        $this->security = $security;
        $this->logger = $logger;
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

        if ($token instanceof TokenInterface) {
            if ($request->hasSession() && method_exists($token, 'getFirewallName')) {
                $this->removeTargetPath($request->getSession(), $token->getFirewallName());
            }

            $user = $token->getUser();

            if (!$user instanceof User || $token instanceof TwoFactorTokenInterface) {
                return;
            }

            if (null !== $this->logger) {
                $this->logger->info(
                    sprintf('User "%s" has logged out', $user->username),
                    ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS, $user->username)]
                );
            }

            $this->triggerPostLogoutHook($user);
        }
    }

    private function triggerPostLogoutHook(User $user): void
    {
        $this->framework->initialize();

        if (empty($GLOBALS['TL_HOOKS']['postLogout']) || !\is_array($GLOBALS['TL_HOOKS']['postLogout'])) {
            return;
        }

        trigger_deprecation('contao/core-bundle', '4.5', 'Using the "postLogout" hook has been deprecated and will no longer work in Contao 5.0.');

        $system = $this->framework->getAdapter(System::class);

        $GLOBALS['TL_USERNAME'] = $user->getUserIdentifier();

        foreach ($GLOBALS['TL_HOOKS']['postLogout'] as $callback) {
            $system->importStatic($callback[0])->{$callback[1]}($user);
        }
    }
}
