<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\Widget;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\Model;
use Contao\PageModel;

#[AsCallback('tl_page', 'fields.serpPreview.eval.title_tag')]
class TitleTagListener extends AbstractTitleTagCallback
{
    protected function getPageModel(Model $record): PageModel|null
    {
        return $record instanceof PageModel ? $record : null;
    }
}
