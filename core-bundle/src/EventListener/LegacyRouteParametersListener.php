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
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Input;
use Contao\UnusedArgumentsException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Handles unused legacy routing parameters.
 *
 * @internal
 */
#[AsEventListener(priority: 4096)]
class LegacyRouteParametersListener
{
    public function __construct(
        private readonly ScopeMatcher $scopeMatcher,
        private readonly ContaoFramework $framework,
    ) {
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (!$this->scopeMatcher->isFrontendMainRequest($event)) {
            return;
        }

        $input = $this->framework->getAdapter(Input::class);

        if ($unused = $input->getUnusedRouteParameters()) {
            // Mark all parameters as used
            $input->setUnusedRouteParameters([]);

            throw new UnusedArgumentsException(\sprintf('Unused arguments: %s', implode(', ', $unused)));
        }
    }
}
