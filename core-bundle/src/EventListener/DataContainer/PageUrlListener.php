<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Exception\DuplicateAliasException;
use Contao\CoreBundle\Exception\RouteParametersException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Slug\Slug;
use Contao\DataContainer;
use Contao\Input;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Symfony\Cmf\Component\Routing\NestedMatcher\FinalMatcherInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class PageUrlListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Slug $slug,
        private readonly TranslatorInterface $translator,
        private readonly Connection $connection,
        private readonly PageRegistry $pageRegistry,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly FinalMatcherInterface $routeMatcher,
    ) {
    }

    #[AsCallback(table: 'tl_page', target: 'fields.alias.save')]
    public function generateAlias(string $value, DataContainer $dc): string
    {
        $pageAdapter = $this->framework->getAdapter(PageModel::class);
        $pageModel = $pageAdapter->findWithDetails($dc->id);

        if (!$pageModel instanceof PageModel) {
            return $value;
        }

        $this->addInputToPage($pageModel);
        $isRoutable = $this->pageRegistry->isRoutable($pageModel);

        if ('' !== $value) {
            if (preg_match('/^[1-9]\d*$/', $value)) {
                throw new \RuntimeException($this->translator->trans('ERR.aliasNumeric', [], 'contao_default'));
            }

            if ($isRoutable) {
                try {
                    $this->aliasExists($value, $pageModel, true);
                } catch (DuplicateAliasException $exception) {
                    if ($pageModel = $exception->getPageModel()) {
                        throw new \RuntimeException($this->translator->trans('ERR.pageUrlNameExists', [$pageModel->title, $pageModel->id], 'contao_default'), $exception->getCode(), $exception);
                    }

                    throw new \RuntimeException($this->translator->trans('ERR.pageUrlExists', [$exception->getUrl()], 'contao_default'), $exception->getCode(), $exception);
                }
            }

            return $value;
        }

        // Generate an alias if there is none
        $value = $this->slug->generate(
            $pageModel->title ?? '',
            (int) $dc->id,
            fn ($alias) => $isRoutable && $this->aliasExists(($pageModel->useFolderUrl ? $pageModel->folderUrl : '').$alias, $pageModel)
        );

        // Generate folder URL aliases (see #4933)
        if ($pageModel->useFolderUrl) {
            $value = $pageModel->folderUrl.$value;
        }

        return $value;
    }

    #[AsCallback(table: 'tl_page', target: 'fields.urlPrefix.save')]
    public function validateUrlPrefix(string $value, DataContainer $dc): string
    {
        $currentRecord = $dc->getCurrentRecord();

        if ('root' !== ($currentRecord['type'] ?? null) || ($currentRecord['urlPrefix'] ?? null) === $value) {
            return $value;
        }

        // First check if another root page uses the same url prefix and domain
        $count = $this->connection->fetchOne(
            "SELECT COUNT(*) FROM tl_page WHERE urlPrefix=:urlPrefix AND dns=:dns AND id!=:rootId AND type='root'",
            [
                'urlPrefix' => $value,
                'dns' => $currentRecord['dns'] ?? null,
                'rootId' => $dc->id,
            ]
        );

        if ($count > 0) {
            throw new \RuntimeException($this->translator->trans('ERR.urlPrefixExists', [$value], 'contao_default'));
        }

        $pageAdapter = $this->framework->getAdapter(PageModel::class);
        $rootPage = $pageAdapter->findWithDetails($dc->id);

        if (!$rootPage instanceof PageModel) {
            return $value;
        }

        $this->addInputToPage($rootPage);

        try {
            $this->recursiveValidatePages((int) $rootPage->id, $rootPage);
        } catch (DuplicateAliasException $exception) {
            throw new \RuntimeException($this->translator->trans('ERR.pageUrlPrefix', [$exception->getUrl()], 'contao_default'), $exception->getCode(), $exception);
        }

        return $value;
    }

    #[AsCallback(table: 'tl_page', target: 'fields.urlSuffix.save')]
    public function validateUrlSuffix(mixed $value, DataContainer $dc): mixed
    {
        $currentRecord = $dc->getCurrentRecord();

        if ('root' !== ($currentRecord['type'] ?? null) || ($currentRecord['urlSuffix'] ?? null) === $value) {
            return $value;
        }

        $pageAdapter = $this->framework->getAdapter(PageModel::class);
        $rootPage = $pageAdapter->findWithDetails($dc->id);

        if (!$rootPage instanceof PageModel) {
            return $value;
        }

        $this->addInputToPage($rootPage);

        try {
            $this->recursiveValidatePages((int) $rootPage->id, $rootPage);
        } catch (DuplicateAliasException $exception) {
            throw new \RuntimeException($this->translator->trans('ERR.pageUrlSuffix', [$exception->getUrl()], 'contao_default'), 0, $exception);
        }

        return $value;
    }

    private function recursiveValidatePages(int $pid, PageModel $rootPage): void
    {
        $pageAdapter = $this->framework->getAdapter(PageModel::class);
        $pages = $pageAdapter->findByPid($pid);

        if (null === $pages) {
            return;
        }

        foreach ($pages as $page) {
            if ($page->alias && $this->pageRegistry->isRoutable($page)) {
                // Inherit root page settings from post data
                $page->loadDetails();
                $page->domain = $rootPage->domain;
                $page->urlPrefix = $rootPage->urlPrefix;
                $page->urlSuffix = $rootPage->urlSuffix;

                $this->aliasExists($page->alias, $page, true);
            }

            $this->recursiveValidatePages((int) $page->id, $rootPage);
        }
    }

    /**
     * @throws DuplicateAliasException
     */
    private function aliasExists(string $currentAlias, PageModel $currentPage, bool $throw = false): bool
    {
        // We can safely modify the page model since loadDetails() detaches it
        // from the registry and calls preventSaving()
        $currentPage->loadDetails();
        $currentPage->alias = $currentAlias;

        // Route must be created again from PageModel because alias changed
        $currentRoute = $this->pageRegistry->getRoute($currentPage);

        try {
            $currentUrl = $this->urlGenerator->generate(
                PageRoute::PAGE_BASED_ROUTE_NAME,
                [RouteObjectInterface::ROUTE_OBJECT => $currentRoute],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        } catch (RouteParametersException) {
            // This route has mandatory parameters, only match exact path with placeholders
            $currentUrl = null;
        }

        $aliasPages = $this->framework->getAdapter(PageModel::class)->findSimilarByAlias($currentPage);

        if (null === $aliasPages) {
            return false;
        }

        $routeCollection = new RouteCollection();

        foreach ($aliasPages as $aliasPage) {
            if (!$this->pageRegistry->isRoutable($aliasPage)) {
                continue;
            }

            // If page has the same root, inherit root page settings from post data
            if ($currentPage->rootId === $aliasPage->rootId) {
                $aliasPage->loadDetails();
                $aliasPage->domain = $currentPage->domain;
                $aliasPage->urlPrefix = $currentPage->urlPrefix;
                $aliasPage->urlSuffix = $currentPage->urlSuffix;
            }

            $aliasRoute = $this->pageRegistry->getRoute($aliasPage);

            // Even if we cannot generate the path because of parameter requirements,
            // two pages can never have the same path AND the same requirements. This
            // could be two regular pages with same alias and "requireItem" enabled.
            if (
                null === $currentUrl
                && $currentRoute->getPath() === $aliasRoute->getPath()
                && $currentRoute->getHost() === $aliasRoute->getHost()
                && 0 === ($currentRoute->getRequirements() <=> $aliasRoute->getRequirements())
            ) {
                if ($throw) {
                    $exception = new DuplicateAliasException($currentRoute->getPath());
                    $exception->setPageModel($aliasPage);

                    throw $exception;
                }

                return true;
            }

            $routeCollection->add('tl_page.'.$aliasPage->id, $aliasRoute);
        }

        if (null === $currentUrl || 0 === $routeCollection->count()) {
            return false;
        }

        $request = Request::create($currentUrl);

        try {
            $attributes = $this->routeMatcher->finalMatch($routeCollection, $request);
        } catch (ResourceNotFoundException) {
            return false;
        }

        if ($throw) {
            $exception = new DuplicateAliasException($currentUrl);

            if ($attributes['pageModel'] instanceof PageModel) {
                $exception->setPageModel($attributes['pageModel']);
            }

            throw $exception;
        }

        return true;
    }

    private function addInputToPage(PageModel $pageModel): void
    {
        $input = $this->framework->getAdapter(Input::class);

        if (null !== ($type = $input->post('type'))) {
            $pageModel->type = $type;
        }

        if (null !== ($title = $input->post('title'))) {
            $pageModel->title = $title;
        }

        if (null !== ($requireItem = $input->post('requireItem'))) {
            $pageModel->requireItem = (bool) $requireItem;
        }

        if ('root' === $pageModel->type) {
            if (null !== ($dns = $input->post('dns'))) {
                $pageModel->domain = $dns;
            }

            if (null !== ($urlPrefix = $input->post('urlPrefix'))) {
                $pageModel->urlPrefix = $urlPrefix;
            }

            if (null !== ($urlSuffix = $input->post('urlSuffix'))) {
                $pageModel->urlSuffix = $urlSuffix;
            }
        }
    }
}
