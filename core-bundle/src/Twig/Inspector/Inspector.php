<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Inspector;

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * @experimental
 */
class Inspector
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function inspectTemplate(string $name): TemplateInformation
    {
        try {
            $blocks = $this->twig->load($name)->getBlockNames();
            $source = $this->twig->getLoader()->getSourceContext($name);
        } catch (LoaderError|RuntimeError|SyntaxError $e) {
            throw new InspectionException($name, $e);
        }

        return new TemplateInformation($source, $blocks);
    }
}
