<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Extension;

use Contao\CoreBundle\Twig\Inheritance\DynamicExtendsTokenParser;
use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchy;
use Contao\CoreBundle\Twig\Interop\ContaoEscaper;
use Contao\CoreBundle\Twig\Interop\ContaoEscaperNodeVisitor;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\EscaperExtension;

class ContaoExtension extends AbstractExtension
{
    /**
     * @var TemplateHierarchy
     */
    private $templateHierarchy;

    /**
     * @var array
     */
    private $affectedTemplates = [];

    public function __construct(Environment $environment, TemplateHierarchy $templateHierarchy)
    {
        /** @var EscaperExtension $escaperExtension */
        $escaperExtension = $environment->getExtension(EscaperExtension::class);

        $escaperExtension->setEscaper(
            'contao_html',
            [(new ContaoEscaper()), '__invoke']
        );

        $this->templateHierarchy = $templateHierarchy;
    }

    /**
     * Register a template to be processed with the `contao_html` escaper
     * strategy. Only register templates that will receive input encoded
     * contexts!
     *
     * @internal
     */
    public function registerTemplateForInputEncoding(string $template): void
    {
        if (\in_array($template, $this->affectedTemplates, true)) {
            return;
        }

        $this->affectedTemplates[] = $template;
    }

    public function getNodeVisitors(): array
    {
        return [
            // Enables the 'contao_twig' escaper for Contao templates with
            // input encoding
            new ContaoEscaperNodeVisitor(
                function () {
                    return $this->affectedTemplates;
                }
            ),
        ];
    }

    public function getTokenParsers(): array
    {
        return [
            // Registers a parser for the 'extends' tag which will overwrite
            // the one of Twig's CoreExtension
            new DynamicExtendsTokenParser($this->templateHierarchy),
        ];
    }
}
