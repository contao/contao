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
        '(.*)?modal(.*)?',

        // Google Analytics (https://developers.google.com/analytics/devguides/collection/analyticsjs/cookie-usage)
        '_ga',
        '_gid',
        '_gat',
        'AMP_TOKEN',
        '_gac_.+',

        // Matomo (https://matomo.org/faq/general/faq_146/)
        '_pk_id',
        '_pk_ref',
        '_pk_ses',
        '_pk_cvar',
        '_pk_hsr',

        // Cloudflare
        '__cfduid',
        'cf_clearance',
        'cf_use_ob',
        'cf_ob_info',

        // Facebook Pixel
        '_fbp',

        // Blackfire
        '__blackfire',
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

        // Not a cacheable request anyway? Then we don't care.
        if (!$request->isMethodCacheable()) {
            return;
        }

        if (!$request->cookies->count()) {
            return;
        }

        // Use a custom whitelist if present, otherwise use the default blacklist
        if (0 !== \count($this->whitelist)) {
            $this->filterCookies($request, $this->whitelist, true);
        } else {
            $this->filterCookies($request, self::BLACKLIST);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::PRE_HANDLE => 'preHandle',
        ];
    }

    private function filterCookies(Request $request, array $list, bool $isWhitelist = false): void
    {
        $removeCookies = preg_grep(
            '/^(?:'.implode(')$|^(?:', $list).')$/i',
            array_keys($request->cookies->all()),
            $isWhitelist ? PREG_GREP_INVERT : 0
        );

        foreach ($removeCookies as $name) {
            $request->cookies->remove($name);
        }
    }
}
