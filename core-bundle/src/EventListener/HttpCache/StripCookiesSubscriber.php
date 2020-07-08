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

/**
 * @internal
 */
class StripCookiesSubscriber implements EventSubscriberInterface
{
    private const BLACKLIST = [
        // Contao Manager
        'contao_manager_auth',

        // Modals are always for JS only
        '(.*)?modal(.*)?',

        // Google Analytics (https://developers.google.com/analytics/devguides/collection/analyticsjs/cookie-usage)
        '_ga',
        '_gid',
        '_gat',
        '_dc_gtm_.+',
        'AMP_TOKEN',
        '_gac_.+',
        '__utm.+',

        // Matomo (https://matomo.org/faq/general/faq_146/)
        '_pk_id.*',
        '_pk_ref.*',
        '_pk_ses.*',
        '_pk_cvar.*',
        '_pk_hsr.*',

        // Cloudflare
        '__cfduid',
        'cf_clearance',
        'cf_use_ob',
        'cf_ob_info',

        // Facebook Pixel
        '_fbp',

        // Blackfire
        '__blackfire',

        // Tideways
        'TIDEWAYS_SESSION',

        // Litespeed HTTP2 Smart Push
        'ls_smartpush',

        // Osano Cookie Consent
        'cookieconsent_status',
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
