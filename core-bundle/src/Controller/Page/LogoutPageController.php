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

use Contao\PageModel;
use Contao\System;
use League\Uri\Components\Query;
use League\Uri\Http;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;

class LogoutPageController extends AbstractPageController
{
    /**
     * @var LogoutUrlGenerator
     */
    private $logoutUrlGenerator;

    public function __construct(LogoutUrlGenerator $logoutUrlGenerator)
    {
        $this->logoutUrlGenerator = $logoutUrlGenerator;
    }

    protected function getResponse(PageModel $pageModel, Request $request): Response
    {
        $logoutUrl = $this->logoutUrlGenerator->getLogoutUrl();
        $redirect = $this->getRedirectUrl($pageModel, $request);

        $uri = Http::createFromString($logoutUrl);

        // Add the redirect= parameter to the logout URL
        $query = new Query($uri->getQuery());
        $query = $query->merge('redirect=' . $redirect);

        return $this->redirect((string) $uri->withQuery((string) $query), Response::HTTP_TEMPORARY_REDIRECT);
    }

    private function getRedirectUrl(PageModel $pageModel, Request $request): string
    {
        // Redirect to last page visited
        if ($pageModel->redirectBack) {
            /** @var System $systemAdapter */
            $systemAdapter = $this->get('contao.framework')->getAdapter(System::class);

            if ($referer = $systemAdapter->getReferer()) {
                return $referer;
            }
        }

        // Redirect to jumpTo page
        if (($jumpTo = $pageModel->getRelated('jumpTo')) instanceof PageModel) {
            return $jumpTo->getAbsoluteUrl();
        }

        return $request->getBaseUrl();
    }
}
