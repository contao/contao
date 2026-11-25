<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\RootPageDependentTrait;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(category: 'miscellaneous')]
class RootPageDependentModulesController extends AbstractFrontendModuleController
{
    use RootPageDependentTrait;

    public function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        return $this->renderRootPageDependent($template, $request, $model, $model->rootPageDependentModules, (array) $model->classes);
    }
}
