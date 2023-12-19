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
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement('accordion_single', category: 'accordion', template: 'content_element/accordion')]
#[AsContentElement('accordion_wrapper', category: 'accordion', template: 'content_element/accordion', nestedFragments: true)]
class AccordionController extends AbstractContentElementController
{
    public function __construct(private readonly Studio $studio)
    {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        if ([] === $request->attributes->get('nestedFragments', [])) {
            $template->set('text', $model->text ?: '');

            $figure = !$model->addImage ? null : $this->studio
                ->createFigureBuilder()
                ->fromUuid($model->singleSRC ?: '')
                ->setSize($model->size)
                ->setOverwriteMetadata($model->getOverwriteMetadata())
                ->enableLightbox($model->fullsize)
                ->buildIfResourceExists()
            ;

            $template->set('image', $figure);
            $template->set('layout', $model->floating);
        }

        $classes = StringUtil::deserialize($model->mooClasses, true) + [null, null];

        $template->set('toggler', $classes[0] ?: 'handorgel__header');
        $template->set('accordion', $classes[1] ?: 'handorgel__content');
        $template->set('title', $model->mooHeadline);

        return $template->getResponse();
    }
}
