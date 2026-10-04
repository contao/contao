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

use Contao\BackendUser;
use Contao\CoreBundle\Search\Backend\Document;
use Contao\CoreBundle\Search\Backend\Provider\ProviderInterface;
use Contao\CoreBundle\Security\Authentication\ContaoStrategyContext;

class DocumentAccessEvaluator
{
    /**
     * @var array<int, BackendUser>
     */
    private array $usersByGroupId = [];

    public function __construct(
        private readonly VirtualBackendUserFactory $virtualBackendUserFactory,
        private readonly ContaoStrategyContext $strategyContext,
    ) {
    }

    public function isGrantedForGroup(ProviderInterface $provider, Document $document, int $groupId): bool
    {
        $user = $this->usersByGroupId[$groupId] ??= $this->virtualBackendUserFactory->createForGroupId($groupId);

        return $this->strategyContext->runInContext(
            ContaoStrategyContext::CONTEXT_BACKEND,
            static fn (): bool => $provider->isDocumentGranted($user, $document),
        );
    }
}
