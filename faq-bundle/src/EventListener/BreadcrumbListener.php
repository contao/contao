<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\FaqBundle\EventListener;

use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\FaqCategoryModel;
use Contao\FaqModel;
use Contao\Input;
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
        /** @var Adapter|Config $configAdapter */
        $configAdapter = $this->framework->getAdapter(Config::class);
        $useAutoItem = (bool) $configAdapter->get('useAutoItem');

        $faqAlias = $this->getFaqAlias($useAutoItem);
        if (!$faqAlias) {
            return $items;
        }

        $faqCategory = $this->getFaqCategory();
        if (!$faqCategory) {
            return $items;
        }

        $faq = $this->getFaq($faqAlias, $faqCategory);
        if (!$faq) {
            return $items;
        }

        if ($GLOBALS['objPage']->requireItem) {
            return $this->overrideActiveBreadcrumbItem($items, $faq, $useAutoItem);
        }

        return $this->addBreadcrumbItem($items, $faq, $useAutoItem);
    }

    private function getFaqAlias(bool $useAutoItem): ?string
    {
        if (!isset($GLOBALS['objPage'])) {
            return null;
        }

        /** @var Adapter|Input $inputAdapter */
        $inputAdapter = $this->framework->getAdapter(Input::class);

        if ($useAutoItem) {
            return $inputAdapter->get('auto_item');
        }

        return $inputAdapter->get('items');
    }

    private function getFaqCategory(): ?FaqCategoryModel
    {
        /** @var Adapter|FaqCategoryModel $repository */
        $repository = $this->framework->getAdapter(FaqCategoryModel::class);

        return $repository->findOneByJumpTo($GLOBALS['objPage']->id);
    }

    private function getFaq(string $faqAlias, FaqCategoryModel $faqCategory): ?FaqModel
    {
        /** @var Adapter|FaqModel $repository */
        $repository = $this->framework->getAdapter(FaqModel::class);

        return $repository->findPublishedByParentAndIdOrAlias($faqAlias, [$faqCategory->id]);
    }

    private function addBreadcrumbItem(array $items, FaqModel $faq, bool $useAutoItem): array
    {
        $currentPage = $this->getCurrentPage();

        foreach ($items as &$item) {
            $item['isActive'] = false;
        }
        unset ($item);

        $title = $this->getFaqTitle($faq, $currentPage);
        $items[] = [
            'isRoot' => false,
            'isActive' => true,
            'href' => $this->generateFaqUrl($faq, $currentPage, $useAutoItem),
            'title' => StringUtil::specialchars($title, true),
            'link' => $title,
            'data' => $currentPage->row(),
            'class' => ''
        ];

        return $items;
    }

    private function overrideActiveBreadcrumbItem(array $items, FaqModel $faq, bool $useAutoItem): array
    {
        $currentPage = $this->getCurrentPage();
        $title = $this->getFaqTitle($faq, $currentPage);

        foreach ($items as &$item) {
            if ($item['isActive'] && $item['data']['id'] === $currentPage->id) {
                $item['title'] = StringUtil::specialchars($title, true);
                $item['link'] = $title;
                $item['href'] = $this->generateFaqUrl($faq, $currentPage, $useAutoItem);

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

    private function getFaqTitle(FaqModel $faq, PageModel $currentPage): string
    {
        if ($faq->question) {
            return $faq->question;
        }

        return $currentPage->pageTitle ?: $currentPage->title;
    }

    private function generateFaqUrl(FaqModel $faqModel, PageModel $currentPage, bool $useAutoItem): string
    {
        $prefix = $useAutoItem ? '/' : '/items/';
        $slug   = $faqModel->alias ?: $faqModel->id;

        return $currentPage->getFrontendUrl($prefix . $slug);
    }
}
