<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\HttpKernel\EventListener\SessionListener as BaseSessionListener;

/**
 * Decorates the default session listener.
 *
 * Symfony has recently changed their session listener to always make the
 * response private if the session has been started. Although we agree with the
 * change, it renders the HTTP cache unusable, because Contao always starts a
 * session (e.g. to store the user's language). This listener circumvents
 * Symfony's changes by not making the response private if the request is a
 * Contao front end request.
 */
class SessionListener implements EventSubscriberInterface
{
    /**
     * @var BaseSessionListener
     */
    private $inner;

    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * Constructor.
     *
     * @param BaseSessionListener      $inner
     * @param ContaoFrameworkInterface $framework
     * @param ScopeMatcher             $scopeMatcher
     */
    public function __construct(BaseSessionListener $inner, ContaoFrameworkInterface $framework, ScopeMatcher $scopeMatcher)
    {
        $this->inner = $inner;
        $this->framework = $framework;
        $this->scopeMatcher = $scopeMatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        $this->inner->onKernelRequest($event);
    }

    /**
     * {@inheritdoc}
     */
    public function onKernelResponse(FilterResponseEvent $event): void
    {
        if (!$this->framework->isInitialized() || !$this->scopeMatcher->isFrontendMasterRequest($event)) {
            $this->inner->onKernelResponse($event);

            return;
        }

        $session = $event->getRequest()->getSession();

        // Save the session (forward compatibility with Symfony 4.1)
        if ($session && $session->isStarted()) {
            $session->save();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onFinishRequest(FinishRequestEvent $event)
    {
        $this->inner->onFinishRequest($event);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return AbstractSessionListener::getSubscribedEvents();
    }
}
