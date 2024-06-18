<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Loader;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * @internal
 */
#[AsEventListener]
class AutoRefreshTemplateHierarchyListener
{
    public function __construct(
        private readonly ContaoFilesystemLoader $loader,
        private readonly string $environment,
    ) {
    }

    /**
     * Auto refresh template hierarchy in dev mode, so that added/removed files are
     * immediately recognized.
     */
    public function __invoke(RequestEvent $event): void
    {
        if ('dev' === $this->environment && $event->isMainRequest()) {
            $this->loader->warmUp(true);
        }
    }
}
