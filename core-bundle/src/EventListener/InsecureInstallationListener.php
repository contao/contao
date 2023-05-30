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

use Contao\CoreBundle\Exception\InsecureInstallationException;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * @internal
 */
class InsecureInstallationListener
{
    private string $secret;
    private string $webDir;

    public function __construct(string $secret, string $webDir = '/public')
    {
        $this->secret = $secret;
        $this->webDir = $webDir;
    }

    /**
     * Throws an exception if the document root is insecure.
     */
    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Skip the check on localhost
        if (\in_array($request->getClientIp(), ['127.0.0.1', 'fe80::1', '::1'], true)) {
            return;
        }

        // The document root is not in a subdirectory
        if ('' !== $request->getBasePath()) {
            throw new InsecureInstallationException('Your installation is not secure. Please set the document root to the '.$this->webDir.' subfolder.');
        }

        // The secret is still at its default value or empty
        if ('contao_install' !== $request->attributes->get('_route') && (empty($this->secret) || 'ThisTokenIsNotSoSecretChangeIt' === $this->secret)) {
            throw new InsecureInstallationException('Your installation is not secure. Please set the "secret" in your parameters.yml or "APP_SECRET" in your .env.local.');
        }
    }
}
