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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

class StripCookiesSubscriber implements EventSubscriberInterface
{
    private const BLACKLIST = [
        // Modals are always for JS only
        '^(.*)?modal(.*)?$',

        // Google Analytics (https://developers.google.com/analytics/devguides/collection/analyticsjs/cookie-usage)
        '^_ga$',
        '^_gid$',
        '^_gat$',
        '^AMP_TOKEN$',
        '^_gac_.+$',

        // Matomo (https://matomo.org/faq/general/faq_146/)
        '^_pk_id$',
        '^_pk_ref$',
        '^_pk_ses$',
        '^_pk_cvar$',
        '^_pk_hsr$',
    ];

    /**
     * @var array
     */
    private $whitelist;

    public function __construct(array $whitelist = [])
    {
        $this->whitelist = $whitelist;
    }

    public function getWhitelist(): array
    {
        return $this->whitelist;
    }

    public function preHandle(CacheEvent $event): void
    {
        $request = $event->getRequest();

        // Not a cacheable request anyway? Then we don't care
        if (!$request->isMethodCacheable()) {
            return;
        }

        if (!$request->cookies->count()) {
            return;
        }

        if ($this->whitelist) {
            $this->filterCookies($request, $this->whitelist, true);

            return;
        }

        $this->filterCookies($request, self::BLACKLIST);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PRE_HANDLE => 'preHandle',
        ];
    }

    private function filterCookies(Request $request, array $list, bool $isWhitelist = false): void
    {
        $cookies = array_keys($request->cookies->all());

        foreach ($cookies as $name) {
            foreach ($list as $entry) {
                $matches = preg_match('/'.$entry.'/i', $name);

                if ($isWhitelist && !$matches) {
                    $request->cookies->remove($name);
                }

                if (!$isWhitelist && $matches) {
                    $request->cookies->remove($name);
                }
            }
        }
    }
}
