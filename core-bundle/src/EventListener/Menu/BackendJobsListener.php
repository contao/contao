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
use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\Job\Jobs;
use Contao\CoreBundle\Menu\BackendMenuBuilder;
use Knp\Menu\Util\MenuManipulator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[AsEventListener]
class BackendJobsListener
{
    public function __construct(
        private readonly Security $security,
        private readonly RouterInterface $router,
        private readonly Jobs $jobs,
    ) {
    }

    public function __invoke(MenuEvent $event): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof BackendUser) {
            return;
        }

        $name = $event->getTree()->getName();

        if ('headerMenu' !== $name) {
            return;
        }

        $tree = $event->getFactory()
            ->createItem('jobs')
            ->setLabel('MSC.jobs')
            ->setUri($this->router->generate('contao_backend', ['do' => 'jobs']))
            ->setExtra(BackendMenuBuilder::EXTRA_CONTENT_TEMPLATE, '@Contao/backend/jobs/menu_item.html.twig')
            ->setExtra('has_pending_jobs', [] !== $this->jobs->findMyNewOrPending())
            ->setExtra('translation_domain', 'contao_default')
        ;

        $event->getTree()->addChild($tree);

        // Move the favorites menu behind "alerts"
        new MenuManipulator()->moveToPosition($tree, 2);
    }
}
