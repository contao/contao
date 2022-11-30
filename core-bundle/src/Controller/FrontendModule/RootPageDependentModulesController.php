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
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RootPageDependentModulesController extends AbstractFrontendModuleController
{
    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null): Response
    {
        if ($this->container->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            return $this->getBackendWildcard($model);
        }

        if (!$pageModel = $this->getPageModel()) {
            return new Response('');
        }

        $modules = StringUtil::deserialize($model->rootPageDependentModules);

        if (empty($modules) || !\is_array($modules) || !\array_key_exists($pageModel->rootId, $modules)) {
            return new Response('');
        }

        $moduleModel = $this->container->get('contao.framework')->getAdapter(ModuleModel::class);
        $module = $moduleModel->findByPk($modules[$pageModel->rootId]);

        $cssID = StringUtil::deserialize($module->cssID, true);
        $cssID[1] = trim(sprintf('%s %s', $cssID[1], implode(' ', $model->classes)));
        $module->cssID = $cssID;

        $controller = $this->container->get('contao.framework')->getAdapter(Controller::class);
        $content = $controller->getFrontendModule($module);

        $this->tagResponse($model);

        return new Response($content);
    }

    public function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        throw new \LogicException('This method should never be called');
    }
}
