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

class LanguageDependentModuleController extends AbstractFrontendModuleController
{
    protected function getResponse(Template $template, ModuleModel $model, Request $request): ?Response
    {
        $locale = $request->getLocale();
        $modules = StringUtil::deserialize($model->languageDependentModules);

        if (\is_array($modules) && \array_key_exists($locale, $modules)) {
            $template->module = Controller::getFrontendModule($modules[$locale]);
        }

        return $template->getResponse();
    }
}
