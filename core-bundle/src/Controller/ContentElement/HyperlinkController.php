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
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HyperlinkController extends AbstractContentElementController
{
    public function __construct(private Studio $studio, private InsertTagParser $insertTagParser)
    {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $href = $this->insertTagParser->replace($model->url);

        $template->set('href', $href);

        $parts = explode('%s', $model->embed, 2);

        $template->set('link_text', [
            'before' => $parts[0] ?? '',
            'after' => $parts[1] ?? '',
            'value' => $model->linkTitle ?: $href,
        ]);

        $linkAttributes = (new HtmlAttributes())
            ->set('href', $href)
            ->setIfExists('title', $model->linkTitle)
            ->setIfExists('data-lightbox', $model->rel)
        ;

        if ($model->target) {
            $linkAttributes
                ->set('target', '_blank')
                ->set('rel', 'noreferrer noopener')
            ;
        }

        $template->set('link_attributes', $linkAttributes);

        $figure = !$model->useImage ? null : $this->studio
            ->createFigureBuilder()
            ->from($model->singleSRC)
            ->setSize($model->size)
            ->setMetadata($model->getOverwriteMetadata())
            ->setLinkAttributes(iterator_to_array($linkAttributes))
            ->buildIfResourceExists()
        ;

        $template->set('image', $figure);

        return $template->getResponse();
    }
}
