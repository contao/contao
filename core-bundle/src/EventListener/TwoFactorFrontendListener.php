<?php

declare(strict_types=1);

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\PageModel;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

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

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(ScopeMatcher $scopeMatcher, TokenStorage $tokenStorage, UrlGeneratorInterface $urlGenerator)
    {
        $this->scopeMatcher = $scopeMatcher;
        $this->tokenStorage = $tokenStorage;
        $this->urlGenerator = $urlGenerator;
    }

    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->scopeMatcher->isFrontendRequest($request)) {
            return;
        }

        if (!$token = $this->tokenStorage->getToken() instanceof TwoFactorToken) {
            return;
        }

        $page = $request->attributes->get('pageModel');

        if (!$page instanceof PageModel) {
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
