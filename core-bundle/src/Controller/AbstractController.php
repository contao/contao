<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use FOS\HttpCacheBundle\Http\SymfonyResponseTagger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;
use Terminal42\ServiceAnnotationBundle\ServiceAnnotationInterface;

abstract class AbstractController extends SymfonyAbstractController implements ServiceAnnotationInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        $services = parent::getSubscribedServices();

        $services['contao.framework'] = ContaoFramework::class;
        $services['fos_http_cache.http.symfony_response_tagger'] = '?'.SymfonyResponseTagger::class;

        return $services;
    }

    protected function initializeContaoFramework(): void
    {
        $this->get('contao.framework')->initialize();
    }

    protected function tagResponse(array $tags): void
    {
        if (!$this->has('fos_http_cache.http.symfony_response_tagger')) {
            return;
        }

        $this->get('fos_http_cache.http.symfony_response_tagger')->addTags($tags);
    }
}
