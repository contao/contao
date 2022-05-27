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
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\ServiceAnnotation\ContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @ContentElement(category="texts", template="content_element/html")
 */
class UnfilteredHtmlController extends AbstractContentElementController
{
    public function __construct(private InsertTagParser $insertTagParser)
    {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        if ($this->isBackendScope($request)) {
            $template->set('html', $model->unfilteredHtml ?? '');

            return $template->getResponse();
        }

        return new Response($this->insertTagParser->replace($model->unfilteredHtml ?? ''));
    }
}
