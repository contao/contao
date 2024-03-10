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

use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Loader\ThemeNamespace;
use Symfony\Contracts\Translation\TranslatorInterface;

class FinderFactory
{
    private ContaoFilesystemLoader $filesystemLoader;
    private ThemeNamespace $themeNamespace;
    private TranslatorInterface $translator;

    /**
     * @internal
     */
    public function __construct(ContaoFilesystemLoader $filesystemLoader, ThemeNamespace $themeNamespace, TranslatorInterface $translator)
    {
        $this->filesystemLoader = $filesystemLoader;
        $this->themeNamespace = $themeNamespace;
        $this->translator = $translator;
    }

    /**
     * Creates a new template finder instance.
     */
    public function create(): Finder
    {
        return new Finder($this->filesystemLoader, $this->themeNamespace, $this->translator);
    }
}
