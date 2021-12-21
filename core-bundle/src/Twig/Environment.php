<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig;

use Contao\CoreBundle\Event\TwigRenderEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Environment as BaseEnvironment;
use Twig\Loader\LoaderInterface;

/**
 * Dispatches a TwigRenderEvent before rendering but otherwise works
 * identically to the original @see BaseEnvironment.
 */
class Environment extends BaseEnvironment
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher, LoaderInterface $loader, $options = [])
    {
        $this->eventDispatcher = $eventDispatcher;

        parent::__construct($loader, $options);
    }

    public function render($name, array $context = []): string
    {
        $event = new TwigRenderEvent($name, $context);
        $this->eventDispatcher->dispatch($event);

        $output = parent::render($name, $event->getContext());

        foreach ($event->getPostRenderTransformers() as $transformer) {
            if ($transformer->supports($name)) {
                $output = $transformer->transform($output);
            }
        }

        return $output;
    }
}
