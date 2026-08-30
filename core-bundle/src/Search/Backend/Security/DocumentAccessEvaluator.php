<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Search\Backend\Security;

use Contao\CoreBundle\Search\Backend\Document;
use Contao\CoreBundle\Search\Backend\Provider\ProviderInterface;
use Contao\CoreBundle\Security\Authentication\ContaoStrategyContext;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class DocumentAccessEvaluator
{
    /**
     * @var array<int, TokenInterface>
     */
    private array $tokensByGroupId = [];

    public function __construct(
        private readonly VirtualBackendUserFactory $virtualBackendUserFactory,
        private readonly ContaoStrategyContext $strategyContext,
    ) {
    }

    public function isGrantedForGroup(ProviderInterface $provider, Document $document, int $groupId): bool
    {
        $token = $this->tokensByGroupId[$groupId] ??= $this->createTokenForGroup($groupId);

        return $this->strategyContext->runInContext(
            ContaoStrategyContext::CONTEXT_BACKEND,
            static fn (): bool => $provider->isDocumentGranted($token, $document),
        );
    }

    private function createTokenForGroup(int $groupId): TokenInterface
    {
        $user = $this->virtualBackendUserFactory->createForGroupId($groupId);

        return new UsernamePasswordToken($user, 'contao_backend', $user->getRoles());
    }
}
