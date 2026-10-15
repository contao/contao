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
use Contao\CoreBundle\Doctrine\DBAL\ChildTraversalOptions;
use Contao\CoreBundle\Doctrine\DBAL\Hierarchy;
use Contao\CoreBundle\Doctrine\DBAL\HierarchyDefinition;
use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\Util\UrlUtil;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\Util\MenuManipulator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[AsEventListener]
class BackendFavoritesListener
{
    public function __construct(
        private readonly Security $security,
        private readonly RouterInterface $router,
        private readonly RequestStack $requestStack,
        private readonly Hierarchy $hierarchy,
    ) {
    }

    public function __invoke(MenuEvent $event): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof BackendUser) {
            return;
        }

        $name = $event->getTree()->getName();

        if ('mainMenu' === $name) {
            $this->buildMainMenu($event, $user);
        }
    }

    private function buildMainMenu(MenuEvent $event, BackendUser $user): void
    {
        if (!$request = $this->requestStack->getCurrentRequest()) {
            return;
        }

        $factory = $event->getFactory();

        $params = [
            'do' => $request->query->get('do'),
            'mtg' => 'favorites',
        ];

        $tree = $factory
            ->createItem('favorites')
            ->setAttribute('id', 'favorites-menu')
            ->setLabel('MSC.favorites')
            ->setUri($this->router->generate('contao_backend', $params))
            ->setExtra('translation_domain', 'contao_default')
        ;

        $requestUri = UrlUtil::getNormalizePathAndQuery($request->getRequestUri());

        $items = $this->createFavoriteItems($factory, $requestUri, $user->id);

        $this->buildTree($tree, $items);

        if (!$tree->hasChildren()) {
            return;
        }

        $event->getTree()->addChild($tree);

        // Move the favorites menu to the top
        new MenuManipulator()->moveToFirstPosition($tree);
    }

    /**
     * @return array<int, array<int, ItemInterface>>
     */
    private function createFavoriteItems(FactoryInterface $factory, string $requestUri, int $user): array
    {
        // Ignore drafts and their descendants
        $options = new ChildTraversalOptions()
            ->withColumns('title', 'url')
            ->withOrderBy('sorting')
            ->withWhere('tstamp > 0')
        ;

        $nodes = $this->hierarchy->getChildRows(
            [0],
            new HierarchyDefinition('tl_favorites', 'id', 'pid')->withScope('user', $user),
            $options,
        );

        $items = [];

        foreach ($nodes as $node) {
            $item = $factory
                ->createItem('favorite_'.$node['id'])
                ->setAttribute('id', 'favorites-menu-'.$node['id'])
                ->setLabel($node['title'])
                ->setUri($node['url'])
                ->setCurrent($node['url'] === $requestUri)
                ->setExtra('translation_domain', false)
            ;

            $items[(int) $node['pid']][(int) $node['id']] = $item;
        }

        return $items;
    }

    /**
     * @param array<int, array<int, ItemInterface>> $items
     */
    private function buildTree(ItemInterface $tree, array $items, int $pid = 0): void
    {
        foreach ($items[$pid] ?? [] as $id => $item) {
            $tree->addChild($item);

            $this->buildTree($item, $items, $id);
        }
    }
}
