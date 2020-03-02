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

use Contao\BackendUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class AccessDecisionManager implements AccessDecisionManagerInterface
{
    /**
     * @var iterable<VoterInterface>
     */
    private $voters;

    /**
     * @param iterable<VoterInterface> $voters
     */
    public function __construct(iterable $voters = [])
    {
        $this->voters = $voters;
    }

    public function decide(TokenInterface $token, array $attributes, $subject = null): bool
    {
        // In Contao, a back end admin user always has access to everything, there's no need to ask any voter
        if ($token->getUser() instanceof BackendUser && $token->getUser()->isAdmin) {
            return true;
        }

        foreach ($this->voters as $voter) {
            $result = $voter->vote($token, $subject, $attributes);

            if (VoterInterface::ACCESS_GRANTED === $result) {
                return true;
            }

            if (VoterInterface::ACCESS_DENIED === $result) {
                return false;
            }
        }

        // In Contao, by default users do have access to everything unless any voter explicitly
        // disallowed access
        return true;
    }
}
