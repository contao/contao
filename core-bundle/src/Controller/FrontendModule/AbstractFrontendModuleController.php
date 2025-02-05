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

use Contao\CoreBundle\Controller\AbstractFragmentController;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractFrontendModuleController extends AbstractFragmentController
{
    public function __invoke(Request $request, ModuleModel $model, string $section, array|null $classes = null): Response
    {
        if ($this->isBackendScope($request)) {
            return $this->getBackendWildcard($model);
        }

        $template = $this->createTemplate($model, 'mod_'.$this->getType());

        $this->addDefaultDataToTemplate(
            $template,
            $model->row(),
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

        $services['contao.csrf.token_manager'] = ContaoCsrfTokenManager::class;

        return $services;
    }

    protected function getBackendWildcard(ModuleModel $module): Response
    {
        $context = [
            'id' => $module->id,
            'name' => $module->name,
            'type' => $module->type,
            'title' => StringUtil::deserialize($module->headline, true)['value'] ?? null,
            'request_token' => $this->container->get('contao.csrf.token_manager')->getDefaultTokenValue(),
        ];

        return $this->render('@Contao/backend/module_wildcard.html.twig', $context);
    }

    /**
     * Add default frontend module data to the template context.
     *
     * @param array<string, mixed> $modelData
     * @param array<string>        $classes
     * @param array<string, mixed> $properties
     */
    protected function addDefaultDataToTemplate(FragmentTemplate $template, array $modelData = [], string $section = 'main', array $classes = [], array $properties = [], bool $asEditorView = false): void
    {
        if ($this->isLegacyTemplate($template->getName())) {
            // Legacy fragments
            $this->addHeadlineToTemplate($template, $modelData['headline'] ?? null);
            $this->addCssAttributesToTemplate($template, 'mod_'.$this->getType(), $modelData['cssID'] ?? null, $classes);
            $this->addPropertiesToTemplate($template, $properties);
            $this->addSectionToTemplate($template, $section);

            return;
        }

        $headlineData = StringUtil::deserialize($modelData['headline'] ?? [] ?: '', true);
        $attributesData = StringUtil::deserialize($modelData['cssID'] ?? [] ?: '', true);

        $template->setData([
            'type' => $this->getType(),
            'template' => $template->getName(),
            'as_editor_view' => $asEditorView,
            'data' => $modelData,
            'section' => $section,
            'properties' => $properties,
            'element_html_id' => $attributesData[0] ?? null,
            'element_css_classes' => trim(($attributesData[1] ?? '').' '.implode(' ', $classes)),
            'headline' => [
                'text' => $headlineData['value'] ?? '',
                'tag_name' => $headlineData['unit'] ?? 'h1',
            ],
        ]);
    }

    abstract protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response;
}
