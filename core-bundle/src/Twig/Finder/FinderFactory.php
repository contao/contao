<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Finder;

use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchyInterface;
use Contao\CoreBundle\Twig\Loader\ThemeNamespace;
use Symfony\Contracts\Translation\TranslatorInterface;

class FinderFactory
{
    private TemplateHierarchyInterface $hierarchy;
    private ThemeNamespace $themeNamespace;
    private TranslatorInterface $translator;

    /**
     * @internal
     */
    public function __construct(TemplateHierarchyInterface $hierarchy, ThemeNamespace $themeNamespace, TranslatorInterface $translator)
    {
        $this->hierarchy = $hierarchy;
        $this->themeNamespace = $themeNamespace;
        $this->translator = $translator;
    }

    /**
     * Creates a new template finder instance.
     */
    public function create(): Finder
    {
        return new Finder($this->hierarchy, $this->themeNamespace, $this->translator);
    }
}
