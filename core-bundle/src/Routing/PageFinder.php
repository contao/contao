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

    public function findRootPageByHost(string $hostname, bool $https, string $acceptLanguage): ?PageModel
    {
        $request = Request::create(($https ? 'https' : 'http').'://'.$hostname);
        $request->headers->set('Accept-Language', $acceptLanguage);

        return $this->matchRootPageForRequest($request);
    }

    public function findRootPageByRequest(Request $request): ?PageModel
    {
        $pageModel = $request->attributes->get('pageModel');

        if (!$pageModel instanceof PageModel) {
            return $this->findRootPageByHost($request->getHost(), $request->isSecure(), $request->headers->get('Accept-Language'));
        }

        return $this->framework->getAdapter(PageModel::class)->findPublishedById($pageModel->loadDetails()->rootId);
    }

    public function findFirstPageTypeByRequestHost(Request $request, string $type): ?PageModel
    {
        $pageModel = $request->attributes->get('pageModel');

        if (!$pageModel instanceof PageModel) {
            $pageModel = $this->findRootPageByHost($request->getHost(), $request->isSecure(), $request->headers->get('Accept-Language'));
        }

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

        $rootPage = $arrParameters['pageModel'] ?? null;

        return $rootPage instanceof PageModel ? $rootPage : null;
    }
}
