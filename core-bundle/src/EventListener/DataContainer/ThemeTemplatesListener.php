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

use Contao\CoreBundle\Exception\InvalidThemePathException;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoaderWarmer;
use Contao\CoreBundle\Twig\Loader\TemplateLocator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Callback(table="tl_theme", target="fields.templates.save")
 */
class ThemeTemplatesListener
{
    /**
     * @var ContaoFilesystemLoaderWarmer
     */
    private $filesystemLoaderWarmer;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(ContaoFilesystemLoaderWarmer $filesystemLoaderWarmer, TranslatorInterface $translator)
    {
        $this->filesystemLoaderWarmer = $filesystemLoaderWarmer;
        $this->translator = $translator;
    }

    public function __invoke(string $value): string
    {
        try {
            // Make sure the selected theme path can be converted into a slug
            TemplateLocator::createDirectorySlug($value);
        } catch (InvalidThemePathException $e) {
            throw new \RuntimeException($this->translator->trans('ERR.invalidThemeTemplatePath', [$e->getPath(), implode('', $e->getInvalidCharacters())], 'contao_default'), 0, $e);
        }

        $this->filesystemLoaderWarmer->refresh();

        return $value;
    }
}
