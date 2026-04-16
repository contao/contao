<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Authorization;

use Contao\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecision;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * And access decision manager that caches results of scalar attributes and
 * objects if the token contains a Contao user instance. Mainly here for enhanced
 * performance in the Contao backend where identical isGranted() calls happen over
 * and over again.
 */
class ContaoUserAccessDecisionManager implements AccessDecisionManagerInterface, ResetInterface
{
    private const CACHE_LIMIT = 8192;

    /**
     * @var array<string, bool>
     */
    private array $cache = [];

    public function __construct(private readonly AccessDecisionManagerInterface $inner)
    {
    }

    public function decide(TokenInterface $token, array $attributes, mixed $object = null, AccessDecision|bool|null $accessDecision = null, bool $allowMultipleAttributes = false): bool
    {
        if (\is_bool($accessDecision)) {
            $allowMultipleAttributes = $accessDecision;
            $accessDecision = null;
        }

        if ($accessDecision instanceof AccessDecision) {
            return $this->inner->decide($token, $attributes, $object, $accessDecision, $allowMultipleAttributes);
        }

        $cacheKey = $this->getCacheKey($token, $attributes, $object, $allowMultipleAttributes);

        if (null === $cacheKey) {
            return $this->inner->decide($token, $attributes, $object, $accessDecision, $allowMultipleAttributes);
        }

        return $this->cache[$cacheKey] ?? $this->decideAndCache($cacheKey, $token, $attributes, $object, $accessDecision, $allowMultipleAttributes);
    }

    public function reset(): void
    {
        $this->cache = [];
    }

    private function decideAndCache(string $cacheKey, TokenInterface $token, array $attributes, mixed $object, AccessDecision|bool|null $accessDecision, bool $allowMultipleAttributes): bool
    {
        $decision = $this->inner->decide($token, $attributes, $object, $accessDecision, $allowMultipleAttributes);

        if (\count($this->cache) < self::CACHE_LIMIT) {
            $this->cache[$cacheKey] = $decision;
        }

        return $decision;
    }

    private function getCacheKey(TokenInterface $token, array $attributes, mixed $object, bool $allowMultipleAttributes): string|null
    {
        $user = $token->getUser();

        // Only handle Contao User instances to limit the scope of this cache
        if (!$user instanceof User) {
            return null;
        }

        // Ignore complex objects
        if (null !== $object && !\is_scalar($object)) {
            return null;
        }

        // Do not cache if the attributes are complex
        foreach ($attributes as $attribute) {
            if (!\is_scalar($attribute)) {
                return null;
            }
        }

        return implode("\0", [
            $user->getAccessDecisionCacheKey(),
            $allowMultipleAttributes ? '1' : '0',
            implode("\0", array_map($this->encodeScalar(...), $attributes)),
            $this->encodeScalar($object),
        ]);
    }

    /**
     * Preserve scalar types so values like false, "", 0 and "0" do not share a cache key.
     */
    private function encodeScalar(mixed $value): string
    {
        return get_debug_type($value).':'.$value;
    }
}
