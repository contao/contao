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
use Contao\CoreBundle\Framework\Adapter;
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

        $framework = $this->container->get('contao.framework');

        /** @var Adapter<ModuleModel> $moduleModel */
        $moduleModel = $framework->getAdapter(ModuleModel::class);
        $module = $moduleModel->findByPk($modules[$pageModel->rootId]);

        if (!$module instanceof ModuleModel) {
            return new Response();
        }

        $cssID = StringUtil::deserialize($module->cssID, true);

        if ($idAttribute = $request->attributes->get('templateProperties', [])['cssID'] ?? null) {
            $cssID[0] = substr($idAttribute, 5, -1);
        }

        $cssID[1] = trim(sprintf('%s %s', $cssID[1] ?? '', implode(' ', (array) $model->classes)));

        $module->cssID = $cssID;

        /** @var Adapter<Controller> $controller */
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
