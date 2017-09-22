<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\HttpKernel;

use FOS\HttpCache\SymfonyCache\CacheInvalidation;
use FOS\HttpCache\SymfonyCache\EventDispatchingHttpCache;
use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Terminal42\HeaderReplay\SymfonyCache\HeaderReplaySubscriber;

class ContaoCache extends HttpCache implements CacheInvalidation
{
    use EventDispatchingHttpCache;

    /**
     * Constructor.
     *
     * @param HttpKernelInterface $kernel
     * @param string|null         $cacheDir
     */
    public function __construct(HttpKernelInterface $kernel, string $cacheDir = null)
    {
        parent::__construct($kernel, $cacheDir);

        $this->addSubscriber(new HeaderReplaySubscriber());
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
        return ['allow_reload' => true];
    }
}
