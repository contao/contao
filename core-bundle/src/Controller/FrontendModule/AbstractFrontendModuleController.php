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

use Contao\BackendTemplate;
use Contao\CoreBundle\Controller\AbstractFragmentController;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractFrontendModuleController extends AbstractFragmentController
{
    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null, PageModel $pageModel = null): Response
    {
        if ($this->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            return $this->getBackendWildcard($model);
        }

        $this->setPageModel($pageModel);

        $type = $this->getType();
        $template = $this->createTemplate($model, 'mod_'.$type);

        $this->addHeadlineToTemplate($template, $model->headline);
        $this->addCssAttributesToTemplate($template, 'mod_'.$type, $model->cssID, $classes);
        $this->addSectionToTemplate($template, $section);
        $this->tagResponse(['contao.db.tl_module.'.$model->id]);

        $response = $this->getResponse($template, $model, $request);

        if (null === $response) {
            $response = $template->getResponse();
        }

        return $response;
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['translator'] = TranslatorInterface::class;
        $services['contao.routing.scope_matcher'] = ScopeMatcher::class;

        return $services;
    }

    protected function getBackendWildcard(ModuleModel $module): Response
    {
        $href = $this->get('router')->generate(
            'contao_backend',
            ['do' => 'themes', 'table' => 'tl_module', 'act' => 'edit', 'id' => $module->id]
        );

        $name = $this->get('translator')->trans('FMD.'.$this->getType().'.0', [], 'contao_modules');

        $template = new BackendTemplate('be_wildcard');
        $template->wildcard = '### '.strtoupper($name).' ###';
        $template->id = $module->id;
        $template->link = $module->name;
        $template->href = $href;

        return new Response($template->parse());
    }

    abstract protected function getResponse(Template $template, ModuleModel $model, Request $request): ?Response;
}
