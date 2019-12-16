<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing;

use Contao\ArticleModel;
use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class PreviewUrlGenerator
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(EventDispatcherInterface $eventDispatcher, RouterInterface $router, RequestStack $requestStack, ContaoFramework $framework)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->framework = $framework;
    }

    public function getPreviewUrl(): string
    {
        if (!$request = $this->requestStack->getCurrentRequest()) {
            throw new \RuntimeException('The request stack did not contain a request');
        }

        $url = $this->router->generate('contao_backend_preview');

        if (!\defined('CURRENT_ID') || !CURRENT_ID || !$do = $request->query->get('do')) {
            return $url;
        }

        if ('page' === $do) {
            return $url.'?page='.CURRENT_ID;
        }

        if ('article' === $do) {
            /** @var ArticleModel $adapter */
            $adapter = $this->framework->getAdapter(ArticleModel::class);

            if (!$article = $adapter->findByPk(CURRENT_ID)) {
                return $url;
            }

            return $url.'?page='.$article->pid;
        }

        $event = new PreviewUrlCreateEvent($do, CURRENT_ID);
        $this->eventDispatcher->dispatch($event, ContaoCoreEvents::PREVIEW_URL_CREATE);

        if ($query = $event->getQuery()) {
            return $url.'?'.$query;
        }

        return $url;
    }
}
