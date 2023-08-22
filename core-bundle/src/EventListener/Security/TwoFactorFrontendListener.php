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

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\FrontendUser;
use Contao\PageModel;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * @internal
 */
class TwoFactorFrontendListener
{
    use TargetPathTrait;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly array $supportedTokens,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$this->scopeMatcher->isFrontendMainRequest($event)) {
            return;
        }

        if (!$token = $this->tokenStorage->getToken()) {
            return;
        }

        // Check if is a supported token
        if (!$token instanceof TwoFactorToken && !\in_array($token::class, $this->supportedTokens, true)) {
            return;
        }

        $request = $event->getRequest();
        $page = $request->attributes->get('pageModel');

        // Check if actual page is available
        if (!$page instanceof PageModel) {
            return;
        }

        $user = $token->getUser();

        if (!$user instanceof FrontendUser) {
            return;
        }

        $adapter = $this->framework->getAdapter(PageModel::class);

        // Check if user has two-factor disabled but is enforced
        if ($page->enforceTwoFactor && !$user->useTwoFactor) {
            $twoFactorPage = $adapter->findPublishedById($page->twoFactorJumpTo);

            if (!$twoFactorPage instanceof PageModel) {
                throw new PageNotFoundException('No two-factor authentication page found');
            }

            // Redirect to two-factor page
            if ($page->id !== $twoFactorPage->id) {
                $event->setResponse(new RedirectResponse($twoFactorPage->getAbsoluteUrl()));
            }

            return;
        }

        // Return if user is authenticated
        if (!$token instanceof TwoFactorToken) {
            return;
        }

        $page401 = $adapter->find401ByPid($page->rootId);

        // Return if we are on the 401 target page already
        if ($page401 instanceof PageModel && $page401->autoforward && $page->id === $page401->jumpTo) {
            return;
        }

        $targetPath = $this->getTargetPath($request->getSession(), $token->getFirewallName());

        if ($targetPath) {
            // Redirect to the target path
            if ($targetPath !== $request->getSchemeAndHttpHost().$request->getRequestUri()) {
                $event->setResponse(new RedirectResponse($targetPath));
            }

            return;
        }

        throw new UnauthorizedHttpException('', 'Missing two-factor authentication');
    }
}
