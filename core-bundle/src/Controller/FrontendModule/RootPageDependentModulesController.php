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

use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(category: 'miscellaneous')]
class RootPageDependentModulesController extends AbstractFrontendModuleController
{
    public function __invoke(Request $request, ModuleModel $model, string $section, array|null $classes = null): Response
    {
        if ($this->isBackendScope($request)) {
            return $this->getBackendWildcard($model);
        }

        if (!$pageModel = $this->getPageModel()) {
            return new Response();
        }

        $elements = StringUtil::deserialize($model->rootPageDependentModules, true);
        $id = $elements[$pageModel->rootId] ?? null;

        if (!$id) {
            return new Response();
        }

        if ($isElement = str_starts_with($id, 'content-')) {
            $id = substr($id, 8);
        }

        if (!$contentModel = $this->getContaoAdapter($isElement ? ContentModel::class : ModuleModel::class)->findById($id)) {
            return new Response();
        }

        $cssID = StringUtil::deserialize($contentModel->cssID, true);
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

        $contentModel->cssID = $cssID;

        $controller = $this->getContaoAdapter(Controller::class);
        $content = $isElement ? $controller->getContentElement($contentModel) : $controller->getFrontendModule($contentModel);

        $this->tagResponse($model);

        return new Response($content);
    }

    public function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        throw new \LogicException('This method should never be called');
    }
}
