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

#[AsContentElement(category: 'texts', nestedFragments: true)]
class AccordionWrapperController extends AbstractContentElementController
{
    public function __construct()
    {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $classes = StringUtil::deserialize($model->mooClasses, true) + [null, null];

        $template->set('toggler', $classes[0] ?: 'toggler');
        $template->set('accordion', $classes[1] ?: 'accordion');
        $template->set('title', $model->mooHeadline);

        return $template->getResponse();
    }
}
