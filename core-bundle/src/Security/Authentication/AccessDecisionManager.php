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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class AccessDecisionManager implements AccessDecisionManagerInterface
{
    private AccessDecisionManagerInterface $inner;
    private AccessDecisionManagerInterface $contaoAccessDecisionManager;
    private ScopeMatcher $scopeMatcher;
    private RequestStack $requestStack;

    public function __construct(AccessDecisionManagerInterface $inner, AccessDecisionManagerInterface $contaoAccessDecisionManager, ScopeMatcher $scopeMatcher, RequestStack $requestStack)
    {
        $this->inner = $inner;
        $this->contaoAccessDecisionManager = $contaoAccessDecisionManager;
        $this->scopeMatcher = $scopeMatcher;
        $this->requestStack = $requestStack;
    }

    public function decide(TokenInterface $token, array $attributes, $object = null): bool
    {
        $request = $this->requestStack->getMainRequest();

        if (null === $request || !$this->scopeMatcher->isContaoRequest($request)) {
            return $this->inner->decide($token, $attributes, $object);
        }

        return $this->contaoAccessDecisionManager->decide($token, $attributes, $object);
    }
}
