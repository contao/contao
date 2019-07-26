<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\HttpKernel;

use Contao\CoreBundle\EventListener\HttpCache\StripCookiesSubscriber;
use Doctrine\DBAL\DriverManager;
use FOS\HttpCache\SymfonyCache\CacheInvalidation;
use FOS\HttpCache\SymfonyCache\CleanupCacheTagsListener;
use FOS\HttpCache\SymfonyCache\EventDispatchingHttpCache;
use FOS\HttpCache\SymfonyCache\PurgeListener;
use FOS\HttpCache\SymfonyCache\PurgeTagsListener;
use FOS\HttpCache\TagHeaderFormatter\TagHeaderFormatter;
use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PdoAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Toflar\Psr6HttpCacheStore\Psr6Store;

class ContaoCache extends HttpCache implements CacheInvalidation
{
    use EventDispatchingHttpCache;

    /**
     * @var bool
     */
    private $isDebug;

    public function __construct(ContaoKernel $kernel, string $cacheDir = null)
    {
        parent::__construct($kernel, $cacheDir);

        $this->isDebug = $kernel->isDebug();

        $this->addSubscriber(new StripCookiesSubscriber(array_filter(explode(',', getenv('COOKIE_WHITELIST') ?: ''))));
        $this->addSubscriber(new PurgeListener());
        $this->addSubscriber(new PurgeTagsListener());
        $this->addSubscriber(new CleanupCacheTagsListener());
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(Request $request, $catch = false): Response
    {
        return parent::fetch($request, $catch);
    }

    /**
     * {@inheritdoc}
     */
    protected function getOptions(): array
    {
        $options = parent::getOptions();

        // Only works as of Symfony 4.3+
        $options['trace_level'] = $this->isDebug ? 'full' : 'short';
        $options['trace_header'] = 'Contao-Cache';

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    protected function createStore(): Psr6Store
    {
        $cacheDir = $this->cacheDir ?: $this->kernel->getCacheDir().'/http_cache';

        if (PhpFilesAdapter::isSupported()) {
            $itemsCache = new PhpFilesAdapter('', 0, $cacheDir);
        } else {
            $itemsCache = new FilesystemAdapter('', 0, $cacheDir);
        }

        $tagsCache = $itemsCache;

        if (class_exists(\SQLite3::class)) {
            $tagsCache =  new PdoAdapter(
                DriverManager::getConnection(['url' => 'sqlite:///'.$cacheDir.'/tag_versions.sqlite'])
            );
        }

        return new Psr6Store([
            'cache_directory' => $cacheDir,
            'cache' => new TagAwareAdapter($itemsCache, $tagsCache),
            'cache_tags_header' => TagHeaderFormatter::DEFAULT_HEADER_NAME,
            'prune_threshold' => 5000,
        ]);
    }
}
