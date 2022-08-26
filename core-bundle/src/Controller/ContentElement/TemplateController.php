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
use Contao\StringUtil;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TemplateController extends AbstractContentElementController
{
    protected function getResponse(Template $template, ContentModel $model, Request $request): Response
    {
        $data = StringUtil::deserialize($model->data, true);

        $template->keys = array_combine(
            array_column($data, 'key'),
            array_column($data, 'value')
        );

        // Backwards compatibililty
        $template->data = $data;

        return $template->getResponse();
    }
}
