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

use Symfony\Bundle\SecurityBundle\Security\FirewallContext;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class AccessDecisionManager implements AccessDecisionManagerInterface
{
    private AccessDecisionManagerInterface $inner;
    private AccessDecisionManagerInterface $contaoAccessDecisionManager;
    private FirewallContext $firewallContext;

    /**
     * @internal
     */
    public function __construct(AccessDecisionManagerInterface $inner, AccessDecisionManagerInterface $contaoAccessDecisionManager, FirewallContext $firewallContext)
    {
        $this->inner = $inner;
        $this->contaoAccessDecisionManager = $contaoAccessDecisionManager;
        $this->firewallContext = $firewallContext;
    }

    public function decide(TokenInterface $token, array $attributes, $object = null): bool
    {
        $config = $this->firewallContext->getConfig();
        $firewallName = $config ? $config->getName() : '';

        if ('contao_frontend' === $firewallName || 'contao_backend' === $firewallName) {
            return $this->contaoAccessDecisionManager->decide($token, $attributes, $object);
        }

        return $this->inner->decide($token, $attributes, $object);
    }
}
