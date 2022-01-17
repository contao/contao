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

use Contao\Controller;
use Contao\CoreBundle\Controller\AbstractFragmentController;
use Contao\ModuleModel;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RootPageDependentModulesController extends AbstractFragmentController
{
    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null): Response
    {
        $pageModel = $this->getPageModel();
        $controller = $this->container->get('contao.framework')->getAdapter(Controller::class);
        $modules = StringUtil::deserialize($model->rootPageDependentModules);
        $content = '';

        if (\is_array($modules) && \array_key_exists($pageModel->rootId, $modules)) {
            $content = $controller->getFrontendModule($modules[$pageModel->rootId]);
        }

        $this->tagResponse($model);

        return new Response($content);
    }
}
