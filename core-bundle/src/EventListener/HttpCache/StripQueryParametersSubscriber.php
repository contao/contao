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
class StripQueryParametersSubscriber implements EventSubscriberInterface
{
    private const BLACKLIST = [
        // Google click identifier
        'gclid',
        'dclid', // Used to be DoubleClick

        // Facebook click identifier
        'fbclid',

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

        //  Urchin Tracking Module (UTM) parameters
        'utm_[a-z]+',
    ];

    /**
     * @var array
     */
    private $whitelist = [];

    /**
     * @var array
     */
    private $disabledFromBlacklist = [];

    public function __construct(array $whitelist = [])
    {
        $this->whitelist = $whitelist;
    }

    public function getWhitelist(): array
    {
        return $this->whitelist;
    }

    public function disableFromBlacklist(array $disableFromBlacklist): self
    {
        $this->disabledFromBlacklist = $disableFromBlacklist;

        return $this;
    }

    public function preHandle(CacheEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->query->count()) {
            return;
        }

        // Use a custom whitelist if present, otherwise use the default blacklist
        if (0 !== \count($this->whitelist)) {
            $this->filterQueryParams($request, $this->whitelist);
        } else {
            $this->filterQueryParams($request, $this->disabledFromBlacklist, self::BLACKLIST);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::PRE_HANDLE => 'preHandle',
        ];
    }

    private function filterQueryParams(Request $request, array $allowList = [], array $denyList = []): void
    {
        // Remove params that match the deny list or all if no deny list was set
        $removeParams = preg_grep(
            '/^(?:'.implode(')$|^(?:', $denyList ?: ['.*']).')$/i',
            array_keys($request->query->all())
        );

        // Do not remove params that match the allow list
        $removeParams = preg_grep(
            '/^(?:'.implode(')$|^(?:', $allowList).')$/i',
            $removeParams,
            PREG_GREP_INVERT
        );

        foreach ($removeParams as $name) {
            $request->query->remove($name);
        }
    }
}
