<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Runtime;

use Contao\BackendCustom;
use Contao\CoreBundle\Controller\AbstractBackendController;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\FrontendTemplate;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Error\RuntimeError;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * @experimental
 */
final class LegacyTemplateFunctionsRuntime implements RuntimeExtensionInterface
{
    private RequestStack $requestStack;
    private ContaoFramework $framework;
    private ScopeMatcher $scopeMatcher;

    /**
     * @internal
     */
    public function __construct(RequestStack $requestStack, ContaoFramework $framework, ScopeMatcher $scopeMatcher)
    {
        $this->requestStack = $requestStack;
        $this->framework = $framework;
        $this->scopeMatcher = $scopeMatcher;
    }

    /**
     * Makes the FrontendTemplate#sections() method available from within Twig templates.
     */
    public function renderLayoutSections(array $context, string $key, string $template = null): string
    {
        $this->framework->initialize();

        if (!($frontendTemplate = $context['Template'] ?? null) instanceof FrontendTemplate) {
            throw new RuntimeError('The "contao_sections" function cannot be used in this template.');
        }

        return $this->captureOutput(
            static function () use ($template, $key, $frontendTemplate): void {
                $frontendTemplate->sections($key, $template);
            }
        );
    }

    /**
     * Makes the FrontendTemplate#section() method available from within Twig templates.
     */
    public function renderLayoutSection(array $context, string $key, string $template = null): string
    {
        $this->framework->initialize();

        if (!($frontendTemplate = $context['Template'] ?? null) instanceof FrontendTemplate) {
            throw new RuntimeError('The "contao_section" function cannot be used in this template.');
        }

        return $this->captureOutput(
            static function () use ($template, $key, $frontendTemplate): void {
                $frontendTemplate->section($key, $template);
            }
        );
    }

    /**
     * Renders a Contao back end template with the given blocks.
     */
    public function renderContaoBackendTemplate(array $blocks = []): string
    {
        trigger_deprecation('contao/core-bundle', '4.13', 'Using the "render_contao_backend_template" Twig function is deprecated and will be removed in Contao 5.0. Let your controller inherit from "%s" and your template directly from "@Contao/be_main" instead.', AbstractBackendController::class);

        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || !$this->scopeMatcher->isBackendRequest($request)) {
            return '';
        }

        $controller = $this->framework->createInstance(BackendCustom::class);
        $template = $controller->getTemplateObject();

        foreach ($blocks as $key => $content) {
            $template->{$key} = $content;
        }

        $response = $controller->run();

        return $response->getContent();
    }

    private function captureOutput(callable $callable): string
    {
        ob_start();

        try {
            $callable();

            return ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }
}
