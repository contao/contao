<?php

namespace Contao\CoreBundle\Job;

use Contao\CoreBundle\Entity\Job as JobEntity;
use Contao\CoreBundle\Repository\JobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

class Jobs
{
    public function __construct(private EntityManagerInterface $entityManager, private Security $security)
    {

    }

    /**
     * @return array<Job>
     */
    public function findMyPending(): array
    {
        $qb = $this->buildQueryBuilderForMine();

        if (null === $qb) {
            return [];
        }

        $qb->andWhere('j.status = :status');
        $qb->setParameter('status', Status::PENDING->value);


        return $this->queryWithQueryBuilder($qb);
    }


    private function buildQueryBuilderForMine(): ?QueryBuilder
    {
        $qb = $this->getJobRepository()->createQueryBuilder('j');
        $userid = $this->security->getUser()?->getUserIdentifier();

        if (null === $userid) {
            return null;
        }

        $qb->andWhere($qb->expr()
            ->orX(
                $qb->expr()->eq('j.owner', $userid),
                $qb->expr()->eq('j.owner', Owner::SYSTEM),
            )
        );

        return $qb;
    }

    private function queryWithQueryBuilder(QueryBuilder $queryBuilder): array
    {
        $jobs = [];
        $jobEntities = $queryBuilder
            ->getQuery()
            ->getResult();

        /** @var JobEntity $jobEntity */
        foreach ($jobEntities as $jobEntity) {
            $jobs[] = $jobEntity->toDto();
        }

        return $jobs;
    }

    public function getByUuid(string $uuid): ?Job
    {
        $jobEntity = $this->getJobRepository()->find($uuid);

        if (null === $jobEntity) {
            return null;
        }

        return $jobEntity->toDto();
    }

    public function persist(Job $job): void
    {
        /** @var JobEntity|null $jobEntity */
        $jobEntity = $this->getJobRepository()->find($job->getUuid());

        if (null === $jobEntity) {
            $jobEntity = JobEntity::fromDto($job);
        } else {
            $jobEntity->updateFromDto($job);
        }

        $this->entityManager->persist($jobEntity);
        $this->entityManager->flush();
    }


    private function getJobRepository(): JobRepository
    {
        return $this->entityManager->getRepository(JobEntity::class);
    }
}
