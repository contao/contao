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
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RootPageDependentModuleController extends AbstractFrontendModuleController
{
    protected function getResponse(Template $template, ModuleModel $model, Request $request): ?Response
    {
        /** @var PageModel $pageModel */
        $pageModel = $this->getPageModel();

        $modules = StringUtil::deserialize($model->rootPageDependentModules);

        if (\is_array($modules) && \array_key_exists($pageModel->rootId, $modules)) {
            $template->module = Controller::getFrontendModule($modules[$pageModel->rootId]);
        }

        return $template->getResponse();
    }
}
