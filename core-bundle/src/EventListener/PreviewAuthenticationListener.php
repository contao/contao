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

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PreviewAuthenticationListener
{
    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @var TokenChecker
     */
    private $tokenChecker;

    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * @var string
     */
    private $previewScript;

    public function __construct(ScopeMatcher $scopeMatcher, TokenChecker $tokenChecker, UrlGeneratorInterface $router, string $previewScript)
    {
        $this->scopeMatcher = $scopeMatcher;
        $this->tokenChecker = $tokenChecker;
        $this->router = $router;
        $this->previewScript = $previewScript;
    }

    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();

        if (
            '' === $this->previewScript
            || $request->getScriptName() !== $this->previewScript
            || !$this->scopeMatcher->isFrontendRequest($request)
            || $this->tokenChecker->hasBackendUser()
        ) {
            return;
        }

        $event->setResponse(new RedirectResponse($this->router->generate('contao_backend_login')));
    }
}
