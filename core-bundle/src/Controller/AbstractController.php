<?php

namespace Contao\CoreBundle\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use FOS\HttpCacheBundle\Http\SymfonyResponseTagger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;
use Terminal42\ServiceAnnotationBundle\ServiceAnnotationInterface;

abstract class AbstractController extends SymfonyAbstractController implements ServiceAnnotationInterface
{
    /**
     * Initializes the Contao framework.
     */
    protected function initializeContaoFramework()
    {
        $this->get('contao.framework')->initialize();
    }

    /**
     * @param array $tags
     */
    protected function tagResponse(array $tags): void
    {
        if (!$this->has('fos_http_cache.http.symfony_response_tagger')) {
            return;
        }

        $this->get('fos_http_cache.http.symfony_response_tagger')->addTags($tags);
    }

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
}
