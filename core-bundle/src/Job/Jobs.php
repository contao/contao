<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Job;

use Contao\CoreBundle\Entity\Job as JobEntity;
use Contao\CoreBundle\Repository\JobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @experimental
 */
class Jobs
{
    public function __construct(
        private readonly JobRepository $jobRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
    ) {
    }

    public function createJob(Owner $owner): Job
    {
        $job = Job::new($owner);
        $this->persist($job);

        return $job;
    }

    public function createSystemJob(): Job
    {
        return $this->createJob(Owner::asSystem());
    }

    public function createUserJob(string|null $userId = null): Job
    {
        $userId ??= $this->security->getUser()?->getUserIdentifier();

        if (null === $userId) {
            throw new \LogicException('Cannot create a user job without having a user id.');
        }

        return $this->createJob(new Owner($userId));
    }

    /**
     * @return array<Job>
     */
    public function findMyPending(): array
    {
        $qb = $this->buildQueryBuilderForMine();

        if (!$qb) {
            return [];
        }

        $qb->andWhere('j.status = :status');
        $qb->setParameter('status', Status::PENDING->value);

        return $this->queryWithQueryBuilder($qb);
    }

    /**
     * @return array<Job>
     */
    public function findMine(): array
    {
        $qb = $this->buildQueryBuilderForMine();

        if (!$qb) {
            return [];
        }

        return $this->queryWithQueryBuilder($qb);
    }

    public function getByUuid(string $uuid): Job|null
    {
        $jobEntity = $this->jobRepository->find($uuid);

        if (null === $jobEntity) {
            return null;
        }

        return $jobEntity->toDto();
    }

    public function persist(Job $job): void
    {
        /** @var JobEntity|null $jobEntity */
        $jobEntity = $this->jobRepository->find($job->getUuid());

        if (null === $jobEntity) {
            $jobEntity = JobEntity::fromDto($job);
        } else {
            $jobEntity->updateFromDto($job);
        }

        if ($parent = $job->getParent()) {
            $this->persist($parent);
            $jobEntity->setParent($this->jobRepository->find($parent->getUuid()));
        }

        $this->entityManager->persist($jobEntity);
        $this->entityManager->flush();

        // If this job is a child, update the status of the parent if required.
        if ($job->getParent()) {
            $this->updateStatusBasedOnChildren($job->getParent());
        }
    }

    public function createChild(Job $job): Job
    {
        $child = Job::new($job->getOwner())->withParent($job);
        $this->persist($child);

        return $child;
    }

    private function buildQueryBuilderForMine(): QueryBuilder|null
    {
        $qb = $this->jobRepository->createQueryBuilder('j');
        $userid = $this->security->getUser()?->getUserIdentifier();

        if (null === $userid) {
            return null;
        }

        $expr = $qb->expr();

        $qb->andWhere('j.parent IS NULL'); // Only parents
        $qb->andWhere(
            $expr->orX(
                $expr->eq('j.owner', ':userOwner'),
                $expr->andX(
                    $expr->eq('j.public', true),
                    $expr->eq('j.owner', ':systemOwner'),
                ),
            ),
        );
        $qb->setParameter('userOwner', $userid);
        $qb->setParameter('systemOwner', Owner::SYSTEM);
        $qb->orderBy('j.createdAt', 'DESC');

        return $qb;
    }

    private function queryWithQueryBuilder(QueryBuilder $queryBuilder): array
    {
        $jobs = [];
        $jobEntities = $queryBuilder
            ->getQuery()
            ->getResult()
        ;

        /** @var JobEntity $jobEntity */
        foreach ($jobEntities as $jobEntity) {
            $jobs[] = $jobEntity->toDto();
        }

        return $jobs;
    }

    private function updateStatusBasedOnChildren(Job $job): void
    {
        $jobEntity = $this->jobRepository->find($job->getUuid());

        if (null === $jobEntity) {
            return;
        }

        if ($jobEntity->getChildren()->isEmpty()) {
            return;
        }

        $onePending = false;
        $allFinished = true;

        foreach ($jobEntity->getChildren() as $child) {
            if (Status::PENDING === $child->toDto()->getStatus()) {
                $onePending = true;
                break;
            }

            if (Status::FINISHED !== $child->toDto()->getStatus()) {
                $allFinished = false;
            }
        }

        if ($onePending) {
            $this->persist($job->markPending());

            return;
        }

        if ($allFinished) {
            $this->persist($job->markFinished());
        }
    }
}
