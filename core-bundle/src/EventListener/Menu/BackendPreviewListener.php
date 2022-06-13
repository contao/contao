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
        private Security $security,
        private RouterInterface $router,
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
        private EventDispatcherInterface $eventDispatcher,
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

        $children = [];

        // Try adding the preview button after the alerts button
        foreach ($tree->getChildren() as $name => $item) {
            $children[$name] = $item;

            if ('alerts' === $name) {
                $children['preview'] = $preview;
            }
        }

        // Prepend the preview button if it could not be added above
        if (!isset($children['preview'])) {
            $children = ['preview' => $preview] + $children;
        }

        $tree->setChildren($children);
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
