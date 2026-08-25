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
use Contao\CoreBundle\Util\UrlUtil;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\Util\MenuManipulator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
        private readonly Connection $connection,
        private readonly TranslatorInterface $translator,
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
            ->setLabel($this->translator->trans('MSC.favorites', [], 'contao_default'))
            ->setUri($this->router->generate('contao_backend', $params))
            ->setExtra('translation_domain', false)
        ;

        $requestUri = UrlUtil::getNormalizePathAndQuery($request->getRequestUri());

        $this->buildTree($tree, $factory, $requestUri, $user->id);

        if (!$tree->hasChildren()) {
            return;
        }

        $event->getTree()->addChild($tree);

        // Move the favorites menu to the top
        new MenuManipulator()->moveToFirstPosition($tree);
    }

    private function buildTree(ItemInterface $tree, FactoryInterface $factory, string $requestUri, int $user, int $pid = 0): void
    {
        $nodes = $this->connection->fetchAllAssociative(
            'SELECT * FROM tl_favorites WHERE pid = :pid AND user = :user ORDER BY sorting',
            [
                'pid' => $pid,
                'user' => $user,
            ],
        );

        foreach ($nodes as $node) {
            // Ignore drafts
            if ($node['tstamp'] < 1) {
                continue;
            }

            $item = $factory
                ->createItem('favorite_'.$node['id'])
                ->setAttribute('id', 'favorites-menu-'.$node['id'])
                ->setLabel(StringUtil::decodeEntities($node['title']))
                ->setUri($node['url'])
                ->setCurrent($node['url'] === $requestUri)
                ->setExtra('title', StringUtil::decodeEntities($node['title']))
                ->setExtra('translation_domain', false)
            ;

            $tree->addChild($item);

            $this->buildTree($item, $factory, $requestUri, $user, (int) $node['id']);
        }
    }
}
