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

use Contao\CoreBundle\Cache\ApplicationCacheState;
use Contao\CoreBundle\Routing\ScopeMatcher;
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
     * @var ApplicationCacheState
     */
    private $cacheState;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(ScopeMatcher $scopeMatcher, ApplicationCacheState $cacheState, TranslatorInterface $translator)
    {
        $this->scopeMatcher = $scopeMatcher;
        $this->cacheState = $cacheState;
        $this->translator = $translator;
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->scopeMatcher->isBackendRequest($request)) {
            return;
        }

        if (!$this->cacheState->isDirty()) {
            return;
        }

        /** @var Session $session */
        $session = $request->getSession();

        $session->getFlashBag()->add(
            'contao.BE.info',
            $this->translator->trans('ERR.application_cache', [], 'contao_default')
        );
    }
}
