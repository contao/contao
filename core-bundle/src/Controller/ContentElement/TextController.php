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
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TextController extends AbstractContentElementController
{
    public function __construct(private readonly Studio $studio)
    {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        // todo: check if the "Add the static files URL to images" handling from Contao\ContentText needs to be done here as well
        $template->set('text', $model->text);

        $figure = !$model->addImage ? null : $this->studio
            ->createFigureBuilder()
            ->from($model->singleSRC)
            ->setSize($model->size)
            ->setMetadata($model->getOverwriteMetadata())
            ->enableLightbox((bool) $model->fullsize)
            ->buildIfResourceExists()
        ;

        $template->set('image', $figure);

        $layoutAttributes = new HtmlAttributes();

        if ($model->addImage && $position = $model->floating) {
            $layoutAttributes->addClass("image--$position");
        }

        $template->set('layout_attributes', $layoutAttributes);

        return $template->getResponse();
    }
}
