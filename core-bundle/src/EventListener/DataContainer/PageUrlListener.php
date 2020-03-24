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
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\CoreBundle\Slug\Slug;
use Contao\DataContainer;
use Contao\Input;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\ServiceAnnotationBundle\ServiceAnnotationInterface;

class PageUrlListener implements ServiceAnnotationInterface, ResetInterface
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
        /** @var PageModel $page */
        $page = $this->getPageAdapter()->findWithDetails($dc->id);

        if ($value !== '') {
            try {
                $this->aliasExists($value, (int) $page->id, $page, true);
            } catch (DuplicateAliasException $exception) {
                throw new \RuntimeException(
                    $this->translator->trans('ERR.pageUrlExists', [$exception->getUrl()], 'contao_default'),
                    $exception->getCode(),
                    $exception
                );
            }

            return $value;
        }

        // Generate an alias if there is none
        $value = $this->slug->generate(
            $dc->activeRecord->title,
            $dc->activeRecord->id,
            function ($alias) use ($page) {
                return $this->aliasExists(($page->useFolderUrl ? $page->folderUrl : '').$alias, (int) $page->id, $page);
            }
        );

        // Generate folder URL aliases (see #4933)
        if ($page->useFolderUrl) {
            $value = $page->folderUrl.$value;
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

        $this->connection
            ->prepare('DELETE FROM tl_search_index WHERE pid IN (SELECT id FROM tl_search WHERE pid=:pageId)')
            ->execute(['pageId' => $dc->id])
        ;

        $this->connection
            ->prepare('DELETE FROM tl_search WHERE pid=:pageId')
            ->execute(['pageId' => $dc->id])
        ;

        return $value;
    }

    /**
     * @Callback(table="tl_page", target="fields.languagePrefix.save")
     */
    public function validateLanguagePrefix(string $value, DataContainer $dc): string
    {
        if ($dc->activeRecord->type !== 'root' || $dc->activeRecord->languagePrefix === $value) {
            return $value;
        }

        $rootPage = $this->getPageAdapter()->findByPk($dc->id);

        if (null === $rootPage) {
            return $value;
        }

        try {
            $this->recursiveValidatePages((int) $rootPage->id, $rootPage);
        } catch (DuplicateAliasException $exception) {
            throw new \RuntimeException(
                $this->translator->trans('ERR.pageUrlPrefix', [$exception->getUrl()], 'contao_default'),
                $exception->getCode(),
                $exception
            );
        }

        return $value;
    }

    /**
     * @Callback(table="tl_page", target="fields.urlSuffix.save")
     */
    public function validateUrlSuffix($value, DataContainer $dc)
    {
        if ($dc->activeRecord->type !== 'root' || $dc->activeRecord->urlSuffix === $value) {
            return $value;
        }

        $rootPage = $this->getPageAdapter()->findByPk($dc->id);

        if (null === $rootPage) {
            return $value;
        }

        try {
            $this->recursiveValidatePages((int) $rootPage->id, $rootPage);
        } catch (DuplicateAliasException $exception) {
            throw new \RuntimeException(
                $this->translator->trans('ERR.pageUrlSuffix', [$exception->getUrl()], 'contao_default')
            );
        }

        return $value;
    }

    public function reset()
    {
        $this->prefixes = null;
        $this->suffixes = null;
    }

    /**
     * @throws DuplicateAliasException
     */
    private function recursiveValidatePages(int $pid, PageModel $rootPage): void
    {
        $pages = $this->getPageAdapter()->findByPid($pid);

        if (null === $pages) {
            return;
        }

        /** @var PageModel $page */
        foreach ($pages as $page) {
            $this->aliasExists($page->alias, (int) $page->id, $rootPage, true);

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
        $currentPrefix = $currentPage->languagePrefix;
        $currentSuffix = $currentPage->urlSuffix;

        if ('root' === $currentPage->type) {
            /** @var Input $input */
            $input = $this->framework->getAdapter(Input::class);

            // TODO: this won't work in edit-all, in legacy mode or if user does not have access to these fields
            $currentDomain = $input->post('dns');
            $currentPrefix = $input->post('languagePrefix');
            $currentSuffix = $input->post('urlSuffix');
        }

        $aliasIds = $this->connection
            ->executeQuery(
                'SELECT id FROM tl_page WHERE alias LIKE :alias AND id!=:id',
                [
                    'alias' => '%'.$this->stripPrefixesAndSuffixes($currentAlias, $currentPrefix, $currentSuffix).'%',
                    'id' => $currentId,
                ]
            )->fetchAll(FetchMode::COLUMN)
        ;

        if (0 === count($aliasIds)) {
            return false;
        }

        $currentUrl = $this->buildUrl($currentAlias, $currentPrefix, $currentSuffix);

        foreach ($aliasIds as $aliasId) {
            $aliasPage = $this->getPageAdapter()->findWithDetails($aliasId);

            if (null === $aliasPage) {
                continue;
            }

            $aliasDomain = $aliasPage->domain;
            $aliasPrefix = $aliasPage->languagePrefix;
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

    private function buildUrl(string $alias, string $languagePrefix, string $urlSuffix): string
    {
        $url = '/'.$alias.$urlSuffix;

        if ($languagePrefix) {
            $url = '/'.$languagePrefix.$url;
        }

        return $url;
    }

    private function stripPrefixesAndSuffixes(string $alias, string $languagePrefix, string $urlSuffix): string
    {
        if (null === $this->prefixes || null === $this->suffixes) {
            $this->prefixes = [];
            $this->suffixes = [];

            $rows = $this->connection
                ->executeQuery("SELECT languagePrefix, urlSuffix FROM tl_page WHERE type='root'")
                ->fetchAll()
            ;

            if (0 === ($prefixLength = strlen($languagePrefix))) {
                $this->prefixes = array_column($rows, 'languagePrefix');
            } else {
                foreach (array_column($rows, 'languagePrefix') as $prefix) {
                    if (0 === substr_compare($prefix, $languagePrefix, 0, $prefixLength, true)) {
                        $prefix = trim(substr($prefix, $prefixLength), '/');

                        if ('' !== $prefix) {
                            $this->prefixes[] = $prefix.'/';
                        }
                    }
                }
            }

            if (0 === ($suffixLength = strlen($urlSuffix))) {
                $this->suffixes = array_column($rows, 'urlSuffix');
            } else {
                foreach (array_column($rows, 'urlSuffix') as $suffix) {
                    if (0 === substr_compare($suffix, $urlSuffix, -$suffixLength, null, true)) {
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

    private function regexArray(array $data, string $delimiter = '/'): ?string
    {
        $data = array_filter(array_unique($data));

        if (0 === count($data)) {
            return null;
        }

        foreach ($data as $k => $v) {
            $data[$k] = preg_quote($v, $delimiter);
        }

        return '('.implode('|', $data).')';
    }

    /**
     * @return Adapter&PageModel
     */
    private function getPageAdapter(): Adapter
    {
        return $this->framework->getAdapter(PageModel::class);
    }
}
