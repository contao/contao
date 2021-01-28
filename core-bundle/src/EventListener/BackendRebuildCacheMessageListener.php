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
use Contao\CoreBundle\Twig\FailTolerantFilesystemLoader;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class BackendRebuildCacheMessageListener
{
    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(ScopeMatcher $scopeMatcher, CacheItemPoolInterface $cache, TranslatorInterface $translator)
    {
        $this->scopeMatcher = $scopeMatcher;
        $this->cache = $cache;
        $this->translator = $translator;
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->scopeMatcher->isBackendRequest($request)) {
            return;
        }

        if (!$this->cache->hasItem(FailTolerantFilesystemLoader::CACHE_DIRTY_FLAG)) {
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
