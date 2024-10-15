<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Voter\BackendSearch;

use Contao\CoreBundle\Search\Backend\Hit;
use Contao\CoreBundle\Search\Backend\Provider\ProviderInterface;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @experimental
 */
class ProviderDelegatingVoter extends Voter
{
    /**
     * @param iterable<ProviderInterface> $providers
     */
    public function __construct(private readonly iterable $providers)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (ContaoCorePermissions::USER_CAN_ACCESS_BACKEND_SEARCH_HIT !== $attribute || !$subject instanceof Hit) {
            return false;
        }

        foreach ($this->providers as $provider) {
            if ($provider->supportsType($subject->getDocument()->getType())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Hit $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var ProviderInterface $provider */
        foreach ($this->providers as $provider) {
            if ($provider->supportsType($subject->getDocument()->getType())) {
                return $provider->isHitGranted($token, $subject);
            }
        }

        return false;
    }
}
