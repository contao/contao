<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Menu;

use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\MenuEvent;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class BackendMenuBuilder
{
    /**
     * Whether the item is a group, overriding the automatically inferred behavior.
     */
    public const EXTRA_IS_GROUP = 'contao_backend_menu_is_group';

    /**
     * An icon image interpreted by the active backend menu template.
     */
    public const EXTRA_ICON = 'contao_backend_menu_icon';

    /**
     * Whether the item is visually highlighted independently of the current-item state.
     */
    public const EXTRA_IS_HIGHLIGHTED = 'contao_backend_menu_is_highlighted';

    /**
     * Whether the renderer should visually separate the item from the preceding item.
     */
    public const EXTRA_HAS_DIVIDER = 'contao_backend_menu_has_divider';

    /**
     * A Twig template used to render specialized item content.
     */
    public const EXTRA_CONTENT_TEMPLATE = 'contao_backend_menu_content_template';

    /**
     * @internal
     */
    public function __construct(
        private readonly FactoryInterface $factory,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function buildMainMenu(): ItemInterface
    {
        return $this->buildTree('mainMenu', ['class' => 'menu_level_0']);
    }

    public function buildHeaderMenu(): ItemInterface
    {
        return $this->buildTree('headerMenu', ['id' => 'tmenu']);
    }

    public function buildLoginMenu(): ItemInterface
    {
        return $this->buildTree('loginMenu');
    }

    public function buildBreadcrumbMenu(): ItemInterface
    {
        return $this->buildTree('breadcrumbMenu', ['id' => 'breadcrumb']);
    }

    private function buildTree(string $name, array $childrenAttributes = []): ItemInterface
    {
        $tree = $this->factory
            ->createItem($name)
            ->setChildrenAttributes($childrenAttributes)
        ;

        $this->eventDispatcher->dispatch(new MenuEvent($this->factory, $tree), ContaoCoreEvents::BACKEND_MENU_BUILD);

        return $tree;
    }
}
