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

use Contao\BackendUser;
use Contao\CoreBundle\DataContainer\DcaUrlAnalyzer;
use Contao\CoreBundle\Event\MenuEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
#[AsEventListener]
readonly class BackendBreadcrumbListener
{
    public function __construct(
        private Security $security,
        private DcaUrlAnalyzer $dcaUrlAnalyzer,
        private TranslatorInterface $translator,
    ) {
    }

    public function __invoke(MenuEvent $event): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof BackendUser) {
            return;
        }

        if ('breadcrumbMenu' !== $event->getTree()->getName()) {
            return;
        }

        $factory = $event->getFactory();
        $tree = $event->getTree();

        foreach ($this->dcaUrlAnalyzer->getTrail(withTreeTrail: true) as $level => ['label' => $label, 'url' => $url, 'treeTrail' => $treeTrail, 'treeSiblings' => $treeSiblings]) {
            if (\count($treeTrail ?? []) > 0) {
                $nearestAncestor = array_pop($treeTrail);

                if ([] !== $treeTrail) {
                    $ancestorTrail = $factory->createItem('ancestor_trail')
                        ->setLabel('<button type="button" data-contao--toggle-state-target="controller" data-action="contao--toggle-state#toggle:prevent">'.$this->translator->trans('MSC.trail', [], 'contao_default').'</button>')
                        ->setAttribute('data-controller', 'contao--toggle-state')
                        ->setAttribute('data-action', 'click@document->contao--toggle-state#documentClick keydown.esc@document->contao--toggle-state#close')
                        ->setAttribute('data-contao--toggle-state-active-class', 'active')
                        ->setExtra('safe_label', true)
                        ->setChildrenAttribute('data-contao--toggle-state-target', 'controls')
                    ;

                    foreach ($treeTrail as $trail => ['label' => $trail_label, 'url' => $trail_url]) {
                        $ancestorTrail->addChild('ancestor_trail_'.$trail, [
                            'label' => $trail_label,
                            'uri' => $trail_url,
                        ]);
                    }

                    $tree->addChild($ancestorTrail);
                }

                $ancestor = $factory
                    ->createItem('ancestor_'.$level)
                    ->setLabel($nearestAncestor['label'])
                    ->setUri($nearestAncestor['url'])
                    ->setExtra('translation_domain', false)
                ;

                $tree->addChild($ancestor);
            }

            $current = $factory
                ->createItem('current_'.$level)
                ->setLabel($label)
                ->setExtra('translation_domain', false)
            ;

            if (null === $treeSiblings) {
                $current->setUri($url);
            } elseif (\count($treeSiblings) > 1) {
                $current
                    ->setLabel('<button type="button" data-contao--toggle-state-target="controller" data-action="contao--toggle-state#toggle:prevent">'.$label.'</button>')
                    ->setAttribute('data-controller', 'contao--toggle-state')
                    ->setAttribute('data-action', 'click@document->contao--toggle-state#documentClick keydown.esc@document->contao--toggle-state#close')
                    ->setAttribute('data-contao--toggle-state-active-class', 'active')
                    ->setExtra('safe_label', true)
                    ->setChildrenAttribute('data-contao--toggle-state-target', 'controls')
                ;

                foreach ($treeSiblings as $i => ['url' => $sibling_url, 'label' => $sibling_label, 'active' => $sibling_active]) {
                    $sibling = [
                        'label' => $sibling_label,
                        'uri' => $sibling_url,
                    ];

                    if ($sibling_active) {
                        unset($sibling['uri']);
                    }

                    $current->addChild('sibling_'.$i, $sibling);
                }
            }

            $tree->addChild($current);
        }
    }
}
