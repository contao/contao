<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\EventListener;

use Contao\CoreBundle\Event\MenuEvent;
use Contao\ManagerBundle\HttpKernel\JwtManager;
use Knp\Menu\ItemInterface;
use Knp\Menu\Util\MenuManipulator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class BackendMenuListener
{
    public function __construct(
        private readonly Security $security,
        private readonly RouterInterface $router,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly bool $debug,
        private readonly string|null $managerPath,
        private readonly JwtManager|null $jwtManager,
    ) {
    }

    public function __invoke(MenuEvent $event): void
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $this->addDebugButton($event);
        $this->addManagerLink($event);
    }

    /**
     * Adds a debug button to the back end header navigation.
     */
    private function addDebugButton(MenuEvent $event): void
    {
        if (!$this->jwtManager instanceof JwtManager) {
            return;
        }

        $tree = $event->getTree();

        if ('headerMenu' !== $tree->getName()) {
            return;
        }

        if (!$request = $this->requestStack->getCurrentRequest()) {
            throw new \RuntimeException('The request stack did not contain a request');
        }

        $params = [
            'do' => 'debug',
            'key' => $this->debug ? 'disable' : 'enable',
            'referer' => base64_encode($request->server->get('QUERY_STRING', '')),
            'ref' => $request->attributes->get('_contao_referer_id'),
        ];

        $class = 'icon-debug';

        if ($this->debug) {
            $class .= ' hover';
        }

        $debug = $event->getFactory()
            ->createItem('debug')
            ->setLabel('debug_mode')
            ->setUri($this->router->generate('contao_backend', $params))
            ->setLinkAttribute('class', $class)
            ->setLinkAttribute('title', $this->translator->trans('debug_mode', [], 'ContaoManagerBundle'))
            ->setExtra('translation_domain', 'ContaoManagerBundle')
        ;

        $tree->addChild($debug);

        // The last two items are "submenu" and "burger", so make this the third to last
        (new MenuManipulator())->moveToPosition($debug, $tree->count() - 3);
    }

    /**
     * Adds a link to the Contao Manager to the back end main navigation.
     */
    private function addManagerLink(MenuEvent $event): void
    {
        if (null === $this->managerPath) {
            return;
        }

        $categoryNode = $event->getTree()->getChild('system');

        if (!$categoryNode instanceof ItemInterface) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (!$request instanceof Request) {
            return;
        }

        $item = $event->getFactory()
            ->createItem('contao_manager')
            ->setLabel('Contao Manager')
            ->setUri($request->getUriForPath('/'.$this->managerPath))
            ->setLinkAttribute('class', 'navigation contao_manager')
            ->setLinkAttribute('title', $this->translator->trans('contao_manager_title', [], 'ContaoManagerBundle'))
            ->setExtra('translation_domain', false)
        ;

        $categoryNode->addChild($item);
    }
}
