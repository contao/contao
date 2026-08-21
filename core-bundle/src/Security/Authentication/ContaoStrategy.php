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

use Symfony\Component\Security\Core\Authorization\AccessDecision;
use Symfony\Component\Security\Core\Authorization\Strategy\AccessDecisionStrategyInterface;

class ContaoStrategy implements AccessDecisionStrategyInterface, \Stringable
{
    public function __construct(
        private readonly AccessDecisionStrategyInterface $defaultStrategy,
        private readonly AccessDecisionStrategyInterface $contaoStrategy,
        private readonly ContaoStrategyContext $strategyContext,
    ) {
    }

    public function __toString(): string
    {
        $strategy = $this->strategyContext->isContaoContext() ? $this->contaoStrategy : $this->defaultStrategy;

        if (method_exists($strategy, '__toString')) {
            return (string) $strategy;
        }

        return get_debug_type($strategy);
    }

    public function decide(\Traversable $results, AccessDecision|null $accessDecision = null): bool
    {
        if ($this->strategyContext->isContaoContext()) {
            return $this->contaoStrategy->decide($results, $accessDecision);
        }

        return $this->defaultStrategy->decide($results, $accessDecision);
    }
}
