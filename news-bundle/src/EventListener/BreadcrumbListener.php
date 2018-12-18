<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\EventListener;

use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Input;
use Contao\News;
use Contao\NewsArchiveModel;
use Contao\NewsModel;
use Contao\PageModel;
use Contao\StringUtil;

class BreadcrumbListener
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    public function onGenerateBreadcrumb(array $items): array
    {
        $newsAlias = $this->getNewsAlias();
        if (!$newsAlias) {
            return $items;
        }

        $newsArchive = $this->getNewsArchive();
        if (!$newsArchive) {
            return $items;
        }

        $news = $this->getNews($newsAlias, $newsArchive);
        if (!$news) {
            return $items;
        }

        if ($GLOBALS['objPage']->requireItem) {
            return $this->overrideActiveBreadcrumbItem($items, $news);
        }

        return $this->addBreadcrumbItem($items, $news);
    }

    private function getNewsAlias(): ?string
    {
        if (!isset($GLOBALS['objPage'])) {
            return null;
        }

        /** @var Adapter|Input $inputAdapter */
        $inputAdapter = $this->framework->getAdapter(Input::class);
        /** @var Adapter|Config $configAdapter */
        $configAdapter = $this->framework->getAdapter(Config::class);

        if ($configAdapter->get('useAutoItem')) {
            return $inputAdapter->get('auto_item');
        }

        return $inputAdapter->get('items');
    }

    private function getNewsArchive(): ?NewsArchiveModel
    {
        /** @var Adapter|NewsArchiveModel $repository */
        $repository = $this->framework->getAdapter(NewsArchiveModel::class);

        return $repository->findOneByJumpTo($GLOBALS['objPage']->id);
    }

    private function getNews(string $newsAlias, NewsArchiveModel $newsArchive): ?NewsModel
    {
        /** @var Adapter|NewsModel $repository */
        $repository = $this->framework->getAdapter(NewsModel::class);

        return $repository->findPublishedByParentAndIdOrAlias($newsAlias, [$newsArchive->id]);
    }

    private function addBreadcrumbItem(array $items, NewsModel $news): array
    {
        $currentPage = $this->getCurrentPage();

        foreach ($items as &$item) {
            $item['isActive'] = false;
        }
        unset ($item);

        $title = $this->getNewsTitle($news, $currentPage);
        $items[] = [
            'isRoot' => false,
            'isActive' => true,
            'href' => $this->generateNewsUrl($news),
            'title' => StringUtil::specialchars($title, true),
            'link' => $title,
            'data' => $currentPage->row(),
            'class' => ''
        ];

        return $items;
    }

    private function overrideActiveBreadcrumbItem(array $items, NewsModel $news): array
    {
        $currentPage = $this->getCurrentPage();
        $title = $this->getNewsTitle($news, $currentPage);

        foreach ($items as &$item) {
            if ($item['isActive'] && $item['data']['id'] === $currentPage->id) {
                $item['title'] = StringUtil::specialchars($title, true);
                $item['link'] = $title;
                $item['href'] = $this->generateNewsUrl($news);

                break;
            }
        }

        return $items;
    }

    private function getCurrentPage(): PageModel
    {
        // Fetch the page again from the database as the global objPage might already have an overridden title
        /** @var Adapter|PageModel $repository */
        $repository = $this->framework->getAdapter(PageModel::class);

        return $repository->findByPk($GLOBALS['objPage']->id) ?: $GLOBALS['objPage'];
    }

    private function getNewsTitle(NewsModel $news, PageModel $currentPage): string
    {
        if ($news->pageTitle) {
            return $news->pageTitle;
        }

        if ($news->headline) {
            return $news->headline;
        }

        return $currentPage->pageTitle ?: $currentPage->title;
    }

    private function generateNewsUrl(NewsModel $newsModel): string
    {
        /** @var Adapter|News $adapter */
        $adapter = $this->framework->getAdapter(News::class);

        return $adapter->generateNewsUrl($newsModel);
    }
}
