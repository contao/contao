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

use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * @internal
 *
 * @deprecated Deprecated since Contao 4.9, to be removed in Contao 5.0
 */
class LegacyLoginConstantsListener
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $this->framework->setLoginConstants();
    }
}
