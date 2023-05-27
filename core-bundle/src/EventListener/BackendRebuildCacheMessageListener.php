<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class BackendRebuildCacheMessageListener
{
    final public const CACHE_DIRTY_FLAG = 'contao.template_path_cache_dirty';

    public function __construct(private ScopeMatcher $scopeMatcher, private CacheItemPoolInterface $cache, private TranslatorInterface $translator)
    {
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->scopeMatcher->isBackendRequest($request)) {
            return;
        }

        if (!$this->cache->hasItem(self::CACHE_DIRTY_FLAG)) {
            return;
        }

        /** @var Session $session */
        $session = $request->getSession();

        $session->getFlashBag()->add(
            'contao.BE.info',
            $this->translator->trans('ERR.applicationCache', [], 'contao_default')
        );
    }
}
