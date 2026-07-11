<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CommentsBundle\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class FaqCommentsVoter extends AbstractCommentsVoter
{
    protected function supportsSource(string $source): bool
    {
        return 'tl_faq' === $source;
    }

    protected function hasAccess(TokenInterface $token, string $source, int $parent): bool
    {
        return true;
    }
}
