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

use Contao\ManagerPlugin\HttpCache\FOSHttpCacheSubscriberPluginInterface;
use FOS\HttpCache\SymfonyCache\CacheInvalidation;
use FOS\HttpCache\TagHeaderFormatter\TagHeaderFormatter;
use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Terminal42\HeaderReplay\SymfonyCache\HeaderReplaySubscriber;
use Toflar\Psr6HttpCacheStore\Psr6Store;

class ContaoCache extends HttpCache implements CacheInvalidation
{
    use EventDispatchingHttpCache;

    public function __construct(ContaoKernel $kernel, string $cacheDir = null)
    {
        parent::__construct($kernel, $cacheDir);

        /** @var FOSHttpCacheSubscriberPluginInterface $plugin */
        foreach ($kernel->getPluginLoader()->getInstancesOf(FOSHttpCacheSubscriberPluginInterface::class, true) as $plugin) {
            foreach ($plugin->getSubscribers() as $subscriber) {
                $this->addSubscriber($subscriber);
            }
        }

        $kernel->setHttpCache($this);
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
}
