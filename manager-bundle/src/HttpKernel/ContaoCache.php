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
use Contao\CoreBundle\EventListener\HttpCache\StripQueryParametersSubscriber;
use FOS\HttpCache\SymfonyCache\CacheInvalidation;
use FOS\HttpCache\SymfonyCache\CleanupCacheTagsListener;
use FOS\HttpCache\SymfonyCache\EventDispatchingHttpCache;
use FOS\HttpCache\SymfonyCache\PurgeListener;
use FOS\HttpCache\SymfonyCache\PurgeTagsListener;
use FOS\HttpCache\TagHeaderFormatter\TagHeaderFormatter;
use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\TerminableInterface;
use Toflar\Psr6HttpCacheStore\Psr6Store;

class ContaoCache extends HttpCache implements CacheInvalidation
{
    use EventDispatchingHttpCache;

    public function __construct(ContaoKernel $kernel, string|null $cacheDir = null)
    {
        parent::__construct($kernel, $cacheDir);

        $stripCookies = new StripCookiesSubscriber($this->readEnvCsv('COOKIE_ALLOW_LIST'));
        $stripCookies->removeFromDenyList($this->readEnvCsv('COOKIE_REMOVE_FROM_DENY_LIST'));

        $stripQueryParams = new StripQueryParametersSubscriber($this->readEnvCsv('QUERY_PARAMS_ALLOW_LIST'));
        $stripQueryParams->removeFromDenyList($this->readEnvCsv('QUERY_PARAMS_REMOVE_FROM_DENY_LIST'));

        $this->addSubscriber($stripCookies);
        $this->addSubscriber($stripQueryParams);
        $this->addSubscriber(new PurgeListener());
        $this->addSubscriber(new PurgeTagsListener());
        $this->addSubscriber(new CleanupCacheTagsListener());
    }

    /**
     * Overwrites the getEventDispatcher() method of the EventDispatchingHttpCache
     * trait, so the LegacyEventDispatcherProxy is not used. Once we have upgraded
     * to Symfony 6, the method can be removed again.
     */
    public function getEventDispatcher(): EventDispatcher
    {
        return $this->eventDispatcher ??= new EventDispatcher();
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

        $kernel = $this->getKernel();

        if ($kernel instanceof TerminableInterface) {
            $kernel->terminate($request, $response);
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
        $cacheDir = $this->cacheDir ?: Path::join($this->kernel->getCacheDir(), 'http_cache');

        return new Psr6Store([
            'cache_directory' => $cacheDir,
            'cache' => new TagAwareAdapter(new FilesystemAdapter('', 0, $cacheDir)),
            'cache_tags_header' => TagHeaderFormatter::DEFAULT_HEADER_NAME,
            'prune_threshold' => 5000,
        ]);
    }

    private function readEnvCsv(string $key): array
    {
        return array_filter(explode(',', (string) ($_SERVER[$key] ?? '')));
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
