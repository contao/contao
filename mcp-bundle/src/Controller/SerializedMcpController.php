<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\Controller;

use Mcp\Server\Transport\StreamableHttpTransport;
use Symfony\AI\McpBundle\Controller\McpController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Lock\LockFactory;

/**
 * @internal
 *
 * TODO: Only used to fix a concurrent request problem in mcp/sdk. Can be removed when
 * fixed upstream. See https://github.com/modelcontextprotocol/php-sdk/issues/275 for
 * more information.
 */
final class SerializedMcpController
{
    public function __construct(
        private readonly McpController $inner,
        private readonly LockFactory $lockFactory,
    ) {
    }

    public function handle(Request $request): Response
    {
        $sessionId = $request->headers->get(
            StreamableHttpTransport::SESSION_HEADER,
        );

        // Initialization does not have a session ID yet.
        if (null === $sessionId || '' === $sessionId) {
            return $this->inner->handle($request);
        }

        $lock = $this->lockFactory->createLock('contao-mcp-session-'.hash('sha256', $sessionId));
        $lock->acquire(true);

        try {
            return $this->inner->handle($request);
        } finally {
            $lock->release();
        }
    }
}
