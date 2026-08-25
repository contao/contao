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
use Contao\CoreBundle\Menu\BackendMenuBuilder;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
#[AsEventListener]
class BackendLoginListener
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function __invoke(MenuEvent $event): void
    {
        $tree = $event->getTree();

        if ('loginMenu' !== $tree->getName()) {
            return;
        }

        $factory = $event->getFactory();
        $passkeyLogin = $this->translator->trans('MSC.passkeyLogin', [], 'contao_default');

        $passkey = $factory
            ->createItem('passkey')
            ->setLabel($passkeyLogin)
            ->setAttribute('class', 'passkey')
            ->setExtra(BackendMenuBuilder::EXTRA_CONTENT_TEMPLATE, '@Contao/backend/menu/item/_passkey.html.twig')
            ->setExtra('translation_domain', false)
        ;

        $tree->addChild($passkey);
    }
}
