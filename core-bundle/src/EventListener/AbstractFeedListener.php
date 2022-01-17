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

use Contao\CoreBundle\Event\FeedEvent;

abstract class AbstractFeedListener
{
    public function __invoke(FeedEvent $event): void
    {
        if (!$this->supports($event->getAlias())) {
            return;
        }

        $this->generate($event);
    }

    abstract public function supports(string $alias);

    abstract public function generate(FeedEvent $event);
}
