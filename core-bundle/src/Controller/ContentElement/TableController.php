<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(category: 'texts')]
class TableController extends AbstractContentElementController
{
    #[\Override]
    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        // Table fields
        $rows = StringUtil::deserialize($model->tableitems, true);
        $header = $model->thead && $rows ? array_shift($rows) : [];
        $footer = $model->tfoot && $rows ? array_pop($rows) : [];

        $template->set('header', $header);
        $template->set('footer', $footer);
        $template->set('rows', $rows);
        $template->set('use_row_headers', $model->tleft);
        $template->set('caption', $model->summary ?: null);

        // Client side sorting
        $sorting = !$header || !$model->sortable ? null : [
            'column' => $model->sortIndex - 1,
            'order' => 'descending' === $model->sortOrder ? 'desc' : 'asc',
        ];

        $template->set('sorting', $sorting);

        return $template->getResponse();
    }
}
