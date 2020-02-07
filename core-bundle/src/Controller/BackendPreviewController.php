<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller;

use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Contao\CoreBundle\Security\Authentication\FrontendPreviewAuthenticator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * This controller handles the back end preview call and redirects to the
 * requested front end page while ensuring that the /preview.php entry point is
 * used. When requested, the front end user gets authenticated.
 *
 * @Route(defaults={"_scope" = "backend"})
 */
class BackendPreviewController
{
    /**
     * @var string
     */
    private $previewScript;

    /**
     * @var FrontendPreviewAuthenticator
     */
    private $previewAuthenticator;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function __construct(string $previewScript, FrontendPreviewAuthenticator $previewAuthenticator, EventDispatcherInterface $dispatcher, RouterInterface $router, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->previewScript = $previewScript;
        $this->previewAuthenticator = $previewAuthenticator;
        $this->dispatcher = $dispatcher;
        $this->router = $router;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @Route("/contao/preview", name="contao_backend_preview")
     */
    public function __invoke(Request $request): Response
    {
        if ($request->getScriptName() !== $this->previewScript) {
            return new RedirectResponse($this->previewScript.$request->getRequestUri());
        }

        if (!$this->authorizationChecker->isGranted('ROLE_USER')) {
            return new Response('Access denied', Response::HTTP_FORBIDDEN);
        }

        // Switch to a particular member (see contao/core#6546)
        if (
            ($frontendUser = $request->query->get('user'))
            && !$this->previewAuthenticator->authenticateFrontendUser($frontendUser, false)
        ) {
            $this->previewAuthenticator->removeFrontendAuthentication();
        }

        $urlConvertEvent = new PreviewUrlConvertEvent($request);

        $this->dispatcher->dispatch($urlConvertEvent, ContaoCoreEvents::PREVIEW_URL_CONVERT);

        if ($targetUrl = $urlConvertEvent->getUrl()) {
            return new RedirectResponse($targetUrl);
        }

        return new RedirectResponse($this->router->generate('contao_root', [], UrlGeneratorInterface::ABSOLUTE_URL));
    }
}
