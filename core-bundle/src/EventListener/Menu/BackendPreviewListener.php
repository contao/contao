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
use Contao\BackendUser;
use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
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

    public function onBuild(MenuEvent $event): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof BackendUser) {
            return;
        }

        $name = $event->getTree()->getName();

        if ('headerMenu' !== $name) {
            return;
        }

        $factory = $event->getFactory();
        $tree = $event->getTree();

        $preview = $factory
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

        $url = $this->router->generate('contao_backend_preview');

        // FIXME: rebuild the CURRENT_ID logic
        if (!\defined('CURRENT_ID') || !CURRENT_ID || !$do = $request->query->get('do')) {
            return $url;
        }

        if ('page' === $do) {
            return $url.'?page='.CURRENT_ID;
        }

        if ('article' === $do) {
            /** @var ArticleModel $adapter */
            $adapter = $this->framework->getAdapter(ArticleModel::class);

            if (!$article = $adapter->findByPk(CURRENT_ID)) {
                return $url;
            }

            return $url.'?page='.$article->pid;
        }

        $event = new PreviewUrlCreateEvent($do, CURRENT_ID);
        $this->eventDispatcher->dispatch($event, ContaoCoreEvents::PREVIEW_URL_CREATE);

        if ($query = $event->getQuery()) {
            return $url.'?'.$query;
        }

        return $url;
    }
}
