<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\Security\Voter;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\NewsletterBundle\Security\ContaoNewsletterPermissions;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class NewsletterChannelAccessVoter implements VoterInterface, CacheableVoterInterface
{
    public function __construct(private readonly Security $security)
    {
    }

    public function supportsAttribute(string $attribute): bool
    {
        return $attribute === ContaoCorePermissions::DC_PREFIX.'tl_newsletter_channel';
    }

    public function supportsType(string $subjectType): bool
    {
        return \in_array($subjectType, [CreateAction::class, ReadAction::class, UpdateAction::class, DeleteAction::class], true);
    }

    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        foreach ($attributes as $attribute) {
            if (!$this->supportsAttribute($attribute)) {
                continue;
            }

            $isGranted = match (true) {
                $subject instanceof CreateAction => $this->security->isGranted(ContaoNewsletterPermissions::USER_CAN_CREATE_CHANNELS),
                $subject instanceof ReadAction,
                $subject instanceof UpdateAction => $this->security->isGranted(ContaoNewsletterPermissions::USER_CAN_EDIT_CHANNEL, $subject->getCurrentId()),
                $subject instanceof DeleteAction => $this->security->isGranted(ContaoNewsletterPermissions::USER_CAN_EDIT_CHANNEL, $subject->getCurrentId())
                    && $this->security->isGranted(ContaoNewsletterPermissions::USER_CAN_DELETE_CHANNELS),
                default => false,
            };

            if (!$isGranted) {
                return self::ACCESS_DENIED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }
}
