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
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Event\MenuEvent;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\Util\MenuManipulator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
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
        private readonly ContaoCsrfTokenManager $tokenManager,
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
        } elseif ('headerMenu' === $name) {
            $this->buildHeaderMenu($event, $user);
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
            'ref' => $request->attributes->get('_contao_referer_id'),
        ];

        $bag = $this->requestStack->getSession()->getBag('contao_backend');

        if (!$bag instanceof AttributeBagInterface) {
            return;
        }

        $collapsed = 0 === ($bag->get('backend_modules')['favorites'] ?? null);

        $tree = $factory
            ->createItem('favorites')
            ->setLabel($this->translator->trans('MSC.favorites', [], 'contao_default'))
            ->setUri($this->router->generate('contao_backend', $params))
            ->setLinkAttribute('class', 'group-favorites')
            ->setLinkAttribute('title', $this->translator->trans($collapsed ? 'MSC.expandNode' : 'MSC.collapseNode', [], 'contao_default'))
            ->setLinkAttribute('data-action', 'contao--toggle-navigation#toggle:prevent')
            ->setLinkAttribute('data-contao--toggle-navigation-category-param', 'favorites')
            ->setLinkAttribute('aria-controls', 'favorites')
            ->setChildrenAttribute('id', 'favorites')
            ->setExtra('translation_domain', false)
        ;

        if ($collapsed) {
            $tree->setAttribute('class', 'collapsed');
        } else {
            $tree->setLinkAttribute('aria-expanded', 'true');
        }

        $requestUri = $this->getRequestUri($request);
        $ref = $request->attributes->get('_contao_referer_id');

        $this->buildTree($tree, $factory, $requestUri, $ref, $user->id);

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
        (new MenuManipulator())->moveToFirstPosition($tree);
    }

    private function buildHeaderMenu(MenuEvent $event, BackendUser $user): void
    {
        if (!$request = $this->requestStack->getCurrentRequest()) {
            return;
        }

        $url = $this->getRequestUri($request);

        $exists = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM tl_favorites WHERE url = :url AND user = :user',
            [
                'url' => $url,
                'user' => $user->id,
            ],
        );

        $factory = $event->getFactory();

        if ($exists) {
            $tree = $this->addEditFavoritesLink($factory, $request);
        } else {
            $tree = $this->addSaveAsFavoriteLink($factory, $request, $url);
        }

        $event->getTree()->addChild($tree);

        // Move the favorites menu behind "manual"
        (new MenuManipulator())->moveToPosition($tree, 1);
    }

    private function buildTree(ItemInterface $tree, FactoryInterface $factory, string $requestUri, string $ref, int $user, int $pid = 0): void
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
                ->setLabel(StringUtil::decodeEntities($node['title']))
                ->setUri($node['url'].(str_contains((string) $node['url'], '?') ? '&' : '?').'ref='.$ref)
                ->setLinkAttribute('class', 'navigation')
                ->setLinkAttribute('title', StringUtil::decodeEntities($node['title']))
                ->setCurrent($node['url'] === $requestUri)
                ->setExtra('translation_domain', false)
            ;

            $tree->addChild($item);

            $this->buildTree($item, $factory, $requestUri, $ref, $user, (int) $node['id']);
        }
    }

    private function addSaveAsFavoriteLink(FactoryInterface $factory, Request $request, string $url): ItemInterface
    {
        $favoriteTitle = $this->translator->trans('MSC.favorite', [], 'contao_default');

        $favoriteData = [
            'do' => 'favorites',
            'act' => 'paste',
            'mode' => 'create',
            'data' => base64_encode($url),
            'rt' => $this->tokenManager->getDefaultTokenValue(),
            'ref' => $request->attributes->get('_contao_referer_id'),
        ];

        return $factory
            ->createItem('favorite')
            ->setLabel($favoriteTitle)
            ->setUri($this->router->generate('contao_backend', $favoriteData))
            ->setLinkAttribute('class', 'icon-favorite')
            ->setLinkAttribute('title', $favoriteTitle)
            ->setExtra('safe_label', true)
            ->setExtra('translation_domain', false)
        ;
    }

    private function addEditFavoritesLink(FactoryInterface $factory, Request $request): ItemInterface
    {
        $favoriteTitle = $this->translator->trans('MSC.editFavorites', [], 'contao_default');

        $favoriteData = [
            'do' => 'favorites',
            'ref' => $request->attributes->get('_contao_referer_id'),
        ];

        return $factory
            ->createItem('favorite')
            ->setLabel($favoriteTitle)
            ->setUri($this->router->generate('contao_backend', $favoriteData))
            ->setLinkAttribute('class', 'icon-favorite icon-favorite--active')
            ->setLinkAttribute('title', $favoriteTitle)
            ->setExtra('safe_label', true)
            ->setExtra('translation_domain', false)
        ;
    }

    private function getRequestUri(Request $request): string
    {
        if (null !== $qs = $request->getQueryString()) {
            parse_str($qs, $pairs);
            ksort($pairs);

            unset($pairs['rt'], $pairs['ref'], $pairs['revise']);

            if ([] !== $pairs) {
                $qs = '?'.http_build_query($pairs, '', '&', PHP_QUERY_RFC3986);
            }
        }

        return $request->getBaseUrl().$request->getPathInfo().$qs;
    }
}
