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

use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class AccessDecisionManager implements AccessDecisionManagerInterface
{
    private AccessDecisionManagerInterface $inner;
    private AccessDecisionManagerInterface $backendDecisionManager;
    private AccessDecisionManagerInterface $frontendDecisionManager;
    private ScopeMatcher $scopeMatcher;
    private RequestStack $requestStack;

    public function __construct(AccessDecisionManagerInterface $inner, AccessDecisionManagerInterface $backendDecisionManager, AccessDecisionManagerInterface $frontendDecisionManager, ScopeMatcher $scopeMatcher, RequestStack $requestStack)
    {
        $this->inner = $inner;
        $this->backendDecisionManager = $backendDecisionManager;
        $this->frontendDecisionManager = $frontendDecisionManager;
        $this->scopeMatcher = $scopeMatcher;
        $this->requestStack = $requestStack;
    }

    public function decide(TokenInterface $token, array $attributes, $object = null)
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || !$this->scopeMatcher->isContaoRequest($request)) {
            return $this->inner->decide($token, $attributes, $object);
        }

        return $this->getAccessDecisionManager($request)->decide($token, $attributes, $object);
    }

    private function getAccessDecisionManager(Request $request): AccessDecisionManagerInterface
    {
        if ($this->scopeMatcher->isBackendRequest($request)) {
            return $this->backendDecisionManager;
        }

        return $this->frontendDecisionManager;
    }
}
