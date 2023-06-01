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

use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

/**
 * @internal
 */
class RefererIdListener
{
    private string|null $token = null;

    public function __construct(
        private TokenGeneratorInterface $tokenGenerator,
        private ScopeMatcher $scopeMatcher,
    ) {
    }

    /**
     * Adds the referer ID to the request.
     */
    public function __invoke(RequestEvent $event): void
    {
        if (!$this->scopeMatcher->isBackendMainRequest($event)) {
            return;
        }

        $request = $event->getRequest();

        if (null === $this->token) {
            $this->token = $this->tokenGenerator->generateToken();
        }

        $request->attributes->set('_contao_referer_id', $this->token);
    }
}
