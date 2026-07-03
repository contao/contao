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

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\EventListener\Widget\AbstractTitleTagCallback;
use Contao\FaqCategoryModel;
use Contao\FaqModel;
use Contao\Model;
use Contao\PageModel;

#[AsCallback('tl_faq', 'fields.serpPreview.eval.title_tag')]
class TitleTagListener extends AbstractTitleTagCallback
{
    protected function getPageModel(Model $record): PageModel|null
    {
        if (!$record instanceof FaqModel) {
            return null;
        }

        $framework = $this->container->get('contao.framework');

        if (!$category = $framework->getAdapter(FaqCategoryModel::class)->findById($record->pid)) {
            return null;
        }

        if (!$page = $framework->getAdapter(PageModel::class)->findById($category->jumpTo)) {
            return null;
        }

        return $page;
    }
}
