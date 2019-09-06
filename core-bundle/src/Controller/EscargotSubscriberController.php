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

use Contao\CoreBundle\Search\EscargotFactory;
use Contao\CoreBundle\Search\EventListener\ControllerResultProvidingSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_scope" = "backend"})
 */
class EscargotSubscriberController
{
    /**
     * @var EscargotFactory
     */
    private $escargotFactory;

    public function __construct(EscargotFactory $escargotFactory)
    {
        $this->escargotFactory = $escargotFactory;
    }

    /**
     * @Route("/_contao/escargot_subscriber", name="contao_escargot_subscriber")
     */
    public function __invoke(Request $request): Response
    {
        $subscriber = $request->query->get('subscriber');
        $jobId = $request->query->get('jobId');

        if (!$subscriber || !$jobId) {
            throw new NotFoundHttpException();
        }

        $subscribers = $this->escargotFactory->getSubscribers([$subscriber]);

        if (1 !== \count($subscribers)) {
            throw new NotFoundHttpException();
        }

        $subscriber = $subscribers[0];

        if (!$subscriber instanceof ControllerResultProvidingSubscriberInterface) {
            throw new NotFoundHttpException();
        }

        return $subscriber->controllerAction($request, $jobId);
    }
}
