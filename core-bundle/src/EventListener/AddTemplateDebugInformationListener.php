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

use Contao\CoreBundle\Event\CompileTemplateEvent;

/**
 * @internal
 */
class AddTemplateDebugInformationListener
{
    private bool $debug;

    public function __construct(bool $debug)
    {
        $this->debug = $debug;
    }

    public function __invoke(CompileTemplateEvent $event): void
    {
        if (!$this->debug) {
            return;
        }

        if ($event->matchType('html', 'html5') && $event->isContaoTemplate()) {
            $name = $event->getName();

            $event->prepend("\n<!-- TWIG TEMPLATE START: $name -->");
            $event->append("<!-- TWIG TEMPLATE END: $name -->\n");
        }
    }
}
