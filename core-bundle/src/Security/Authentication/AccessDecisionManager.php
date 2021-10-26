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
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager as SymfonyAccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class AccessDecisionManager implements AccessDecisionManagerInterface
{
    private AccessDecisionManagerInterface $inner;
    private iterable $voters;
    private ScopeMatcher $scopeMatcher;
    private RequestStack $requestStack;

    /**
     * @var array<SymfonyAccessDecisionManager>
     */
    private array $cache = [];

    public function __construct(AccessDecisionManagerInterface $inner, iterable $voters, ScopeMatcher $scopeMatcher, RequestStack $requestStack)
    {
        $this->inner = $inner;
        $this->voters = $voters;
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

    private function getAccessDecisionManager(Request $request): SymfonyAccessDecisionManager
    {
        $cacheKey = $this->scopeMatcher->isBackendRequest($request) ? 'BE' : 'FE';

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        // Back end is allowed by default, front end is the opposite
        $allowIfAllAbstain = 'BE' === $cacheKey;

        return $this->cache[$cacheKey] = new SymfonyAccessDecisionManager(
            $this->voters,
            SymfonyAccessDecisionManager::STRATEGY_PRIORITY,
            $allowIfAllAbstain
        );
    }
}
