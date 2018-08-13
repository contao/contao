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
use Contao\CoreBundle\HttpKernel\Header\HeaderStorageInterface;
use Contao\CoreBundle\HttpKernel\Header\NativeHeaderStorage;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
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
 *
 * To prevent session cookies from being stored in the HTTP cache, we remove
 * them from the response and send them directly. Any other cookie makes the
 * response uncacheable.
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
     * @var HeaderStorageInterface
     */
    private $headerStorage;

    /**
     * Constructor.
     *
     * @param BaseSessionListener         $inner
     * @param ContaoFrameworkInterface    $framework
     * @param ScopeMatcher                $scopeMatcher
     * @param HeaderStorageInterface|null $headerStorage
     */
    public function __construct(BaseSessionListener $inner, ContaoFrameworkInterface $framework, ScopeMatcher $scopeMatcher, HeaderStorageInterface $headerStorage = null)
    {
        $this->inner = $inner;
        $this->framework = $framework;
        $this->scopeMatcher = $scopeMatcher;
        $this->headerStorage = $headerStorage ?: new NativeHeaderStorage();
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
        if (!method_exists($this->inner, 'onKernelResponse')) {
            return;
        }

        if (!$this->framework->isInitialized() || !$this->scopeMatcher->isFrontendMasterRequest($event)) {
            $this->inner->onKernelResponse($event);

            return;
        }

        $session = $event->getRequest()->getSession();

        // Save the session (forward compatibility with Symfony 4.1)
        if ($session && $session->isStarted()) {
            $session->save();
        }

        $this->handleResponseCookies($event->getResponse());
    }

    /**
     * {@inheritdoc}
     */
    public function onFinishRequest(FinishRequestEvent $event): void
    {
        if (!method_exists($this->inner, 'onFinishRequest')) {
            return;
        }

        $this->inner->onFinishRequest($event);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return AbstractSessionListener::getSubscribedEvents();
    }

    /**
     * Mark the response as uncachable if it has a non-session cookie.
     *
     * @param Response $response
     */
    private function handleResponseCookies(Response $response): void
    {
        // Move the session cookie from the Symfony response to PHP headers
        foreach ($response->headers->getCookies() as $cookie) {
            if (session_name() === $cookie->getName()) {
                $response->headers->removeCookie($cookie->getName(), $cookie->getPath(), $cookie->getDomain());
                $this->headerStorage->add('Set-Cookie: '.$cookie);
                break;
            }
        }

        if ($response->isCacheable() && !empty($response->headers->getCookies(ResponseHeaderBag::COOKIES_ARRAY))) {
            $response
                ->setPrivate()
                ->setMaxAge(0)
                ->headers->addCacheControlDirective('must-revalidate')
            ;
        }
    }
}
