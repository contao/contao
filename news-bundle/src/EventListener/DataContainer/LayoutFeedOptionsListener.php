<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\NewsBundle\Controller\Page\NewsFeedController;
use Contao\PageModel;

#[AsCallback('tl_layout', target: 'fields.newsfeeds.options')]
class LayoutFeedOptionsListener
{
    public function __construct(private ContaoFramework $framework)
    {
    }

    public function __invoke(): array
    {
        $this->framework->initialize();

        $model = $this->framework->getAdapter(PageModel::class);

        if (!$feeds = $model->findByType(NewsFeedController::TYPE)) {
            return [];
        }

        $options = [];
        $formats = ['rss' => 'RSS 2.0', 'atom' => 'Atom', 'json' => 'JSON'];

        foreach ($feeds as $feed) {
            $options[$feed->id] = sprintf('%s (%s)', $feed->title, $formats[$feed->feedFormat]);
        }

        return $options;
    }
}
