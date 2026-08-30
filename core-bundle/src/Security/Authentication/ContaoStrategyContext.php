<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Authentication;

use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\FirewallMapInterface;

class ContaoStrategyContext
{
    public const CONTEXT_BACKEND = 'contao_backend';

    public const CONTEXT_FRONTEND = 'contao_frontend';

    private int|null $contaoContextRequestId = null;

    private string|null $contaoContext = null;

    /**
     * @var list<string>
     */
    private array $forcedContexts = [];

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly FirewallMapInterface $firewallMap,
    ) {
    }

    /**
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    public function runInContext(string $context, callable $callback): mixed
    {
        if (!\in_array($context, [self::CONTEXT_BACKEND, self::CONTEXT_FRONTEND], true)) {
            throw new \InvalidArgumentException(\sprintf('Invalid Contao strategy context "%s".', $context));
        }

        $this->forcedContexts[] = $context;

        try {
            return $callback();
        } finally {
            array_pop($this->forcedContexts);
        }
    }

    public function isContaoContext(): bool
    {
        return null !== $this->getContext();
    }

    public function getContext(): string|null
    {
        if ([] !== $this->forcedContexts) {
            return $this->forcedContexts[array_key_last($this->forcedContexts)];
        }

        // Use the main request here because sub-requests cannot have their own firewall
        // in Symfony
        $request = $this->requestStack->getMainRequest();

        if (!$request || !$this->firewallMap instanceof FirewallMap) {
            $this->contaoContextRequestId = null;

            return $this->contaoContext = null;
        }

        $requestId = spl_object_id($request);

        if ($this->contaoContextRequestId === $requestId) {
            return $this->contaoContext;
        }

        $this->contaoContextRequestId = $requestId;
        $config = $this->firewallMap->getFirewallConfig($request);

        if (!$config instanceof FirewallConfig) {
            return $this->contaoContext = null;
        }

        $context = $config->getContext();

        return $this->contaoContext = \in_array($context, [self::CONTEXT_BACKEND, self::CONTEXT_FRONTEND], true) ? $context : null;
    }
}
