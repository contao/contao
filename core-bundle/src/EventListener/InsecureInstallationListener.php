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
    /**
     * @var string
     */
    private $secret;

    public function __construct($secret)
    {
        $this->secret = $secret;
    }

    /**
     * Throws an exception if the document root is insecure.
     *
     * @throws InsecureInstallationException
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
            throw new InsecureInstallationException('Your installation is not secure. Please set the document root to the /web subfolder.');
        }

        // The secret is still at its default value or empty
        if (empty($this->secret) || 'ThisTokenIsNotSoSecretChangeIt' === $this->secret) {
            throw new InsecureInstallationException('Your installation is not secure. Please set the "secret" in your parameters.yml or "APP_SECRET" in your .env.local.');
        }
    }
}
