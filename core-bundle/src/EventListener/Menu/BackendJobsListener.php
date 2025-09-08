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
use Knp\Menu\Util\MenuManipulator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

/**
 * @internal
 */
#[AsEventListener]
class BackendJobsListener
{
    public function __construct(
        private readonly Security $security,
        private readonly Environment $twig,
        private readonly RouterInterface $router,
        private readonly RequestStack $requestStack,
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

        $markup = $this->twig->render('@Contao/backend/jobs/_menu_item.html.twig', [
            'jobs_link' => $this->router->generate('contao_backend', ['do' => 'jobs', 'ref' => $this->getRefererId()]),
            'has_pending_jobs' => [] !== $this->jobs->findMyNewOrPending(),
        ]);

        $tree = $event->getFactory()
            ->createItem('jobs')
            ->setLabel($markup)
            ->setExtra('safe_label', true)
            ->setExtra('translation_domain', false)
        ;

        $event->getTree()->addChild($tree);

        // Move the favorites menu behind "alerts"
        (new MenuManipulator())->moveToPosition($tree, 2);
    }

    private function getRefererId(): string
    {
        if (!$request = $this->requestStack->getCurrentRequest()) {
            throw new \RuntimeException('The request stack did not contain a request');
        }

        return $request->attributes->get('_contao_referer_id');
    }
}
