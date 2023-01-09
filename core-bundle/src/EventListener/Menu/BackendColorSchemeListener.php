<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\Menu;

use Contao\CoreBundle\Event\MenuEvent;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class BackendColorSchemeListener
{
    public function __construct(private Security $security, private TranslatorInterface $translator)
    {
    }

    public function __invoke(MenuEvent $event): void
    {
        if (!$this->security->isGranted('ROLE_USER')) {
            return;
        }

        $tree = $event->getTree();

        if ('headerMenu' !== $tree->getName() || !$submenu = $tree->getChild('submenu')) {
            return;
        }

        $colorScheme = $event
            ->getFactory()
            ->createItem('color-scheme')
            ->setUri('#')
            ->setAttribute('class', 'color-scheme')
            ->setLinkAttribute('class', 'icon-color-scheme')
            ->setLinkAttribute('data-controller', 'contao--color-scheme')
            ->setLinkAttribute('data-contao--color-scheme-target', 'label')
            ->setLinkAttribute(
                'data-contao--color-scheme-i18n-value',
                json_encode([
                    'dark' => $this->translator->trans('MSC.darkMode', [], 'contao_default'),
                    'light' => $this->translator->trans('MSC.lightMode', [], 'contao_default'),
                ])
            )
            ->setExtra('safe_label', true)
            ->setExtra('translation_domain', false)
        ;

        $submenu->addChild($colorScheme);
    }
}
