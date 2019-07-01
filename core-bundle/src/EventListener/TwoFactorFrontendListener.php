<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\FrontendUser;
use Contao\PageModel;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class TwoFactorFrontendListener
{
    use TargetPathTrait;

    /** @var ContaoFramework */
    private $framework;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var array
     */
    private $supportedTokens;

    public function __construct(ContaoFramework $framework, ScopeMatcher $scopeMatcher, TokenStorageInterface $tokenStorage, array $supportedTokens)
    {
        $this->framework = $framework;
        $this->scopeMatcher = $scopeMatcher;
        $this->tokenStorage = $tokenStorage;
        $this->supportedTokens = $supportedTokens;
    }

    public function onKernelRequest(GetResponseEvent $event): void
    {
        // Check if is frontend request
        if (!$this->scopeMatcher->isFrontendMasterRequest($event)) {
            return;
        }

        $request = $event->getRequest();
        $token = $this->tokenStorage->getToken();

        // Check if is a supported token
        if (!$token instanceof TwoFactorToken && !\in_array(get_class($token), $this->supportedTokens)) {
            return;
        }

        $page = $request->attributes->get('pageModel');

        // Check if actual page is available
        if (!$page instanceof PageModel) {
            return;
        }

        $user = $token->getUser();

        // Check if FrontendUser
        if (!$user instanceof FrontendUser) {
            return;
        }

        /** @var PageModel $adapter */
        $adapter = $this->framework->getAdapter(PageModel::class);

        // Check if user has two-factor disabled but is enforced
        if (!$user->useTwoFactor && $page->enforceTwoFactor) {
            // Search for two-factor page
            $twoFactorPage = $adapter->findPublishedById($page->twoFactorJumpTo);

            if (!$twoFactorPage instanceof PageModel) {
                throw new PageNotFoundException('No two-factor authentication page found.');
            }

            // Two-factor page found, quit
            if ($page->id === $twoFactorPage->id) {
                return;
            }

            // Redirect to two-factor page
            $event->setResponse(new RedirectResponse($twoFactorPage->getAbsoluteUrl()));

            return;
        }

        // Check if user has TwoFactorToken
        if (!$token instanceof TwoFactorToken) {
            return;
        }

        // Search 401 error page
        $unauthorizedPage = $adapter->find401ByPid($page->rootId);

        if ($unauthorizedPage instanceof PageModel) {
            return;
        }

        // Check if 401 error page is available
        $redirect = $adapter->findPublishedById($unauthorizedPage->jumpTo);

        if ($redirect instanceof PageModel && $page->id === $redirect->id) {
            return;
        }

        $targetPath = $this->getTargetPath($request->getSession(), $token->getProviderKey());

        if ($targetPath) {
            if ($request->getSchemeAndHttpHost().$request->getRequestUri() === $targetPath) {
                return;
            }

            $event->setResponse(new RedirectResponse($targetPath));
            return;
        }

        throw new UnauthorizedHttpException('', 'Missing two-factor authentication.');
    }
}
