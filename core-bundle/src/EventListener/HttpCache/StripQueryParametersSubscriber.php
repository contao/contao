<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\HttpCache;

use FOS\HttpCache\SymfonyCache\CacheEvent;
use FOS\HttpCache\SymfonyCache\Events;
use Nyholm\Psr7\Uri;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class StripQueryParametersSubscriber implements EventSubscriberInterface
{
    private const REMOVED_QUERY_PARAMETERS = '_contao_http_cache_removed_query_parameters';

    private const DENY_LIST = [
        // Google click identifier
        'gclid',
        'gbraid',
        'wbraid',
        'dclid', // Used to be DoubleClick

        // Facebook click identifier
        'fbclid',

        // LinkedIn click identifier
        'li_fat_id',

        // TikTok click identifier
        'ttclid',

        // Microsoft Ads click identifier
        'msclkid',

        // Awin click identifier
        'zanpid', // Used to be Zanox

        // Google custom search engine
        'cx',
        'ie',
        'cof',

        // Google search analytics
        'siteurl',

        // Google Ads
        'gclsrc',
        'gad_source',
        'gad_campaignid',
        'ved',

        // Urchin Tracking Module (UTM) parameters
        'utm_[a-z]+',

        // Matomo campaign parameters
        'mtm_[a-z]+',

        // etracker campaign parameters
        'etcc_[a-z]+',

        // HubSpot campaign parameters
        'hsa_[a-z]+',
    ];

    private array $removeFromDenyList = [];

    public function __construct(private readonly array $allowList = [])
    {
    }

    public function getAllowList(): array
    {
        return $this->allowList;
    }

    public function removeFromDenyList(array $removeFromDenyList): self
    {
        $this->removeFromDenyList = $removeFromDenyList;

        return $this;
    }

    public function preHandle(CacheEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->query->count()) {
            return;
        }

        // Use a custom allow list if present, otherwise use the default deny list
        if ($this->allowList) {
            $removedQueryParameters = $this->filterQueryParams($request, $this->allowList);
        } else {
            $removedQueryParameters = $this->filterQueryParams($request, $this->removeFromDenyList, self::DENY_LIST);
        }

        if ($removedQueryParameters) {
            // Request attributes are copied when the cache forwards a cloned request
            $request->attributes->set(self::REMOVED_QUERY_PARAMETERS, $removedQueryParameters);
        }
    }

    public function postHandle(CacheEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $removedQueryParameters = $request->attributes->get(self::REMOVED_QUERY_PARAMETERS, []);

        if (!$response?->isRedirect() || !\is_array($removedQueryParameters) || !$removedQueryParameters) {
            return;
        }

        $location = $response->headers->get('Location');

        if (null === $location) {
            return;
        }

        $uri = new Uri($location);
        $existingParameters = HeaderUtils::parseQuery($uri->getQuery());
        $parameters = array_diff_key($removedQueryParameters, $existingParameters);

        if (!$parameters) {
            return;
        }

        $query = implode('&', array_filter([$uri->getQuery(), http_build_query($parameters)], static fn (string $value): bool => '' !== $value));
        $location = (string) $uri->withQuery($query);

        if ($response instanceof RedirectResponse) {
            $response->setTargetUrl($location);
        } else {
            $response->headers->set('Location', $location);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::PRE_HANDLE => 'preHandle',
            Events::POST_HANDLE => 'postHandle',
        ];
    }

    private function filterQueryParams(Request $request, array $allowList = [], array $denyList = []): array
    {
        // Remove params that match the deny list or all if no deny list was set
        $removeParams = preg_grep(
            '/^(?:'.implode(')$|^(?:', $denyList ?: ['.*']).')$/i',
            array_keys($request->query->all()),
        );

        // Do not remove params that match the allow list
        $removeParams = preg_grep('/^(?:'.implode(')$|^(?:', $allowList).')$/i', $removeParams, PREG_GREP_INVERT);

        $removedQueryParameters = array_intersect_key($request->query->all(), array_flip($removeParams));

        foreach ($removeParams as $name) {
            $request->query->remove($name);
        }

        // We also need to adjust the ServerBag, otherwise the cache storage will use the
        // wrong URI (see #6908)
        $request->server->set('QUERY_STRING', http_build_query($request->query->all()));

        return $removedQueryParameters;
    }
}
