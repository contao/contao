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

use Contao\ArticleModel;
use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\PageModel;

/**
 * @internal
 */
class PreviewUrlConvertListener
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    public function __invoke(PreviewUrlConvertEvent $event): void
    {
        if (!$this->framework->isInitialized()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->query->get('url')) {
            $event->setUrl($request->getBaseUrl().'/'.$request->query->get('url'));

            return;
        }

        if ($request->query->get('page')) {
            /** @var PageModel $pageAdapter */
            $pageAdapter = $this->framework->getAdapter(PageModel::class);

            if ($page = $pageAdapter->findWithDetails($request->query->get('page'))) {
                $params = null;

                /** @var ArticleModel $articleAdapter */
                $articleAdapter = $this->framework->getAdapter(ArticleModel::class);

                // Add the /article/ fragment (see contao/core-bundle#673)
                if ($article = $articleAdapter->findByAlias($request->query->get('article'))) {
                    $params = sprintf(
                        '/articles/%s%s',
                        'main' !== $article->inColumn ? $article->inColumn.':' : '',
                        $article->id
                    );
                }

                $event->setUrl($page->getPreviewUrl($params));

                return;
            }
        }
    }
}
