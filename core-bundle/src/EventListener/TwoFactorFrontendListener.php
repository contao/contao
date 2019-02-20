<?php

declare(strict_types=1);

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\FrontendUser;
use Contao\PageModel;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class TwoFactorFrontendListener
{
    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    public function __construct(ScopeMatcher $scopeMatcher, TokenStorage $tokenStorage)
    {
        $this->scopeMatcher = $scopeMatcher;
        $this->tokenStorage = $tokenStorage;
    }

    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        $token = $this->tokenStorage->getToken();

        // Check if is frontend request
        if (!$this->scopeMatcher->isFrontendRequest($request)) {
            return;
        }

        // Check if is a supported token
        if (!$token instanceof TwoFactorToken && !$token instanceof UsernamePasswordToken) {
            return;
        }

        $page = $request->attributes->get('pageModel');

        if (!$page instanceof PageModel) {
            return;
        }

        $user = $token->getUser();

        if (!$user instanceof FrontendUser) {
            return;
        }

        if (!$user->useTwoFactor && $page->enforceTwoFactor) {
            $twoFactorPage = PageModel::findPublishedById($page->twofactor_jumpTo);

            if (!$twoFactorPage instanceof PageModel) {
                throw new PageNotFoundException('No two-factor authentication page found.');
            }

            if ($page->id === $twoFactorPage->id) {
                return;
            }

            $event->setResponse(new RedirectResponse($twoFactorPage->getAbsoluteUrl()));

            return;
        }

        if (!$token = $this->tokenStorage->getToken() instanceof TwoFactorToken) {
            return;
        }

        $unauthorizedPage = PageModel::find401ByPid($page->rootId);

        if (!$unauthorizedPage instanceof PageModel) {
            return;
        }

        $redirect = PageModel::findByPk($unauthorizedPage->jumpTo);

        if (!$redirect instanceof PageModel) {
            return;
        }

        if ($page->id === $redirect->id) {
            return;
        }

        throw new UnauthorizedHttpException('Missing two-factor authentication.');
    }
}
