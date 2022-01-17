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

use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\FeedEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class FeedController
{
    private EventDispatcherInterface $dispatcher;
    private Environment $twig;

    // TODO: Add ATOM feed
    // TODO: Add JSON feed (see https://www.jsonfeed.org/)
    private array $types = [
        'rss' => [
            'template' => '@ContaoCore/Frontend/Feed/rss.xml.twig',
            'content_type' => 'application/rss+xml',
        ],
    ];

    public function __construct(EventDispatcherInterface $dispatcher, Environment $twig)
    {
        $this->dispatcher = $dispatcher;
        $this->twig = $twig;
    }

    /**
     * @Route("/share/{alias}.xml", name="feed", defaults={"_scope" = "frontend"})
     */
    public function __invoke(string $alias, Request $request): Response
    {
        $request->attributes->set('_feed', $alias);

        $event = new FeedEvent($alias, $request);
        $this->dispatcher->dispatch($event, ContaoCoreEvents::FEED);

        if (null === $event->getFeed() || null === $event->getType()) {
            throw new NotFoundHttpException();
        }

        $view = $this->types[$event->getType()] ?? null;

        if (!$view) {
            throw new \RuntimeException(sprintf('%s is not a supported feed type. Choose one of %s.', $event->getType(), implode(',', array_keys($this->types))));
        }

        $renderedFeed = $this->twig->render($view['template'], [
            'feed' => $event->getFeed(),
        ]);

        return new Response($renderedFeed, 200, ['Content-Type' => $view['content_type']]);
    }
}
