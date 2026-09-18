<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Search\Backend\Security;

use Contao\CoreBundle\Search\Backend\Document;
use Contao\CoreBundle\Search\Backend\Provider\ProviderInterface;
use Contao\Date;
use Doctrine\DBAL\Connection;

/**
 * @experimental
 */
class DocumentAllowedGroupsResolver
{
    /**
     * @var array<int>|null
     */
    private array|null $backendGroupIds = null;

    public function __construct(
        private readonly Connection $connection,
        private readonly DocumentAccessEvaluator $documentAccessEvaluator,
        private readonly int $maxGroups = 10,
    ) {
    }

    /**
     * @return array<int>
     */
    public function resolveAllowedGroups(ProviderInterface $provider, Document $document): array
    {
        $allowedGroups = [];

        foreach ($this->getActiveBackendGroupIds() as $groupId) {
            if ($this->documentAccessEvaluator->isGrantedForGroup($provider, $document, $groupId)) {
                $allowedGroups[] = $groupId;
            }
        }

        return $allowedGroups;
    }

    /**
     * @return array<int>
     */
    private function getActiveBackendGroupIds(): array
    {
        if (null !== $this->backendGroupIds) {
            return $this->backendGroupIds;
        }

        $time = Date::floorToMinute();
        $groups = [];
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('id')
            ->from('tl_user_group')
            ->where("disable=0 AND (start='' OR start<=:time) AND (stop='' OR stop>:time)")
            ->orderBy('name', 'ASC')
            ->setParameter('time', $time)
        ;

        if ($this->maxGroups > 0) {
            $qb->setMaxResults($this->maxGroups);
        }

        $result = $qb->fetchFirstColumn();

        foreach ($result as $groupId) {
            $groups[] = (int) $groupId;
        }

        return $this->backendGroupIds = $groups;
    }
}
