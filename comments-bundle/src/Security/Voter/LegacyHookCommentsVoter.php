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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class LegacyHookCommentsVoter extends AbstractCommentsVoter
{
    public function __construct(
        private readonly ContaoFramework $framework,
    ) {
    }

    protected function supportsSource(string $source): bool
    {
        return true;
    }

    protected function hasAccess(TokenInterface $token, string $source, int $parent): bool
    {
        if (!isset($GLOBALS['TL_HOOKS']['isAllowedToEditComment']) || !\is_array($GLOBALS['TL_HOOKS']['isAllowedToEditComment'])) {
            return false;
        }

        trigger_deprecation('contao/comments-bundle', '5.6', 'The isAllowedToEditComment hook is deprecated and will be removed in Contao 6. Implement a security voters based on AbstractCommentsVoter instead.');

        $systemAdapter = $this->framework->getAdapter(System::class);

        foreach ($GLOBALS['TL_HOOKS']['isAllowedToEditComment'] as $callback) {
            if (true === $systemAdapter->importStatic($callback[0])->{$callback[1]}($parent, $source)) {
                return true;
            }
        }

        return false;
    }
}
