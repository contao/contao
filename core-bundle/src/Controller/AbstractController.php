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

use Contao\CoreBundle\Cache\EntityCacheTags;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\EventListener\MakeResponsePrivateListener;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\PageModel;
use FOS\HttpCacheBundle\Http\SymfonyResponseTagger;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractController extends SymfonyAbstractController
{
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['contao.framework'] = ContaoFramework::class;
        $services['contao.routing.content_url_generator'] = ContentUrlGenerator::class;
        $services['event_dispatcher'] = EventDispatcherInterface::class;
        $services['logger'] = '?'.LoggerInterface::class;
        $services['fos_http_cache.http.symfony_response_tagger'] = '?'.SymfonyResponseTagger::class;
        $services['contao.csrf.token_manager'] = ContaoCsrfTokenManager::class;
        $services['contao.cache.entity_tags'] = EntityCacheTags::class;

        return $services;
    }

    protected function initializeContaoFramework(): void
    {
        $this->container->get('contao.framework')->initialize();
    }

    /**
     * @template T
     *
     * @param class-string<T> $class
     *
     * @return T
     *
     * @phpstan-return Adapter<T>
     */
    protected function getContaoAdapter(string $class): Adapter
    {
        return $this->container->get('contao.framework')->getAdapter($class);
    }

    protected function tagResponse(array|object|string|null $tags): void
    {
        $this->container->get('contao.cache.entity_tags')->tagWith($tags);
    }

    /**
     * @return array{csrf_field_name: string, csrf_token_manager: ContaoCsrfTokenManager, csrf_token_id: string}
     */
    protected function getCsrfFormOptions(): array
    {
        return [
            'csrf_field_name' => 'REQUEST_TOKEN',
            'csrf_token_manager' => $this->container->get('contao.csrf.token_manager'),
            'csrf_token_id' => $this->getParameter('contao.csrf_token_name'),
        ];
    }

    /**
     * Set the cache headers according to the page settings.
     */
    protected function setCacheHeaders(Response $response, PageModel $pageModel): Response
    {
        // Do not cache the response if caching was not configured at all or disabled explicitly
        if ($pageModel->cache < 1 && $pageModel->clientCache < 1) {
            $response->headers->set('Cache-Control', 'no-cache, no-store');

            return $response->setPrivate(); // Make sure the response is private
        }

        // Private cache
        if ($pageModel->clientCache > 0) {
            $response->setMaxAge($pageModel->clientCache);
            $response->setPrivate(); // Make sure the response is private
        }

        // Shared cache
        if ($pageModel->cache > 0) {
            $response->setSharedMaxAge($pageModel->cache); // Automatically sets the response to public

            /**
             * We vary on cookies if a response is cacheable by the shared
             * cache, so a reverse proxy does not load a response from cache if
             * the _request_ contains a cookie.
             *
             * This DOES NOT mean that we generate a cache entry for every
             * response containing a cookie! Responses with cookies will always
             * be private.
             *
             * @see MakeResponsePrivateListener
             *
             * However, we want to be able to force the reverse proxy to load a
             * response from cache, even if the request contains a cookie – in
             * case the admin has configured to do so. A typical use case would
             * be serving public pages from cache to logged in members.
             */
            if (!$pageModel->alwaysLoadFromCache) {
                $response->setVary(['Cookie']);
            }

            // Tag the page (see #2137)
            $this->container->get('contao.cache.entity_tags')->tagWithModelInstance($pageModel);
        }

        return $response;
    }

    protected function generateContentUrl(object $content, array $parameters = []): string
    {
        return $this->container->get('contao.routing.content_url_generator')->generate($content, $parameters);
    }

    protected function hasParameter(string $name): bool
    {
        if (!$this->container->has('parameter_bag')) {
            return false;
        }

        return $this->container->get('parameter_bag')->has($name);
    }
}
