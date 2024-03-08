<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\Routing;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Content\ContentUrlResolverInterface;
use Contao\CoreBundle\Routing\Content\ContentUrlResult;
use Contao\NewsletterChannelModel;
use Contao\NewsletterModel;
use Contao\PageModel;

class NewsletterResolver implements ContentUrlResolverInterface
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function resolve(object $content): ContentUrlResult|null
    {
        if (!$content instanceof NewsletterModel) {
            return null;
        }

        $pageAdapter = $this->framework->getAdapter(PageModel::class);
        $channelAdapter = $this->framework->getAdapter(NewsletterChannelModel::class);

        return ContentUrlResult::resolve($pageAdapter->findPublishedById((int) $channelAdapter->findById($content->pid)?->jumpTo));
    }

    public function getParametersForContent(object $content, PageModel $pageModel): array
    {
        if (!$content instanceof NewsletterModel) {
            return [];
        }

        return ['parameters' => '/'.($content->alias ?: $content->id)];
    }
}
