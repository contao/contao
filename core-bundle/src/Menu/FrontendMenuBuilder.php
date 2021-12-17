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

use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\Database;
use Contao\Date;
use Contao\Environment;
use Contao\PageModel;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuItem;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class FrontendMenuBuilder
{
    private FactoryInterface $factory;
    private RequestStack $requestStack;
    private EventDispatcherInterface $dispatcher;
    private Connection $connection;
    private PageRegistry $pageRegistry;
    private TokenChecker $tokenChecker;
    private Security $security;
    private LoggerInterface $logger;

    public function __construct(FactoryInterface $factory, RequestStack $requestStack, EventDispatcherInterface $dispatcher, Connection $connection, PageRegistry $pageRegistry, TokenChecker $tokenChecker, Security $security, LoggerInterface $logger)
    {
        $this->factory = $factory;
        $this->requestStack = $requestStack;
        $this->dispatcher = $dispatcher;
        $this->connection = $connection;
        $this->pageRegistry = $pageRegistry;
        $this->tokenChecker = $tokenChecker;
        $this->security = $security;
        $this->logger = $logger;
    }

    public function getMenu(ItemInterface $root, int $pid, int $level = 1, string $host = null, array $options = []): ItemInterface
    {
        if (null === ($pages = $this->getPages($pid, $options))) {
            return $root;
        }

        $request = $this->requestStack->getCurrentRequest();
        $requestPage = $request->attributes->get('pageModel');

        $isMember = $this->security->isGranted('ROLE_MEMBER');

        foreach ($pages as ['page' => $page, 'hasSubpages' => $hasSubpages]) {
            // Skip hidden sitemap pages
            if (($options['isSitemap'] ?? false) && 'map_never' === $page->sitemap) {
                continue;
            }

            $page->loadDetails();

            $item = new MenuItem($page->title, $this->factory);

            // Override the domain (see #3765)
            if (null !== $host) {
                $page->domain = $host;
            }

            if ($page->tabindex > 0) {
                trigger_deprecation('contao/core-bundle', '4.12', 'Using a tabindex value greater than 0 has been deprecated and will no longer work in Contao 5.0.');
            }

            // Hide the page if it is not protected and only visible to guests (backwards compatibility)
            if ($page->guests && !$page->protected && $isMember) {
                trigger_deprecation('contao/core-bundle', '4.12', 'Using the "show to guests only" feature has been deprecated an will no longer work in Contao 5.0. Use the "protect page" function instead.');
                continue;
            }

            // PageModel->groups is an array after calling loadDetails()
            if (
                $page->protected && !$options['showProtected']
                && (!($options['isSitemap'] ?? false) || 'map_always' !== $page->sitemap)
                && !$this->security->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, $page->groups)
            ) {
                continue;
            }

            ++$level;

            // Check whether there will be subpages
            if ($hasSubpages) {
                $this->getMenu($item, (int) $page->id, $level, $host, $options);

                $childRecords = Database::getInstance()->getChildRecords($page->id, 'tl_page');

                if (
                    !$options['showLevel']
                    || $options['showLevel'] >= $level
                    || (!$options['hardLimit'] && (($requestPage->id === $page->id) || \in_array($requestPage->id, $childRecords, false)))
                ) {
                    $item->setDisplayChildren(false);
                }
            }

            if (null === $href = $this->generateUri($page, $item)) {
                continue;
            }

            $this->populateMenuItem($item, $requestPage, $page, $href, $options);
            $root->addChild($item);
        }

        // TODO this is the same event as for the backend. must introduce new event
        $menuEvent = new MenuEvent($this->factory, $root);
        $this->dispatcher->dispatch($menuEvent);

        return $root;
    }

    /**
     * Get all published pages by their parent ID and add the "hasSubpages" property.
     *
     * @return array<array{page:PageModel,hasSubpages:bool}>|null
     */
    protected function findPagesByPid(int $pid, bool $showHidden = false, bool $isSitemap = false): ?array
    {
        $time = Date::floorToMinute();
        $blnBeUserLoggedIn = $this->tokenChecker->hasBackendUser() && $this->tokenChecker->isPreviewMode();
        $unroutableTypes = $this->pageRegistry->getUnroutableTypes();

        $pages = $this->connection
            ->executeQuery("SELECT p1.id, EXISTS(SELECT * FROM tl_page p2 WHERE p2.pid=p1.id AND p2.type!='root' AND p2.type NOT IN ('".implode("', '", $unroutableTypes)."')".(!$showHidden ? ($isSitemap ? " AND (p2.hide='' OR sitemap='map_always')" : " AND p2.hide=''") : '').(!$blnBeUserLoggedIn ? " AND p2.published='1' AND (p2.start='' OR p2.start<='$time') AND (p2.stop='' OR p2.stop>'$time')" : '').") AS hasSubpages FROM tl_page p1 WHERE p1.pid=:pid AND p1.type!='root' AND p1.type NOT IN ('".implode("', '", $unroutableTypes)."')".(!$showHidden ? ($isSitemap ? " AND (p1.hide='' OR sitemap='map_always')" : " AND p1.hide=''") : '').(!$blnBeUserLoggedIn ? " AND p1.published='1' AND (p1.start='' OR p1.start<='$time') AND (p1.stop='' OR p1.stop>'$time')" : '').' ORDER BY p1.sorting', ['pid' => $pid])
            ->fetchAllAssociative()
        ;

        if (\count($pages) < 1) {
            return null;
        }

        // Load models into the registry with a single query
        PageModel::findMultipleByIds(array_map(static fn ($row) => $row['id'], $pages));

        return array_map(
            static fn (array $row): array => [
                'page' => PageModel::findByPk($row['id']),
                'hasSubpages' => (bool) $row['hasSubpages'],
            ],
            $pages
        );
    }

    private function getPages(int $pid, array $options): ?array
    {
        return $this->findPagesByPid($pid, (bool) $options['showHidden']);
    }

    private function generateUri(PageModel $pageModel, ItemInterface $menuItem): ?string
    {
        switch ($pageModel->type) {
            case 'redirect':
                $href = $pageModel->url;

                if (0 === strncasecmp($href, 'mailto:', 7)) {
                    $href = StringUtil::encodeEmail($href);
                }

                return $href;

            case 'root':
                // Overwrite the alias to link to the empty URL or language URL (see #1641)
                $pageModel->alias = 'index';

                return $pageModel->getFrontendUrl();

            case 'forward':
                if ($pageModel->jumpTo) {
                    $jumpTo = PageModel::findPublishedById($pageModel->jumpTo);
                } else {
                    $jumpTo = PageModel::findFirstPublishedRegularByPid($pageModel->id);
                }

                // Hide the link if the target page is invisible
                if (!$jumpTo instanceof PageModel || (!$jumpTo->loadDetails()->isPublic && !$this->tokenChecker->hasBackendUser())) {
                    $menuItem->setDisplay(false);
                }

                try {
                    return $jumpTo->getFrontendUrl();
                } catch (ExceptionInterface $exception) {
                    $this->logger->log(LogLevel::ERROR, sprintf('Unable to generate URL for page ID %s: %s', $pageModel->id, $exception->getMessage()), ['contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]);

                    return null;
                }

            default:
                try {
                    return $pageModel->getFrontendUrl();
                } catch (ExceptionInterface $exception) {
                    $this->logger->log(LogLevel::ERROR, sprintf('Unable to generate URL for page ID %s: %s', $pageModel->id, $exception->getMessage()), ['contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]);

                    return null;
                }
        }
    }

    private function populateMenuItem(MenuItem $item, PageModel $requestPage, PageModel $page, ?string $href, array $options = []): void
    {
        $extra = $page->row();
        $trail = \in_array($page->id, $requestPage->trail, false);

        $item->setUri($href);

        // Use the path without query string to check for active pages (see #480)
        $path = current(explode('?', Environment::get('request'), 2));

        $hasSubmenu = $item->hasChildren() && $item->getDisplayChildren();

        if (
            $href === $path
            && !($options['isSitemap'] ?? false)
            && (($requestPage->id === $page->id) || ('forward' === $page->type && $requestPage->id === $page->jumpTo))
        ) {
            $extra['isActive'] = true;
            $extra['isTrail'] = false;

            $cssClass = implode('', [
                'forward' === $page->type && $requestPage->id === $page->jumpTo ? 'forward'.($trail ? ' trail' : '') : 'active',
                $hasSubmenu ? ' submenu' : '',
                $page->protected ? ' protected' : '',
                $page->cssClass ? ' '.$page->cssClass : '',
            ]);

            $item->setCurrent(true);
        } else {
            $cssClass = implode('', [
                $hasSubmenu ? 'submenu' : '',
                $page->protected ? ' protected' : '',
                $trail ? ' trail' : '',
                $page->cssClass ? ' '.$page->cssClass : '',
            ]);

            // Mark pages on the same level (see #2419)
            if ($page->pid === $requestPage->pid) {
                $cssClass .= ' sibling';
            }

            $extra['isActive'] = false;
            $extra['isTrail'] = $trail;
        }

        $extra['class'] = trim($cssClass);
        $extra['title'] = StringUtil::specialchars($page->title, true);
        $extra['pageTitle'] = StringUtil::specialchars($page->pageTitle, true);
        $extra['description'] = str_replace(["\n", "\r"], [' ', ''], (string) $page->description);

        $rel = [];

        if (0 === strncmp($page->robots, 'noindex,nofollow', 16)) {
            $rel[] = 'nofollow';
        }

        // Override the link target
        if ('redirect' === $page->type && $page->target) {
            $rel[] = 'noreferrer';
            $rel[] = 'noopener';

            $item->setLinkAttribute('target', '_blank');
        }

        // Set the rel attribute
        if (!empty($rel)) {
            $item->setLinkAttribute('rel', implode(' ', $rel));
        }

        if ($title = $page->pageTitle ?: $page->title) {
            $item->setLinkAttribute('title', $title);
        }

        if ($page->accesskey) {
            $item->setLinkAttribute('accesskey', $page->accesskey);
        }

        if ($page->tabindex) {
            $item->setLinkAttribute('tabindex', $page->tabindex);
        }

        foreach ($extra as $k => $v) {
            $item->setExtra($k, $v);
        }

        $item->setExtra('pageModel', $page);
    }
}
