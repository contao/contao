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

use Contao\FrontendTemplate;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * @internal
 */
final class LegacyTemplateFunctionsRuntime implements RuntimeExtensionInterface
{
    public function __construct(private readonly Environment $twig)
    {
    }

    /**
     * Makes the FrontendTemplate#sections() method available from within Twig templates.
     */
    public function renderLayoutSections(array $context, string $key, string|null $templateName = null): string
    {
        $frontendTemplate = $context['Template'] ?? null;

        if (!$frontendTemplate instanceof FrontendTemplate) {
            throw new RuntimeError('The "contao_section" function cannot be used in this template.');
        }

        if (!array_filter($frontendTemplate->sections) || ($key && !isset($frontendTemplate->positions[$key]))) {
            return '';
        }

        $matches = [];

        foreach ($frontendTemplate->positions[$key] as $id => $section) {
            if (!empty($frontendTemplate->sections[$id])) {
                if (!isset($section['template'])) {
                    $section['template'] = 'block_section';
                }

                $section['content'] = $frontendTemplate->sections[$id];
                $matches[$id] = $section;
            }
        }

        // Return if the section is empty (see #1115)
        if ([] === $matches) {
            return '';
        }

        $templateName ??= 'block_sections';

        return $this->twig->render("@Contao/$templateName.html.twig", [
            ...$frontendTemplate->getData(),
            'matches' => $matches,
        ]);
    }

    /**
     * Makes the FrontendTemplate#section() method available from within Twig templates.
     */
    public function renderLayoutSection(array $context, string $id, string|null $templateName = null): string
    {
        $frontendTemplate = $context['Template'] ?? null;

        if (!$frontendTemplate instanceof FrontendTemplate) {
            throw new RuntimeError('The "contao_sections" function cannot be used in this template.');
        }

        if (empty($frontendTemplate->sections[$id])) {
            return '';
        }

        if (null === $templateName) {
            foreach ($frontendTemplate->positions as $position) {
                if (isset($position[$id]['template'])) {
                    $templateName = $position[$id]['template'];
                }
            }
        }

        $templateName ??= 'block_sections';

        return $this->twig->render("@Contao/$templateName.html.twig", [
            ...$frontendTemplate->getData(),
            'id' => $id,
            'content' => $frontendTemplate->sections[$id],
        ]);
    }
}
