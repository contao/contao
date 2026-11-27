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
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(category: 'miscellaneous')]
class RootPageDependentModulesController extends AbstractFrontendModuleController
{
    public function __invoke(Request $request, ModuleModel $model, string $section, array|null $classes = null): Response
    {
        if ($this->container->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            return $this->getBackendWildcard($model);
        }

        if (!$pageModel = $this->getPageModel()) {
            return new Response();
        }

        $modules = StringUtil::deserialize($model->rootPageDependentModules, true);

        if (empty($modules[$pageModel->rootId])) {
            return new Response();
        }

        /** @var ContaoFramework $framework */
        $framework = $this->container->get('contao.framework');
        $moduleModel = $framework->getAdapter(ModuleModel::class);

        if (!$module = $moduleModel->findById($modules[$pageModel->rootId])) {
            return new Response();
        }

        $cssID = StringUtil::deserialize($module->cssID, true);
        $modelCssID = StringUtil::deserialize($model->cssID, true);

        // Override the CSS ID (see #305)
        if (!empty($modelCssID[0])) {
            $cssID[0] = $modelCssID[0];
        }

        if ($idAttribute = $request->attributes->get('templateProperties', [])['cssID'] ?? null) {
            $cssID[0] = substr($idAttribute, 5, -1);
        }

        // Merge the CSS classes (see #6011)
        $cssID[1] = implode(' ', array_filter(array_map(trim(...), [$cssID[1] ?? '', $modelCssID[1] ?? '', ...(array) $model->classes])));

        $module->cssID = $cssID;

        $controller = $framework->getAdapter(Controller::class);
        $content = $controller->getFrontendModule($module);

        $this->tagResponse($model);

        return new Response($content);
    }

    public function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        throw new \LogicException('This method should never be called');
    }
}
