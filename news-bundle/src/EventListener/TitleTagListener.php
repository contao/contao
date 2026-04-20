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

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\EventListener\Widget\AbstractTitleTagCallback;
use Contao\Model;
use Contao\NewsArchiveModel;
use Contao\NewsModel;
use Contao\PageModel;

#[AsCallback('tl_news', 'fields.serpPreview.eval.title_tag')]
class TitleTagListener extends AbstractTitleTagCallback
{
    protected function getPageModel(Model $record): PageModel|null
    {
        if (!$record instanceof NewsModel) {
            return null;
        }

        if (!($archive = $record->getRelated('pid')) instanceof NewsArchiveModel) {
            return null;
        }

        if (!($page = $archive->getRelated('jumpTo')) instanceof PageModel) {
            return null;
        }

        return $page;
    }
}
