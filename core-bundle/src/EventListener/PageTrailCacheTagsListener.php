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
use Contao\PageModel;
use FOS\HttpCache\ResponseTagger;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

#[AsEventListener]
class PageTrailCacheTagsListener
{
    public function __construct(
        private readonly ScopeMatcher $scopeMatcher,
        private readonly ResponseTagger|null $responseTagger = null,
    ) {
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (!$this->responseTagger || !$this->scopeMatcher->isFrontendMainRequest($event)) {
            return;
        }

        $pageModel = $event->getRequest()->attributes->get('pageModel');

        if (!$pageModel instanceof PageModel) {
            return;
        }

        $tags = [];

        foreach ($pageModel->trail as $trail) {
            $tags[] = 'contao.db.tl_page.'.$trail;
        }

        if ($tags) {
            $this->responseTagger->addTags($tags);
        }
    }
}
