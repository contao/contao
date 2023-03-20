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
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Http\FirewallMapInterface;

class AccessDecisionManager implements AccessDecisionManagerInterface
{
    /**
     * @internal
     */
    public function __construct(
        private AccessDecisionManagerInterface $inner,
        private AccessDecisionManagerInterface $contaoAccessDecisionManager,
        private RequestStack $requestStack,
        private FirewallMapInterface $firewallMap,
    ) {
    }

    public function decide(TokenInterface $token, array $attributes, $object = null): bool
    {
        if ($this->isContaoContext()) {
            return $this->contaoAccessDecisionManager->decide($token, $attributes, $object);
        }

        return $this->inner->decide($token, $attributes, $object);
    }

    private function isContaoContext(): bool
    {
        // Use the main request here because sub-requests cannot have
        // their own firewall in Symfony
        $request = $this->requestStack->getMainRequest();

        if (!$this->firewallMap instanceof FirewallMap || null === $request) {
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
