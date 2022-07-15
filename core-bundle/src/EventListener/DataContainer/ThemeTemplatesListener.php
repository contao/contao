<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Exception\InvalidThemePathException;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoaderWarmer;
use Contao\CoreBundle\Twig\Loader\ThemeNamespace;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCallback(table: "tl_theme", target: "fields.templates.save")]
class ThemeTemplatesListener
{
    public function __construct(
        private ContaoFilesystemLoaderWarmer $filesystemLoaderWarmer,
        private ThemeNamespace $themeNamespace,
        private TranslatorInterface $translator,
    ) {
    }

    public function __invoke(string $value): string
    {
        // Make sure the selected theme path can be converted into a slug
        try {
            $this->themeNamespace->generateSlug($value);
        } catch (InvalidThemePathException $e) {
            throw new \RuntimeException($this->translator->trans('ERR.invalidThemeTemplatePath', [$e->getPath(), implode('', $e->getInvalidCharacters())], 'contao_default'), 0, $e);
        }

        $this->filesystemLoaderWarmer->refresh();

        return $value;
    }
}
