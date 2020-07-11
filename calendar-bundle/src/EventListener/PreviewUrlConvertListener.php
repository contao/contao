<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\EventListener;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
class PreviewUrlConvertListener
{
    private $framework;

    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    public function __construct(ContaoFramework $framework, UrlGeneratorInterface $router)
    {
        $this->framework = $framework;
        $this->router = $router;
    }

    /**
     * Adds the front end preview URL to the event.
     */
    public function __invoke(PreviewUrlConvertEvent $event): void
    {
        if (!$this->framework->isInitialized()) {
            return;
        }

        $request = $event->getRequest();

        if (null === $request || null === ($eventModel = $this->getEventModel($request))) {
            return;
        }

        $event->setUrl($this->router->generate(
            PageRoute::ROUTE_NAME,
            [PageRoute::CONTENT_PARAMETER => $eventModel],
            UrlGeneratorInterface::ABSOLUTE_URL
        ));
    }

    private function getEventModel(Request $request): ?CalendarEventsModel
    {
        if (!$request->query->has('calendar')) {
            return null;
        }

        /** @var CalendarEventsModel $adapter */
        $adapter = $this->framework->getAdapter(CalendarEventsModel::class);

        return $adapter->findByPk($request->query->get('calendar'));
    }
}
