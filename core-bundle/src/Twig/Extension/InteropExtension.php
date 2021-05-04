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

use Contao\CoreBundle\Twig\Interop\ContaoEscaper;
use Contao\CoreBundle\Twig\Interop\ContaoEscaperNodeVisitor;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\EscaperExtension;

class InteropExtension extends AbstractExtension
{
    /**
     * @var array
     */
    private $affectedTemplates = [];

    public function __construct(Environment $environment)
    {
        /** @var EscaperExtension $escaperExtension */
        $escaperExtension = $environment->getExtension(EscaperExtension::class);

        $escaperExtension->setEscaper(
            'contao_html',
            [(new ContaoEscaper()), '__invoke']
        );
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
            new ContaoEscaperNodeVisitor(
                function () {
                    return $this->affectedTemplates;
                }
            ),
        ];
    }
}
