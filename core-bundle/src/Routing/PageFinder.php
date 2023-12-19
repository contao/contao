<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingExceptionInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

class PageFinder
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly RequestMatcherInterface $requestMatcher,
    ) {
    }

    /**
     * Finds the root page matching the request host and optionally an Accept-Language header.
     * If $acceptLanguage is not given, it will always return the fallback root page.
     */
    public function findRootPageForHostAndLanguage(string $hostname, string|null $acceptLanguage = null): PageModel|null
    {
        if ($hostname) {
            $hostname = "http://$hostname";
        }

        $request = Request::create($hostname);
        $request->headers->set('Accept-Language', $acceptLanguage ?? '');

        return $this->matchRootPageForRequest($request);
    }

    /**
     * Finds the root page matching the request host and Accept-Language.
     */
    public function findRootPageForRequest(Request $request): PageModel|null
    {
        $pageModel = $request->attributes->get('pageModel');

        if ($pageModel instanceof PageModel) {
            $this->framework->initialize();

            return $this->framework->getAdapter(PageModel::class)->findPublishedById($pageModel->loadDetails()->rootId);
        }

        return $this->findRootPageForHostAndLanguage($request->getHost(), $request->headers->get('Accept-Language'));
    }

    /**
     * Finds all root pages matching the given host name. It will first look for a matching
     * root page through routing and then find all root pages with the same tl_page.dns value.
     *
     * @return array<PageModel>
     */
    public function findRootPagesForHost(string $hostname): array
    {
        $pageModel = $this->findRootPageForHostAndLanguage($hostname);

        if (!$pageModel) {
            return [];
        }

        $this->framework->initialize();

        $rootPages = $this->framework->getAdapter(PageModel::class)->findPublishedRootPages(['dns' => $pageModel->dns]);

        return $rootPages ? $rootPages->getModels() : [];
    }

    /**
     * Finds the first sub-page of a given type for a request host and Accept-Language. This
     * is mainly useful to retrieve an error page for the current host, or any other page type
     * that only exists once per root page.
     */
    public function findFirstPageOfTypeForRequest(Request $request, string $type): PageModel|null
    {
        $pageModel = $request->attributes->get('pageModel');

        if (!$pageModel instanceof PageModel) {
            $pageModel = $this->findRootPageForRequest($request);

            if (!$pageModel instanceof PageModel) {
                return null;
            }
        }

        $this->framework->initialize();

        return $this->framework->getAdapter(PageModel::class)->findFirstPublishedByTypeAndPid($type, $pageModel->loadDetails()->rootId);
    }

    private function matchRootPageForRequest(Request $request): PageModel|null
    {
        $pageModel = $this->matchPageForRequest($request);

        if (!$pageModel) {
            return null;
        }

        if ('root' === $pageModel->type) {
            return $pageModel;
        }

        $this->framework->initialize();

        return $this->framework->getAdapter(PageModel::class)->findPublishedById($pageModel->loadDetails()->rootId);
    }

    private function matchPageForRequest(Request $request): PageModel|null
    {
        try {
            $parameters = $this->requestMatcher->matchRequest($request);
        } catch (RoutingExceptionInterface) {
            return null;
        }

        $pageModel = $parameters['pageModel'] ?? null;

        return $pageModel instanceof PageModel ? $pageModel : null;
    }
}
