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
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TableController extends AbstractContentElementController
{
    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $rows = StringUtil::deserialize($model->tableitems, true);
        $header = $model->thead && !empty($rows) ? array_shift($rows) : [];
        $footer = $model->tfoot && !empty($rows) ? array_pop($rows) : [];

        $template->set('header', $header);
        $template->set('footer', $footer);
        $template->set('rows', $rows);
        $template->set('use_row_headers', (bool) $model->tleft);

        $sorting = empty($header) || !$model->sortable ? null : [
            'index' => (int) $model->sortIndex,
            'order' => 'descending' === $model->sortOrder ? 'desc' : 'asc',
        ];

        $template->set('sorting', $sorting);

        $template->set('summary', $model->summary ?: null);

        return $template->getResponse();
    }
}
