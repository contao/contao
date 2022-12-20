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
use Doctrine\DBAL\Connection;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\Util\MenuManipulator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class BackendFavoritesListener
{
    public function __construct(
        private Security $security,
        private RouterInterface $router,
        private RequestStack $requestStack,
        private Connection $connection,
        private TranslatorInterface $translator,
    ) {
    }

    public function __invoke(MenuEvent $event): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof BackendUser) {
            return;
        }

        $name = $event->getTree()->getName();

        if ('mainMenu' !== $name) {
            return;
        }

        $this->buildFavoritesMenu($event, $user);
    }

    private function buildFavoritesMenu(MenuEvent $event, BackendUser $user): void
    {
        if (!$request = $this->requestStack->getCurrentRequest()) {
            throw new \RuntimeException('The request stack did not contain a request');
        }

        $factory = $event->getFactory();
        $path = $this->router->generate('contao_backend');

        $params = [
            'do' => $request->query->get('do'),
            'mtg' => 'favorites',
            'ref' => $request->attributes->get('_contao_referer_id'),
        ];

        $session = $this->requestStack->getSession()->getBag('contao_backend');
        $collapsed = 0 === ($session['backend_modules']['favorites'] ?? null);

        $tree = $factory
            ->createItem('favorites')
            ->setLabel($this->translator->trans('MSC.favorites', [], 'contao_default'))
            ->setUri($this->router->generate('contao_backend', $params))
            ->setLinkAttribute('class', 'group-favorites')
            ->setLinkAttribute('title', $this->translator->trans($collapsed ? 'MSC.expandNode' : 'MSC.collapseNode', [], 'contao_default'))
            ->setLinkAttribute('onclick', "return AjaxRequest.toggleNavigation(this, 'favorites', '$path')")
            ->setLinkAttribute('aria-controls', 'favorites')
            ->setChildrenAttribute('id', 'favorites')
            ->setLinkAttribute('aria-expanded', 'true')
            ->setExtra('translation_domain', false)
        ;

        if ($collapsed) {
            $tree->setAttribute('class', 'collapsed');
            $tree->setLinkAttribute('aria-expanded', 'false');
        }

        $this->buildTree($tree, $factory, $user->id);

        if (!$tree->hasChildren()) {
            return;
        }

        foreach ($tree->getChildren() as $children) {
            if ($children->hasChildren()) {
                $children->setAttribute('class', 'has-children');
            }
        }

        $event->getTree()->addChild($tree);

        // Move the favorites menu to the top
        (new MenuManipulator())->moveToPosition($tree, 0);
    }

    private function buildTree(ItemInterface $tree, FactoryInterface $factory, int $user, int $pid = 0): void
    {
        if (!$request = $this->requestStack->getCurrentRequest()) {
            throw new \RuntimeException('The request stack did not contain a request');
        }

        parse_str($request->server->get('QUERY_STRING'), $pairs);
        unset($pairs['rt'], $pairs['ref'], $pairs['revise']);

        $uri = '';

        if (!empty($pairs)) {
            $uri = '?'.http_build_query($pairs, '', '&', PHP_QUERY_RFC3986);
        }

        $requestUri = $request->getBaseUrl().$request->getPathInfo().$uri;

        $nodes = $this->connection->fetchAllAssociative(
            'SELECT * FROM tl_favorites WHERE pid = :pid AND user = :user ORDER BY sorting',
            [
                'pid' => $pid,
                'user' => $user,
            ]
        );

        $ref = $request->attributes->get('_contao_referer_id');

        foreach ($nodes as $node) {
            // Ignore drafts
            if ($node['tstamp'] < 1) {
                continue;
            }

            $item = $factory
                ->createItem($node['title'])
                ->setLabel($node['title'])
                ->setUri($node['url'].(str_contains((string) $node['url'], '?') ? '&' : '?').'ref='.$ref)
                ->setLinkAttribute('class', 'navigation')
                ->setLinkAttribute('title', $node['title'])
                ->setCurrent($node['url'] === $requestUri)
                ->setExtra('translation_domain', false)
            ;

            $tree->addChild($item);

            $this->buildTree($item, $factory, $user, $node['id']);
        }
    }
}
