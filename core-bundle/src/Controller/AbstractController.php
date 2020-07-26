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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Page\PageRoute;
use FOS\HttpCacheBundle\Http\SymfonyResponseTagger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class AbstractController extends SymfonyAbstractController
{
    public static function getSubscribedServices()
    {
        $services = parent::getSubscribedServices();

        $services['contao.framework'] = ContaoFramework::class;
        $services['fos_http_cache.http.symfony_response_tagger'] = '?'.SymfonyResponseTagger::class;

        return $services;
    }

    protected function initializeContaoFramework(): void
    {
        $this->get('contao.framework')->initialize();
    }

    protected function tagResponse(array $tags): void
    {
        if (!$this->has('fos_http_cache.http.symfony_response_tagger')) {
            return;
        }

        $this->get('fos_http_cache.http.symfony_response_tagger')->addTags($tags);
    }

    /**
     * Uses the Symfony router to generate a URL for the given content.
     *
     * "Content" in this case should be any supported model/entity of Contao,
     * e.g. a PageModel, NewsModel or similar.
     */
    protected function generateContentUrl($content, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        $parameters[PageRoute::CONTENT_PARAMETER] = $content;

        return $this->generateUrl(PageRoute::ROUTE_NAME, $parameters, $referenceType);
    }

    /**
     * Uses the Symfony router to generate redirect response to the URL of the given content.
     *
     * "Content" in this case should be any supported model/entity of Contao,
     * e.g. a PageModel, NewsModel or similar.
     */
    protected function redirectToContent($content, array $parameters = [], int $status = 302): RedirectResponse
    {
        return $this->redirect($this->generateContentUrl($content, $parameters, UrlGeneratorInterface::ABSOLUTE_URL), $status);
    }
}
