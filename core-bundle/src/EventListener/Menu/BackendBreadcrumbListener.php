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
            if (\count($treeTrail ?? []) > 0) {
                $ancestorTrail = $factory->createItem('ancestor_trail')->setExtra('render_dropdown', true);

                foreach ($treeTrail as $trail => ['label' => $trail_label, 'url' => $trail_url]) {
                    $ancestorTrail->addChild('ancestor_trail_'.$trail, [
                        'label' => $trail_label,
                        'uri' => $trail_url,
                    ]);
                }

                $tree->addChild($ancestorTrail);
            }

            $hasSiblings = \is_array($treeSiblings) && \count($treeSiblings) > 1;

            $current = $factory
                ->createItem('current_'.$level)
                ->setLabel($label)
                ->setUri($hasSiblings ? $url : null)
                ->setExtra('render_dropdown', $hasSiblings)
            ;

            if ($hasSiblings) {
                foreach ($treeSiblings as $i => ['url' => $sibling_url, 'label' => $sibling_label, 'active' => $sibling_active]) {
                    $item = $current->addChild('sibling_'.$i, [
                        'label' => $sibling_label,
                        'uri' => $sibling_url,
                    ]);

                    if ($sibling_active) {
                        $item->setCurrent(true);
                    }
                }
            }

            $tree->addChild($current);
        }
    }
}
