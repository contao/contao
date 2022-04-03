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
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Model;
use Contao\ModuleModel;
use Contao\StringUtil;
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

        $template = $this->createTemplate($model, 'mod_'.$this->getType());

        $this->addDefaultDataToTemplate(
            $template,
            (array) $model->row(),
            $section,
            $classes ?? [],
            $request->attributes->get('templateProperties', []),
        );

        $this->tagResponse($model);

        return $this->getResponse($template, $model, $request);
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

    /**
     * Add default frontend module data to the template context.
     *
     * @param array<string, mixed> $modelData
     * @param array<string>        $classes
     * @param array<string, mixed> $properties
     */
    protected function addDefaultDataToTemplate(FragmentTemplate $template, array $modelData = [], string $section = 'main', array $classes = [], array $properties = [], bool $asOverview = false): void
    {
        if ($this->isLegacyTemplate($template->getName())) {
            // Legacy fragments
            $this->addHeadlineToTemplate($template, $modelData['headline'] ?? null);
            $this->addCssAttributesToTemplate($template, $template->getName(), $modelData['cssID'] ?? null, $classes);
            $this->addPropertiesToTemplate($template, $properties);
            $this->addSectionToTemplate($template, $section);

            return;
        }

        $headlineData = StringUtil::deserialize($modelData['headline'] ?? [] ?: '', true);
        $attributesData = StringUtil::deserialize($modelData['cssID'] ?? [] ?: '', true);

        $template->setData([
            'type' => $this->getType(),
            'template' => $template->getName(),
            'as_overview' => $asOverview,
            'data' => $modelData,
            'section' => $section,
            'properties' => $properties,
            'attributes' => (new HtmlAttributes())
                ->setIfExists('id', $attributesData[0] ?? null)
                ->addClass($attributesData[1] ?? '', ...$classes),
            'headline' => [
                'value' => $headlineData['value'] ?? '',
                'unit' => $headlineData['unit'] ?? 'h1',
            ],
        ]);
    }

    abstract protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response;
}
