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
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Routing\UrlResolver;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(category: 'links')]
class HyperlinkController extends AbstractContentElementController
{
    public function __construct(
        private readonly Studio $studio,
        private readonly UrlResolver $urlResolver,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $href = $this->urlResolver->resolve($model->url ?? '');

        $linkAttributes = (new HtmlAttributes())
            ->set('href', $href)
            ->setIfExists('title', $model->titleText)
            ->setIfExists('data-lightbox', $model->rel)
        ;

        if ($model->target) {
            $linkAttributes
                ->set('target', '_blank')
                ->set('rel', 'noreferrer noopener')
            ;
        }

        $template->set('href', $href);
        $template->set('link_attributes', $linkAttributes);

        // Link text and text before/after
        $parts = explode('%s', $model->embed, 2);

        $template->set('link_text', $model->linkTitle ?: $href);
        $template->set('text_before', $parts[0] ?? '');
        $template->set('text_after', $parts[1] ?? '');

        // Set a figure in case of an image link
        $figure = !$model->useImage ? null : $this->studio
            ->createFigureBuilder()
            ->fromUuid($model->singleSRC ?: '')
            ->setSize($model->size)
            ->setOverwriteMetadata($model->getOverwriteMetadata())
            ->setLinkAttributes($linkAttributes)
            ->buildIfResourceExists()
        ;

        $template->set('image', $figure);

        return $template->getResponse();
    }
}
