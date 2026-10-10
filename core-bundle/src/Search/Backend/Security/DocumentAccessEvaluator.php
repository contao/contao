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
use Contao\CoreBundle\Security\User\BackendUserFactory;

class DocumentAccessEvaluator
{
    /**
     * @var array<int, BackendUser>
     */
    private array $usersByGroupId = [];

    public function __construct(private readonly BackendUserFactory $userFactory)
    {
    }

    public function isGrantedForGroup(ProviderInterface $provider, Document $document, int $groupId): bool
    {
        $user = $this->usersByGroupId[$groupId] ??= $this->userFactory->createWithDefaults([
            'id' => 0,
            'username' => '__contao_backend_search_group_'.$groupId,
            'name' => '__contao_backend_search_group_'.$groupId,
            'admin' => false,
            'inherit' => 'group',
            'groups' => [$groupId],
        ]);

        return $provider->isDocumentGranted($user, $document);
    }
}
