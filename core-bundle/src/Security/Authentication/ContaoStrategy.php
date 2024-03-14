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
use Symfony\Component\Security\Core\Authorization\Strategy\AccessDecisionStrategyInterface;
use Symfony\Component\Security\Http\FirewallMapInterface;

class ContaoStrategy implements AccessDecisionStrategyInterface, \Stringable
{
    public function __construct(
        private readonly AccessDecisionStrategyInterface $originalStrategy,
        private readonly AccessDecisionStrategyInterface $priorityStrategy,
        private readonly RequestStack $requestStack,
        private readonly FirewallMapInterface $firewallMap,
    ) {
    }

    public function __toString(): string
    {
        if (!$this->isContaoContext()) {
            if (method_exists($this->originalStrategy, '__toString')) {
                return (string) $this->originalStrategy;
            }

            return get_debug_type($this->originalStrategy);
        }

        return (string) $this->priorityStrategy;
    }

    public function decide(\Traversable $results): bool
    {
        if ($this->isContaoContext()) {
            return $this->priorityStrategy->decide($results);
        }

        return $this->originalStrategy->decide($results);
    }

    private function isContaoContext(): bool
    {
        // Use the main request here because sub-requests cannot have their own firewall
        // in Symfony
        $request = $this->requestStack->getMainRequest();

        if (!$request || !$this->firewallMap instanceof FirewallMap) {
            return false;
        }

        $config = $this->firewallMap->getFirewallConfig($request);

        if (!$config instanceof FirewallConfig) {
            return false;
        }

        $context = $config->getContext();

        return 'contao_frontend' === $context || 'contao_backend' === $context;
    }
}
