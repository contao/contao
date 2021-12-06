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
use Contao\ModuleModel;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractFrontendModuleController extends AbstractFragmentController
{
    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null): Response
    {
        if ($this->container->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            return $this->getBackendWildcard($model);
        }

        $type = $this->getType();
        $template = $this->createTemplate($model, 'mod_'.$type);

        $this->addHeadlineToTemplate($template, $model->headline);
        $this->addCssAttributesToTemplate($template, 'mod_'.$type, $model->cssID, $classes);
        $this->addPropertiesToTemplate($template, $request->attributes->get('templateProperties', []));
        $this->addSectionToTemplate($template, $section);
        $this->tagResponse($model);

        $response = $this->getResponse($template, $model, $request);

        if (null === $response) {
            trigger_deprecation('contao/core-bundle', '4.12', 'Returning null in %s::getResponse() is deprecated, return a Response instead.', static::class);
            $response = $template->getResponse();
        }

        return $response;
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['translator'] = TranslatorInterface::class;

        return $services;
    }

    protected function getBackendWildcard(ModuleModel $module): Response
    {
        $href = $this->container->get('router')->generate(
            'contao_backend',
            ['do' => 'themes', 'table' => 'tl_module', 'act' => 'edit', 'id' => $module->id]
        );

        $name = $this->container->get('translator')->trans('FMD.'.$this->getType().'.0', [], 'contao_modules');

        $template = new BackendTemplate('be_wildcard');
        $template->wildcard = '### '.strtoupper($name).' ###';
        $template->id = $module->id;
        $template->link = $module->name;
        $template->href = $href;

        return new Response($template->parse());
    }

    abstract protected function getResponse(Template $template, ModuleModel $model, Request $request): ?Response;
}
