<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\Page;

use Contao\CoreBundle\Controller\AbstractController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsPage;
use Contao\PageModel;
use Contao\System;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;

#[AsPage(path: '', contentComposition: false)]
class LogoutPageController extends AbstractController
{
    public function __construct(
        private readonly LogoutUrlGenerator $logoutUrlGenerator,
    ) {
    }

    public function __invoke(Request $request, PageModel $pageModel): Response
    {
        $redirect = $this->getRedirectUrl($pageModel, $request);

        if (!$this->getUser()) {
            return new RedirectResponse($redirect);
        }

        $pairs = [];
        $logoutUrl = $this->logoutUrlGenerator->getLogoutUrl();
        $request = Request::create($logoutUrl);

        if ($request->server->has('QUERY_STRING')) {
            parse_str($request->server->get('QUERY_STRING'), $pairs);
        }

        // Add the redirect= parameter to the logout URL
        $pairs['redirect'] = $redirect;

        $uri = $request->getSchemeAndHttpHost().$request->getBaseUrl().$request->getPathInfo().'?'.http_build_query($pairs, '', '&', PHP_QUERY_RFC3986);

        return new RedirectResponse($uri, Response::HTTP_TEMPORARY_REDIRECT);
    }

    private function getRedirectUrl(PageModel $pageModel, Request $request): string
    {
        // Redirect to last page visited
        if ($pageModel->redirectBack && $referer = $this->getContaoAdapter(System::class)->getReferer()) {
            return $referer;
        }

        // Redirect to jumpTo page
        if (($jumpTo = $pageModel->getRelated('jumpTo')) instanceof PageModel) {
            return $this->generateContentUrl($jumpTo);
        }

        return $request->getBaseUrl();
    }
}
