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

        foreach ($this->dcaUrlAnalyzer->getTrail(withTreeTrail: true) as $index => $path) {
            $trail = $path['treeTrail'] ?? [];
            $siblings = $path['treeSiblings'] ?? [];

            if (count($trail) > 0) {
                $current = array_pop($trail);

                if (count($trail) > 0) {
                    $collapsedPath = $factory->createItem('collapsed_path_' . $index);

                    foreach ($trail as $j => $item) {
                        $collapsedPath->addChild('collapsed_path_'.$j, [
                            'label' => $item['label'],
                            'uri'   => $item['url'],
                        ]);
                    }

                    $tree->addChild($collapsedPath);
                }

                $currentTrail = $factory
                    ->createItem('current_trail_' . $index)
                    ->setLabel($current['label'])
                    ->setUri($current['url'])
                    ->setExtra('translation_domain', false)
                ;

                $tree->addChild($currentTrail);
            }

            $currentPath = $factory
                ->createItem('current_path_' . $index)
                ->setLabel($path['label'])
                ->setUri($path['url'])
                ->setExtra('translation_domain', false)
            ;

            if (count($siblings) > 0) {
                foreach ($siblings as $j => $sibling) {
                    $siblingPath['label'] = $sibling['label'];

                    if ($sibling['active'] !== true) {
                        $siblingPath['uri'] = $sibling['url'];
                    }

                    $currentPath->addChild('collapsed_path_'.$j, $siblingPath);
                }
            }

            $tree->addChild($currentPath);
        }
    }
}
