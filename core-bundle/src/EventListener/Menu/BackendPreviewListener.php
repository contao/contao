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

use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Knp\Menu\Util\MenuManipulator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class BackendPreviewListener
{
    public function __construct(
        private readonly Security $security,
        private readonly RouterInterface $router,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(MenuEvent $event): void
    {
        if (!$this->security->isGranted('ROLE_USER')) {
            return;
        }

        $tree = $event->getTree();

        if ('headerMenu' !== $tree->getName()) {
            return;
        }

        $preview = $event->getFactory()
            ->createItem('preview')
            ->setLabel('MSC.fePreview')
            ->setUri($this->getPreviewUrl())
            ->setLinkAttribute('class', 'icon-preview')
            ->setLinkAttribute('title', $this->translator->trans('MSC.fePreviewTitle', [], 'contao_default'))
            ->setLinkAttribute('target', '_blank')
            ->setLinkAttribute('accesskey', 'f')
            ->setExtra('translation_domain', 'contao_default')
        ;

        $tree->addChild($preview);

        // The last two items are "submenu" and "burger", so make this the third to last
        (new MenuManipulator())->moveToPosition($preview, $tree->count() - 3);
    }

    private function getPreviewUrl(): string
    {
        if (!$request = $this->requestStack->getCurrentRequest()) {
            throw new \RuntimeException('The request stack did not contain a request');
        }

        $id = $this->getIdFromRequest($request);
        $do = $request->query->get('do', '');
        $url = $this->router->generate('contao_backend_preview');

        $event = new PreviewUrlCreateEvent($do, $id);
        $this->eventDispatcher->dispatch($event, ContaoCoreEvents::PREVIEW_URL_CREATE);

        if ($query = $event->getQuery()) {
            return $url.'?'.$query;
        }

        return $url;
    }

    private function getIdFromRequest(Request $request): int
    {
        return (int) ($request->query->get('id') ?? $request->query->get('pid'));
    }
}
