<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\HttpKernel;

use FOS\HttpCache\SymfonyCache\CacheInvalidation;
use FOS\HttpCache\SymfonyCache\EventDispatchingHttpCache;
use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Terminal42\HeaderReplay\SymfonyCache\HeaderReplaySubscriber;

/**
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Yanick Witschi <https://github.com/toflar>
 */
class ContaoCache extends HttpCache implements CacheInvalidation
{
    use EventDispatchingHttpCache;

    /**
     * Constructor.
     *
     * @param KernelInterface $kernel
     * @param null            $cacheDir
     *
     * @todo Maybe provide a contao manager plugin?
     */
    public function __construct(KernelInterface $kernel, $cacheDir = null)
    {
        parent::__construct($kernel, $cacheDir);

        $this->addSubscriber(new HeaderReplaySubscriber());
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(Request $request, $catch = false)
    {
        return parent::fetch($request, $catch);
    }

    /**
     * {@inheritdoc}
     */
    protected function createStore(): Psr6Store
    {
        return new Psr6Store([
            'cache_directory' => $this->cacheDir ?: $this->kernel->getCacheDir().'/http_cache',
            'cache_tags_header' => TagHeaderFormatter::DEFAULT_HEADER_NAME,
        ]);
    }
}
