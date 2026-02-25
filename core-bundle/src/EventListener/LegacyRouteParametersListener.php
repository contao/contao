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
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Input;
use Contao\UnusedArgumentsException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Handles unused legacy routing parameters.
 *
 * @internal
 */
class LegacyRouteParametersListener
{
    private array $globalsBackup = [];

    public function __construct(
        private readonly ScopeMatcher $scopeMatcher,
        private readonly ContaoFramework $framework,
        private readonly ResponseContextAccessor $responseContextAccessor,
    ) {
    }

    #[AsEventListener(priority: -4096)]
    public function onRequest(RequestEvent $event): void
    {
        if (!$this->scopeMatcher->isFrontendMainRequest($event)) {
            return;
        }

        // Backup some globals (see #7659)
        $this->globalsBackup = [
            $GLOBALS['TL_HEAD'] ?? [],
            $GLOBALS['TL_BODY'] ?? [],
            $GLOBALS['TL_MOOTOOLS'] ?? [],
            $GLOBALS['TL_JQUERY'] ?? [],
            $GLOBALS['TL_USER_CSS'] ?? [],
            $GLOBALS['TL_FRAMEWORK_CSS'] ?? [],
        ];
    }

    #[AsEventListener(priority: 4096)]
    public function onResponse(ResponseEvent $event): void
    {
        if (!$this->scopeMatcher->isFrontendMainRequest($event)) {
            return;
        }

        $input = $this->framework->getAdapter(Input::class);

        if ($unused = $input->getUnusedRouteParameters()) {
            // Mark all parameters as used
            $input->setUnusedRouteParameters([]);

            // Restore the globals (see #7659)
            [
                $GLOBALS['TL_HEAD'],
                $GLOBALS['TL_BODY'],
                $GLOBALS['TL_MOOTOOLS'],
                $GLOBALS['TL_JQUERY'],
                $GLOBALS['TL_USER_CSS'],
                $GLOBALS['TL_FRAMEWORK_CSS'],
            ] = $this->globalsBackup;

            $this->globalsBackup = [];

            // Reset the response context
            $this->responseContextAccessor->setResponseContext(null);

            throw new UnusedArgumentsException(\sprintf('Unused arguments: %s', implode(', ', $unused)));
        }
    }
}
