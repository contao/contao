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

use FOS\HttpCache\SymfonyCache\CacheInvalidation;
use FOS\HttpCache\SymfonyCache\CleanupCacheTagsListener;
use FOS\HttpCache\SymfonyCache\EventDispatchingHttpCache;
use FOS\HttpCache\SymfonyCache\PurgeListener;
use FOS\HttpCache\SymfonyCache\PurgeTagsListener;
use FOS\HttpCache\TagHeaderFormatter\TagHeaderFormatter;
use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Toflar\Psr6HttpCacheStore\Psr6Store;

class ContaoCache extends HttpCache implements CacheInvalidation
{
    use EventDispatchingHttpCache;

    /**
     * @var bool
     */
    private $wasBypassed;

    public function __construct(ContaoKernel $kernel, string $cacheDir = null)
    {
        parent::__construct($kernel, $cacheDir);

        $this->addSubscriber(new PurgeListener());
        $this->addSubscriber(new PurgeTagsListener());
        $this->addSubscriber(new CleanupCacheTagsListener());
    }

    public function wasBypassed(): bool
    {
        return $this->wasBypassed;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true): Response
    {
        // Never cache private requests
        if ($this->isRequestPrivate($request)) {
            $this->wasBypassed = true;

            return $this->kernel->handle($request, $type, $catch);
        }

        $this->wasBypassed = false;

        return parent::handle($request, $type, $catch);
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
    protected function createStore()
    {
        return new Psr6Store([
            'cache_directory' => $this->cacheDir ?: $this->kernel->getCacheDir().'/http_cache',
            'cache_tags_header' => TagHeaderFormatter::DEFAULT_HEADER_NAME,
        ]);
    }

    /**
     * Checks if the Request includes authorization or other sensitive information
     * that should cause the cache to be bypassed.
     */
    private function isRequestPrivate(Request $request): bool
    {
        if ($request->headers->has('Authorization')) {
            return true;
        }

        if (\count($request->cookies->all())) {
            return true;
        }

        return false;
    }
}
