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
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Validator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(category: 'links')]
class HyperlinkController extends AbstractContentElementController
{
    public function __construct(
        private readonly Studio $studio,
        private readonly InsertTagParser $insertTagParser,
        private readonly RequestStack $requestStack,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        // Link with attributes
        $href = $this->insertTagParser->replaceInline($model->url);

        if (Validator::isRelativeUrl($href)) {
            $href = $this->requestStack->getMainRequest()?->getBasePath().'/'.$href;
        }

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
            ->setMetadata($model->getOverwriteMetadata())
            ->setLinkAttributes($linkAttributes)
            ->buildIfResourceExists()
        ;

        $template->set('image', $figure);

        return $template->getResponse();
    }
}
