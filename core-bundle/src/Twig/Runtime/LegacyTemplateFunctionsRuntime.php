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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FrontendTemplate;
use Twig\Error\RuntimeError;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * @experimental
 */
final class LegacyTemplateFunctionsRuntime implements RuntimeExtensionInterface
{
    /**
     * @internal
     */
    public function __construct(private ContaoFramework $framework)
    {
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
