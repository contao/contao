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
    private ContaoFramework $framework;
    private RequestMatcherInterface $pageRequestMatcher;
    private RequestMatcherInterface $notFoundRequestMatcher;

    public function __construct(ContaoFramework $framework, RequestMatcherInterface $pageRequestMatcher, RequestMatcherInterface $notFoundRequestMatcher)
    {
        $this->framework = $framework;
        $this->pageRequestMatcher = $pageRequestMatcher;
        $this->notFoundRequestMatcher = $notFoundRequestMatcher;
    }

    /**
     * Find the root page matching the request host and optionally an Accept-Language header.
     * If $acceptLanguage is not given, it will always return the fallback root page.
     */
    public function findRootPageForHost(string $hostname, string $acceptLanguage = null): ?PageModel
    {
        $request = Request::create('http://'.$hostname);
        $request->headers->set('Accept-Language', $acceptLanguage ?? '');

        return $this->matchRootPageForRequest($request);
    }

    /**
     * Finds all root pages matching the given host name. It will first look for a matching
     * root page through routing and then find all root pages with the same tl_page.dns value.
     *
     * @return array<PageModel>
     */
    public function findAllRootPagesForHost(string $hostname): array
    {
        $pageModel = $this->findRootPageForHost($hostname);

        if (null === $pageModel) {
            return [];
        }

        $this->framework->initialize();
        $rootPages = $this->framework->getAdapter(PageModel::class)->findPublishedRootPages(['dns' => $pageModel->dns]);

        /** @var array<PageModel> $models */
        $models = $rootPages ? $rootPages->getModels() : [];

        return $models;
    }

    /**
     * Finds the root page matching the request host and Accept-Language.
     */
    public function findRootPageForRequest(Request $request): ?PageModel
    {
        $this->framework->initialize();

        if (($pageModel = $request->attributes->get('pageModel')) instanceof PageModel) {
            return $this->framework->getAdapter(PageModel::class)->findPublishedById($pageModel->loadDetails()->rootId);
        }

        return $this->findRootPageForHost($request->getHost(), $request->headers->get('Accept-Language'));
    }

    /**
     * Finds all root pages matching the host of the given request. It will first look for a matching
     * root page through routing and then find all root pages with the same tl_page.dns value.
     *
     * @return array<PageModel>
     */
    public function findAllRootPagesForRequest(Request $request): array
    {
        return $this->findAllRootPagesForHost($request->getHost());
    }

    /**
     * Finds the first sub-page of a given page for a request host and Accept-Language.
     * This is mainly useful to retrieve an error page for the current host,
     * or any other page type that only exists once per root page.
     */
    public function findFirstPageOfTypeForRequest(Request $request, string $type): ?PageModel
    {
        $pageModel = $request->attributes->get('pageModel');

        if (!$pageModel instanceof PageModel) {
            $pageModel = $this->findRootPageForRequest($request);
        }

        if (!$pageModel instanceof PageModel) {
            return null;
        }

        $this->framework->initialize();

        return $this->framework->getAdapter(PageModel::class)->findFirstPublishedByTypeAndPid($type, $pageModel->loadDetails()->rootId);
    }

    private function matchRootPageForRequest(Request $request): ?PageModel
    {
        $pageModel = $this->matchPageForRequest($request);

        if (null === $pageModel) {
            return null;
        }

        if ('root' === $pageModel->type) {
            return $pageModel;
        }

        $this->framework->initialize();

        return $this->framework->getAdapter(PageModel::class)->findPublishedById($pageModel->loadDetails()->rootId);
    }

    private function matchPageForRequest(Request $request): ?PageModel
    {
        try {
            $arrParameters = $this->pageRequestMatcher->matchRequest($request);
        } catch (RoutingExceptionInterface $exception) {
            try {
                $arrParameters = $this->notFoundRequestMatcher->matchRequest($request);
            } catch (RoutingExceptionInterface $exception) {
                return null;
            }
        }

        $pageModel = $arrParameters['pageModel'] ?? null;

        return $pageModel instanceof PageModel ? $pageModel : null;
    }
}
