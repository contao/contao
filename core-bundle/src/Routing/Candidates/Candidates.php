<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\Candidates;

use Contao\CoreBundle\Routing\Content\PageProviderInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Symfony\Cmf\Component\Routing\Candidates\CandidatesInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;

class Candidates implements CandidatesInterface
{
    /**
     * A limit to apply to the number of candidates generated.
     *
     * This is to prevent abusive requests with a lot of "/". The limit is per
     * batch, that is if a locale matches you could get as many as 2 * $limit
     * candidates if the URL has that many slashes.
     *
     * @var int
     */
    private const LIMIT = 20;

    /**
     * @var Connection
     */
    private $database;

    /**
     * @var bool
     */
    private $prependLocale;

    /**
     * @var bool
     */
    private $legacyRouting;

    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * @var array
     */
    private $languagePrefix;

    /**
     * @var array
     */
    private $urlSuffix;

    /**
     * @var ServiceLocator
     */
    private $pageProviders;

    public function __construct(Connection $database, ServiceLocator $pageProviders, bool $legacyRouting, string $urlSuffix, bool $prependLocale)
    {
        $this->database = $database;
        $this->pageProviders = $pageProviders;
        $this->legacyRouting = $legacyRouting;
        $this->prependLocale = $prependLocale;

        $this->languagePrefix = [];
        $this->urlSuffix = [$urlSuffix];
    }

    public function isCandidate($name)
    {
        return strncmp($name, 'tl_page.', 8);
    }

    public function restrictQuery($queryBuilder): void
    {
    }

    public function getCandidates(Request $request)
    {
        $this->initialize();

        $url = $request->getPathInfo();
        $url = rawurldecode(ltrim($url, '/'));
        $candidates = [];

        if ($this->legacyRouting) {
            $url = $this->removeSuffixAndLanguage($url);

            if (null === $url) {
                return [];
            }

            $this->addCandidatesFor($url, $candidates);

            return array_values(array_unique($candidates));
        }

        $this->addCandidatesFor($url, $candidates);

        foreach ($this->urlSuffix as $suffix) {
            $this->addUrlWithoutSuffix($url, $suffix, $candidates);
        }

        foreach ($this->languagePrefix as $prefix) {
            if (0 !== strncmp($url, $prefix.'/', \strlen($prefix) + 1)) {
                continue;
            }

            $for = substr($url, \strlen($prefix) + 1);
            $this->addCandidatesFor($for, $candidates);

            foreach ($this->urlSuffix as $suffix) {
                $this->addUrlWithoutSuffix($for, $suffix, $candidates);
            }
        }

        return array_values(array_unique($candidates));
    }

    protected function addCandidatesFor(string $url, array &$candidates): void
    {
        if ('' === $url) {
            $candidates[] = 'index';

            return;
        }

        $part = $url;
        $count = 0;

        while (false !== ($pos = strrpos($part, '/'))) {
            if (++$count > self::LIMIT) {
                return;
            }
            $candidates[] = $part;
            $part = substr($url, 0, $pos);
        }

        $candidates[] = $part;
    }

    private function addUrlWithoutSuffix(string $url, string $suffix, array &$candidates): void
    {
        $offset = -\strlen($suffix);

        if (0 === substr_compare($url, $suffix, $offset)) {
            $candidates[] = substr($url, 0, $offset);
        }
    }

    private function removeSuffixAndLanguage(string $pathInfo): ?string
    {
        $suffixLength = \strlen($this->urlSuffix[0]);

        if (0 !== $suffixLength) {
            if (substr($pathInfo, -$suffixLength) !== $this->urlSuffix[0]) {
                return null;
            }

            $pathInfo = substr($pathInfo, 0, -$suffixLength);
        }

        if ($this->prependLocale) {
            $matches = [];

            if (!preg_match('@^([a-z]{2}(-[A-Z]{2})?)/(.+)$@', $pathInfo, $matches)) {
                return null;
            }

            $pathInfo = $matches[3];
        }

        return $pathInfo;
    }

    private function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->initialized = true;

        if ($this->legacyRouting) {
            return;
        }

        $languagePrefix = $this->database
            ->query("SELECT DISTINCT languagePrefix FROM tl_page WHERE type='root'")
            ->fetchAll(FetchMode::COLUMN)
        ;

        $urlSuffix = [];

        foreach ($this->pageProviders->getProvidedServices() as $type => $v) {
            /** @var PageProviderInterface $provider */
            $provider = $this->pageProviders->get($type);
            $urlSuffix[] = $provider->getUrlSuffixes();
        }

        $this->urlSuffix = array_filter(array_unique(array_merge(...$urlSuffix)));
        $this->languagePrefix = array_filter($languagePrefix);
    }
}
