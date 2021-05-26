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

use Contao\ArticleModel;
use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
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
    /**
     * @var Security
     */
    private $security;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(Security $security, RouterInterface $router, RequestStack $requestStack, TranslatorInterface $translator, EventDispatcherInterface $eventDispatcher, ContaoFramework $framework)
    {
        $this->security = $security;
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->eventDispatcher = $eventDispatcher;
        $this->framework = $framework;
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
            ->setLabel($this->trans('MSC.fePreview'))
            ->setUri($this->getPreviewUrl())
            ->setLinkAttribute('title', $this->trans('MSC.fePreviewTitle'))
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

    private function trans(string $id): string
    {
        return $this->translator->trans($id, [], 'contao_default');
    }

    private function getPreviewUrl(): string
    {
        if (!$request = $this->requestStack->getCurrentRequest()) {
            throw new \RuntimeException('The request stack did not contain a request');
        }

        $id = $this->getIdFromRequest($request);
        $url = $this->router->generate('contao_backend_preview');

        if (!$id || !$do = $request->query->get('do')) {
            return $url;
        }

        if ('page' === $do) {
            return $url.'?page='.$id;
        }

        if ('article' === $do) {
            /** @var ArticleModel $adapter */
            $adapter = $this->framework->getAdapter(ArticleModel::class);

            if (!$article = $adapter->findByPk($id)) {
                return $url;
            }

            return $url.'?page='.$article->pid;
        }

        $event = new PreviewUrlCreateEvent($do, $id);
        $this->eventDispatcher->dispatch($event, ContaoCoreEvents::PREVIEW_URL_CREATE);

        if ($query = $event->getQuery()) {
            return $url.'?'.$query;
        }

        return $url;
    }

    private function getIdFromRequest(Request $request): int
    {
        if (!$request->query->has('table')) {
            return (int) $request->query->get('id');
        }

        return (int) $request->getSession()->get('CURRENT_ID');
    }
}
