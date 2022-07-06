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
use FOS\HttpCache\SymfonyCache\CacheInvalidation;
use FOS\HttpCache\SymfonyCache\CleanupCacheTagsListener;
use FOS\HttpCache\SymfonyCache\EventDispatchingHttpCache;
use FOS\HttpCache\SymfonyCache\PurgeListener;
use FOS\HttpCache\SymfonyCache\PurgeTagsListener;
use FOS\HttpCache\TagHeaderFormatter\TagHeaderFormatter;
use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\TerminableInterface;
use Toflar\Psr6HttpCacheStore\Psr6Store;

class ContaoCache extends HttpCache implements CacheInvalidation
{
    use EventDispatchingHttpCache;

    public function __construct(ContaoKernel $kernel, string $cacheDir = null)
    {
        parent::__construct($kernel, $cacheDir);

        $whitelist = array_filter(explode(',', $_SERVER['COOKIE_WHITELIST'] ?? ''));

        $this->addSubscriber(new StripCookiesSubscriber($whitelist));
        $this->addSubscriber(new PurgeListener());
        $this->addSubscriber(new PurgeTagsListener());
        $this->addSubscriber(new CleanupCacheTagsListener());
    }

    public function fetch(Request $request, $catch = false): Response
    {
        return parent::fetch($request, $catch);
    }

    /**
     * Override default terminate method in order to never call the
     * "kernel.terminate" event on cache hit.
     *
     * @todo Remove once symfony/http-kernel is required in at least ^6.2
     */
    public function terminate(Request $request, Response $response): void
    {
        $traces = $this->getTraces();

        if (\in_array('fresh', $traces[$this->getTraceKey($request)] ?? [], true)) {
            return;
        }

        if ($this->getKernel() instanceof TerminableInterface) {
            $this->getKernel()->terminate($request, $response);
        }
    }

    protected function getOptions(): array
    {
        $options = parent::getOptions();

        $options['trace_level'] = $_SERVER['TRACE_LEVEL'] ?? 'short';
        $options['trace_header'] = 'Contao-Cache';

        return $options;
    }

    protected function createStore(): Psr6Store
    {
        $cacheDir = $this->cacheDir ?: $this->kernel->getCacheDir().'/http_cache';

        return new Psr6Store([
            'cache_directory' => $cacheDir,
            'cache' => new TagAwareAdapter(new FilesystemAdapter('', 0, $cacheDir)),
            'cache_tags_header' => TagHeaderFormatter::DEFAULT_HEADER_NAME,
            'prune_threshold' => 5000,
        ]);
    }

    /**
     * Unfortunately, we need to copy this from the parent as it is private.
     *
     * @todo Remove once symfony/http-kernel is required in at least ^6.2
     */
    private function getTraceKey(Request $request): string
    {
        $path = $request->getPathInfo();

        if ($qs = $request->getQueryString()) {
            $path .= '?'.$qs;
        }

        return $request->getMethod().' '.$path;
    }
}
