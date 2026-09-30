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
use Contao\CoreBundle\Controller\RootPageDependentTrait;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(category: 'includes')]
class RootPageDependentElementsController extends AbstractContentElementController
{
    use RootPageDependentTrait;

    public function __invoke(Request $request, ContentModel $model, string $section, array|null $classes = null): Response
    {
        return $this->renderRootPageDependent($request, $model, $model->rootPageDependentElements, $classes ?? []);
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        throw new \LogicException('This method should never be called');
    }
}
