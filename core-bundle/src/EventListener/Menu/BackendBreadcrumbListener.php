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

/**
 * @internal
 */
#[AsEventListener]
readonly class BackendBreadcrumbListener
{
    public function __construct(
        private Security $security,
        private DcaUrlAnalyzer $dcaUrlAnalyzer,
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
            $current = $factory
                ->createItem('current_'.$level)
                ->setLabel($label)
                ->setUri($url)
                ->setExtra('translation_domain', false)
            ;

            if (\count($treeTrail ?? []) > 0) {
                $nearestAncestor = array_pop($treeTrail);

                if ([] !== $treeTrail) {
                    $ancestorTrail = $factory->createItem('ancestor_trail_'.$level);

                    foreach ($treeTrail as $trailLevel => ['label' => $label, 'url' => $url]) {
                        $ancestorTrail->addChild('ancestor_trail_'.$trailLevel, [
                            'label' => $label,
                            'uri' => $url,
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

            foreach (($treeSiblings ?? []) as $i => ['url' => $url, 'label' => $label, 'active' => $active]) {
                $sibling = [
                    'label' => $label,
                ];

                if (!$active) {
                    $sibling['uri'] = $url;
                }

                $current->addChild('sibling_'.$i, $sibling);
            }

            $tree->addChild($current);
        }
    }
}
