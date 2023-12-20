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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(category: 'miscellaneous', nestedFragments: true)]
class ElementGroupController extends AbstractContentElementController
{
    public function __construct()
    {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        return $template->getResponse();
    }
}
