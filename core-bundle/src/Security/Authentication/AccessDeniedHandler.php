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

use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\PageFinder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(
        private readonly PageFinder $pageFinder,
        private readonly PageRegistry $pageRegistry,
        private readonly HttpKernelInterface $httpKernel,
    ) {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): Response|null
    {
        $errorPage = $this->pageFinder->findFirstPageOfTypeForRequest($request, 'error_403');

        if (!$errorPage) {
            return null;
        }

        $errorPage->loadDetails();
        $errorPage->protected = false;

        $route = $this->pageRegistry->getRoute($errorPage);
        $subRequest = $request->duplicate(null, null, $route->getDefaults());

        try {
            return $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);
        } catch (ResponseException $e) {
            return $e->getResponse();
        }
    }
}
