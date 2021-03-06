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

use Contao\CoreBundle\Exception\DuplicateAliasException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\CoreBundle\Slug\Slug;
use Contao\DataContainer;
use Contao\Input;
use Contao\PageModel;
use Contao\Search;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageUrlListener implements ResetInterface
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var Slug
     */
    private $slug;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var array|null
     */
    private $prefixes;

    /**
     * @var array|null
     */
    private $suffixes;

    public function __construct(ContaoFramework $framework, Slug $slug, TranslatorInterface $translator, Connection $connection)
    {
        $this->framework = $framework;
        $this->slug = $slug;
        $this->translator = $translator;
        $this->connection = $connection;
    }

    /**
     * @Callback(table="tl_page", target="fields.alias.save")
     */
    public function generateAlias(string $value, DataContainer $dc): string
    {
        /** @var PageModel $pageAdapter */
        $pageAdapter = $this->framework->getAdapter(PageModel::class);

        /** @var PageModel $pageModel */
        $pageModel = $pageAdapter->findWithDetails($dc->id);

        if ('' !== $value) {
            if (preg_match('/^[1-9]\d*$/', $value)) {
                throw new \RuntimeException($this->translator->trans('ERR.aliasNumeric', [], 'contao_default'));
            }

            try {
                $this->aliasExists($value, (int) $pageModel->id, $pageModel, true);
            } catch (DuplicateAliasException $exception) {
                throw new \RuntimeException($this->translator->trans('ERR.pageUrlExists', [$exception->getUrl()], 'contao_default'), $exception->getCode(), $exception);
            }

            return $value;
        }

        // Generate an alias if there is none
        $value = $this->slug->generate(
            $dc->activeRecord->title,
            $dc->activeRecord->id,
            function ($alias) use ($pageModel) {
                return $this->aliasExists(($pageModel->useFolderUrl ? $pageModel->folderUrl : '').$alias, (int) $pageModel->id, $pageModel);
            }
        );

        // Generate folder URL aliases (see #4933)
        if ($pageModel->useFolderUrl) {
            $value = $pageModel->folderUrl.$value;
        }

        return $value;
    }

    /**
     * @Callback(table="tl_page", target="fields.alias.save")
     */
    public function purgeSearchIndexOnAliasChange(string $value, DataContainer $dc): string
    {
        if ($value === $dc->activeRecord->alias) {
            return $value;
        }

        $this->purgeSearchIndex((int) $dc->id);

        return $value;
    }

    /**
     * @Callback(table="tl_page", target="config.ondelete", priority=16)
     */
    public function purgeSearchIndexOnDelete(DataContainer $dc): void
    {
        if (!$dc->id) {
            return;
        }

        $this->purgeSearchIndex((int) $dc->id);
    }

    /**
     * @Callback(table="tl_page", target="fields.urlPrefix.save")
     */
    public function validateUrlPrefix(string $value, DataContainer $dc): string
    {
        if ('root' !== $dc->activeRecord->type || $dc->activeRecord->urlPrefix === $value) {
            return $value;
        }

        // First check if another root page uses the same url prefix and domain
        $count = $this->connection->fetchOne(
            "SELECT COUNT(*) FROM tl_page WHERE urlPrefix=:urlPrefix AND dns=:dns AND id!=:rootId AND type='root'",
            [
                'urlPrefix' => $value,
                'dns' => $dc->activeRecord->dns,
                'rootId' => $dc->id,
            ]
        );

        if ($count > 0) {
            throw new \RuntimeException($this->translator->trans('ERR.urlPrefixExists', [$value], 'contao_default'));
        }

        /** @var PageModel $pageAdapter */
        $pageAdapter = $this->framework->getAdapter(PageModel::class);
        $rootPage = $pageAdapter->findByPk($dc->id);

        if (null === $rootPage) {
            return $value;
        }

        try {
            $this->recursiveValidatePages((int) $rootPage->id, $rootPage);
        } catch (DuplicateAliasException $exception) {
            throw new \RuntimeException($this->translator->trans('ERR.pageUrlPrefix', [$exception->getUrl()], 'contao_default'), $exception->getCode(), $exception);
        }

        return $value;
    }

    /**
     * @Callback(table="tl_page", target="fields.urlSuffix.save")
     */
    public function validateUrlSuffix($value, DataContainer $dc)
    {
        if ('root' !== $dc->activeRecord->type || $dc->activeRecord->urlSuffix === $value) {
            return $value;
        }

        /** @var PageModel $pageAdapter */
        $pageAdapter = $this->framework->getAdapter(PageModel::class);
        $rootPage = $pageAdapter->findByPk($dc->id);

        if (null === $rootPage) {
            return $value;
        }

        try {
            $this->recursiveValidatePages((int) $rootPage->id, $rootPage);
        } catch (DuplicateAliasException $exception) {
            throw new \RuntimeException($this->translator->trans('ERR.pageUrlSuffix', [$exception->getUrl()], 'contao_default'), 0, $exception);
        }

        return $value;
    }

    public function reset(): void
    {
        $this->prefixes = null;
        $this->suffixes = null;
    }

    public function purgeSearchIndex(int $pageId): void
    {
        $urls = $this->connection->fetchFirstColumn(
            'SELECT url FROM tl_search WHERE pid=:pageId',
            ['pageId' => $pageId]
        );

        /** @var Search $search */
        $search = $this->framework->getAdapter(Search::class);

        foreach ($urls as $url) {
            $search->removeEntry($url);
        }
    }

    private function recursiveValidatePages(int $pid, PageModel $rootPage): void
    {
        /** @var PageModel $pageAdapter */
        $pageAdapter = $this->framework->getAdapter(PageModel::class);
        $pages = $pageAdapter->findByPid($pid);

        if (null === $pages) {
            return;
        }

        /** @var PageModel $page */
        foreach ($pages as $page) {
            if ($page->alias) {
                $this->aliasExists($page->alias, (int) $page->id, $rootPage, true);
            }

            $this->recursiveValidatePages((int) $page->id, $rootPage);
        }
    }

    /**
     * @throws DuplicateAliasException
     */
    private function aliasExists(string $currentAlias, int $currentId, PageModel $currentPage, bool $throw = false): bool
    {
        $currentPage->loadDetails();

        $currentDomain = $currentPage->domain;
        $currentPrefix = $currentPage->urlPrefix;
        $currentSuffix = $currentPage->urlSuffix;

        if ('root' === $currentPage->type) {
            /** @var Input $input */
            $input = $this->framework->getAdapter(Input::class);

            if (null !== ($dns = $input->post('dns'))) {
                $currentDomain = $dns;
            }

            if (null !== ($urlPrefix = $input->post('urlPrefix'))) {
                $currentPrefix = $urlPrefix;
            }

            if (null !== ($urlSuffix = $input->post('urlSuffix'))) {
                $currentSuffix = $urlSuffix;
            }
        }

        $aliasIds = $this->connection->fetchFirstColumn(
            'SELECT id FROM tl_page WHERE alias LIKE :alias AND id!=:id',
            [
                'alias' => '%'.$this->stripPrefixesAndSuffixes($currentAlias, $currentPrefix, $currentSuffix).'%',
                'id' => $currentId,
            ]
        );

        if (0 === \count($aliasIds)) {
            return false;
        }

        /** @var PageModel $pageAdapter */
        $pageAdapter = $this->framework->getAdapter(PageModel::class);
        $currentUrl = $this->buildUrl($currentAlias, $currentPrefix, $currentSuffix);

        foreach ($aliasIds as $aliasId) {
            $aliasPage = $pageAdapter->findWithDetails($aliasId);

            if (null === $aliasPage) {
                continue;
            }

            $aliasDomain = $aliasPage->domain;
            $aliasPrefix = $aliasPage->urlPrefix;
            $aliasSuffix = $aliasPage->urlSuffix;

            if ($currentPage->rootId === $aliasPage->rootId) {
                $aliasDomain = $currentDomain;
                $aliasPrefix = $currentPrefix;
                $aliasSuffix = $currentSuffix;
            }

            $aliasUrl = $this->buildUrl($aliasPage->alias, $aliasPrefix, $aliasSuffix);

            if ($currentDomain !== $aliasDomain || $currentUrl !== $aliasUrl) {
                continue;
            }

            // Duplicate alias found
            if ($throw) {
                throw new DuplicateAliasException($currentUrl);
            }

            return true;
        }

        return false;
    }

    private function buildUrl(string $alias, string $urlPrefix, string $urlSuffix): string
    {
        $url = '/'.$alias.$urlSuffix;

        if ($urlPrefix) {
            $url = '/'.$urlPrefix.$url;
        }

        return $url;
    }

    private function stripPrefixesAndSuffixes(string $alias, string $urlPrefix, string $urlSuffix): string
    {
        if (null === $this->prefixes || null === $this->suffixes) {
            $this->prefixes = [];
            $this->suffixes = [];

            $rows = $this->connection->fetchAllAssociative("SELECT urlPrefix, urlSuffix FROM tl_page WHERE type='root'");

            if (0 === ($prefixLength = \strlen($urlPrefix))) {
                $this->prefixes = array_column($rows, 'urlPrefix');
            } else {
                foreach (array_column($rows, 'urlPrefix') as $prefix) {
                    if (0 === substr_compare($prefix, $urlPrefix, 0, $prefixLength, true)) {
                        $prefix = trim(substr($prefix, $prefixLength), '/');

                        if ('' !== $prefix) {
                            $this->prefixes[] = $prefix.'/';
                        }
                    }
                }
            }

            if (0 === ($suffixLength = \strlen($urlSuffix))) {
                $this->suffixes = array_column($rows, 'urlSuffix');
            } else {
                foreach (array_column($rows, 'urlSuffix') as $suffix) {
                    if (0 === substr_compare($suffix, $urlSuffix, -$suffixLength, $suffixLength, true)) {
                        $this->suffixes[] = substr($suffix, 0, -$suffixLength);
                    }
                }
            }
        }

        if (null !== ($prefixRegex = $this->regexArray($this->prefixes))) {
            $alias = preg_replace('/^'.$prefixRegex.'/i', '', $alias);
        }

        if (null !== ($suffixRegex = $this->regexArray($this->suffixes))) {
            $alias = preg_replace('/'.$suffixRegex.'$/i', '', $alias);
        }

        return $alias;
    }

    private function regexArray(array $data): ?string
    {
        $data = array_filter(array_unique($data));

        if (0 === \count($data)) {
            return null;
        }

        foreach ($data as $k => $v) {
            $data[$k] = preg_quote($v, '/');
        }

        return '('.implode('|', $data).')';
    }
}
