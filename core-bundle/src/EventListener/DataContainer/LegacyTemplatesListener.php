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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Message;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LegacyTemplatesListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly TranslatorInterface $translator,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly bool $templateStudioEnabled = true,
    ) {
    }

    #[AsCallback(table: 'tl_templates', target: 'config.onload')]
    public function addInfoMessage(): void
    {
        if (!$this->templateStudioEnabled) {
            return;
        }

        $reference = \sprintf(
            '<a href="%s">%s</a>',
            $this->urlGenerator->generate('contao_template_studio'),
            $this->translator->trans('MOD.template_studio.0', [], 'contao_default'),
        );

        $message = $this->translator->trans('tl_templates.twig_studio_hint', [$reference], 'contao_templates');

        $this->framework->getAdapter(Message::class)->addInfo($message);
    }
}
