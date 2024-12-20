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

use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\CoreBundle\Exception\InsufficientAuthenticationException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\FrontendUser;
use Contao\PageModel;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * @internal
 */
#[AsEventListener]
class TwoFactorFrontendListener
{
    use TargetPathTrait;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly PageFinder $pageFinder,
        private readonly ContentUrlGenerator $urlGenerator,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly array $supportedTokens,
    ) {
    }

    /**
     * If we are in the front end, make sure the user completes the two-factor login
     * process, or sets up the two-factor authentication if it is enforced in the root page.
     */
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
        $rootPage = $this->pageFinder->findRootPageForRequest($request);
        $user = $token->getUser();

        // Check if user has two-factor disabled, but it is enforced in the current root page
        if ($rootPage instanceof PageModel && $rootPage->enforceTwoFactor && $user instanceof FrontendUser && !$user->useTwoFactor) {
            $adapter = $this->framework->getAdapter(PageModel::class);
            $twoFactorPage = $adapter->findPublishedById($rootPage->twoFactorJumpTo);

            if (!$twoFactorPage instanceof PageModel) {
                throw new ForwardPageNotFoundException('No two-factor authentication page found');
            }

            // Redirect to two-factor page
            if ($rootPage->id !== $twoFactorPage->id) {
                $event->setResponse(new RedirectResponse($this->urlGenerator->generate($twoFactorPage, [], UrlGeneratorInterface::ABSOLUTE_URL)));
            }

            return;
        }

        // Return if user is authenticated
        if (!$token instanceof TwoFactorToken) {
            return;
        }

        $currentPage = $request->attributes->get('pageModel');

        // Return if we are on the 401 target page already
        if ($currentPage instanceof PageModel) {
            $page401 = $this->pageFinder->findFirstPageOfTypeForRequest($request, 'error_401');

            if ($page401 instanceof PageModel && $page401->autoforward && $currentPage->id === $page401->jumpTo) {
                return;
            }
        }

        $targetPath = $this->getTargetPath($request->getSession(), $token->getFirewallName());

        if ($targetPath) {
            // Redirect to the target path
            if ($targetPath !== $request->getSchemeAndHttpHost().$request->getRequestUri()) {
                $event->setResponse(new RedirectResponse($targetPath));
            }

            return;
        }

        throw new InsufficientAuthenticationException('Missing two-factor authentication');
    }
}
