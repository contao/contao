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

use Contao\CoreBundle\Cache\EntityCacheTags;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Model\Collection as ModelCollection;
use Doctrine\Common\Collections\Collection;
use FOS\HttpCacheBundle\Http\SymfonyResponseTagger;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class AbstractController extends SymfonyAbstractController
{
    public static function getSubscribedServices()/*: array*/
    {
        $services = parent::getSubscribedServices();

        $services['contao.framework'] = ContaoFramework::class;
        $services['event_dispatcher'] = EventDispatcherInterface::class;
        $services['logger'] = '?'.LoggerInterface::class;
        $services['fos_http_cache.http.symfony_response_tagger'] = '?'.SymfonyResponseTagger::class;
        $services['contao.csrf.token_manager'] = ContaoCsrfTokenManager::class;
        $services['contao.cache.entity_tags'] = EntityCacheTags::class;

        return $services;
    }

    protected function initializeContaoFramework(): void
    {
        $this->container->get('contao.framework')->initialize();
    }

    /**
     * @template T
     *
     * @param class-string<T> $class
     *
     * @return T
     *
     * @phpstan-return Adapter<T>
     */
    protected function getContaoAdapter(string $class)
    {
        return $this->container->get('contao.framework')->getAdapter($class);
    }

    /**
     * @param array|Collection|ModelCollection|string|object|null $tags
     */
    protected function tagResponse($tags): void
    {
        $this->container->get('contao.cache.entity_tags')->tagWith($tags);
    }

    /**
     * @return array{csrf_field_name: string, csrf_token_manager: ContaoCsrfTokenManager, csrf_token_id: string}
     */
    protected function getCsrfFormOptions(): array
    {
        return [
            'csrf_field_name' => 'REQUEST_TOKEN',
            'csrf_token_manager' => $this->container->get('contao.csrf.token_manager'),
            'csrf_token_id' => $this->getParameter('contao.csrf_token_name'),
        ];
    }
}
